<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\CompetitorProduct;
use app\common\model\CrawlTarget;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use think\facade\Log;

/**
 * G2G 竞品爬虫服务
 *
 * 工作流程：
 * 1. 从 CrawlTarget.url（G2G 商品分类页面地址）解析出 region_id / filter_attr / seo_term 等参数
 * 2. 直接请求 G2G 前端页面背后调用的 JSON API（sls.g2g.com/v3/offer/search），
 *    该接口无需登录、无需渲染，返回结构化的店铺报价数据
 * 3. 将结构化数据写入 competitor_product 表
 *
 * 注：早期版本用 Node.js + Puppeteer 无头浏览器渲染页面再解析 DOM，
 *    依赖本机 Node 环境（PHP-FPM 进程 PATH 里往往找不到 nvm 装的 node，导致爬取失败），
 *    且速度慢、易被反爬拦截。改为直接调用页面背后的 JSON API 后不再需要 Node 环境。
 */
class CrawlService
{
    /**
     * G2G 商品列表接口地址
     */
    protected string $apiUrl = 'https://sls.g2g.com/v3/offer/search';

    /**
     * 每页拉取数量（单次请求上限，超过则翻页拉取直至拉满或无更多数据）
     */
    protected int $pageSize = 20;

    /**
     * 最大翻页次数（防止目标数据量过大导致单次爬取耗时过长，也降低触发对方限流的概率）
     */
    protected int $maxPage = 3;

    /**
     * 翻页间隔（毫秒），避免请求过于密集触发对方限流/反爬
     */
    protected int $pageDelayMs = 800;

    protected Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
                'Origin' => 'https://www.g2g.com',
                'Referer' => 'https://www.g2g.com/',
            ],
        ]);
    }

    /**
     * 执行爬取
     *
     * @param int $targetId 爬取目标ID
     * @return array{target: array, products: array, count: int, elapsed: float}
     * @throws RuntimeException
     */
    public function crawl(int $targetId): array
    {
        $target = CrawlTarget::find($targetId);
        if (! $target) {
            throw new RuntimeException('爬取目标不存在');
        }

        $params = $this->parseTargetUrl($target['url']);

        $startTime = microtime(true);
        $rawList = $this->fetchAll($params);

        if (empty($rawList)) {
            Log::warning("[CrawlService] 未获取到数据, url={$target['url']}");
            $crawlAt = date('Y-m-d H:i:s');
            CrawlTarget::where('id', $targetId)->update(['last_crawl_at' => $crawlAt]);
            return [
                'target' => $target->toArray(),
                'products' => [],
                'count' => 0,
                'elapsed' => round(microtime(true) - $startTime, 2),
            ];
        }

        // ----- 写入数据库（同一批次覆盖写入） -----
        $crawlAt = date('Y-m-d H:i:s');

        // 删除该目标的上次爬取结果
        CompetitorProduct::where('crawl_target_id', $targetId)->delete();

        $inserted = 0;
        foreach ($rawList as $item) {
            $product = new CompetitorProduct;
            $product->save([
                'crawl_target_id' => $targetId,
                'store_name' => $item['username'] ?? '',
                'store_url' => $this->buildOfferUrl($item['offer_id'] ?? ''),
                'store_level' => isset($item['user_level']) ? '等级 ' . $item['user_level'] : '',
                'stock' => (string)($item['available_qty'] ?? ''),
                'price' => (float)($item['converted_unit_price'] ?? $item['unit_price'] ?? 0),
                'currency' => $item['display_currency'] ?? 'USD',
                'crawl_at' => $crawlAt,
            ]);
            $inserted++;
        }

        // 更新目标的最后爬取时间
        CrawlTarget::where('id', $targetId)->update(['last_crawl_at' => $crawlAt]);

        // 注：改价策略的触发已改为「信号驱动」——真实爬虫是 Python，爬完写 crawl_notify，
        // 由 php think price:strategy:consume 消费通知后执行策略改价，这里不再直接触发。

        $elapsed = round(microtime(true) - $startTime, 2);
        Log::info("[CrawlService] 爬取完成, targetId={$targetId}, count={$inserted}, elapsed={$elapsed}s");

        return [
            'target' => $target->toArray(),
            'products' => CompetitorProduct::where('crawl_target_id', $targetId)
                ->order('price', 'asc')
                ->select()
                ->toArray(),
            'count' => $inserted,
            'elapsed' => $elapsed,
        ];
    }

    /**
     * 循环翻页拉取全部数据（直到无更多数据或达到 maxPage 上限）
     */
    protected function fetchAll(array $params): array
    {
        $all = [];
        for ($page = 1; $page <= $this->maxPage; $page++) {
            if ($page > 1) {
                usleep($this->pageDelayMs * 1000);
            }
            $query = array_merge($params, [
                'page_size' => $this->pageSize,
                'page' => $page,
                'group' => 0,
                'v' => 'v2',
            ]);
            $list = $this->fetchPage($query);
            // 单页失败（超时/限流等）不影响已抓到的数据，直接停止翻页
            if ($list === null) {
                break;
            }
            if (empty($list)) {
                break;
            }
            $all = array_merge($all, $list);
            if (count($list) < $this->pageSize) {
                // 已到最后一页
                break;
            }
        }
        return $all;
    }

    /**
     * 请求单页数据。首页失败抛异常（说明目标本身有问题），后续页失败仅返回 null 静默跳过，
     * 保留已抓取到的前面页数据，不让单页波动导致整次爬取全部失败。
     */
    protected function fetchPage(array $query): ?array
    {
        try {
            $res = $this->http->get($this->apiUrl, ['query' => $query]);
            $json = json_decode((string)$res->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE || ($json['code'] ?? null) != 2000) {
                Log::warning('[CrawlService] 接口返回异常: ' . substr((string)$res->getBody(), 0, 300));
                if ($query['page'] == 1) {
                    throw new RuntimeException('接口返回异常');
                }
                return null;
            }
            return $json['payload']['results'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('[CrawlService] 请求异常(page=' . $query['page'] . '): ' . $e->getMessage());
            if ($query['page'] == 1) {
                throw new RuntimeException('爬取请求失败: ' . $e->getMessage());
            }
            return null;
        }
    }

    /**
     * 从 G2G 商品分类页面 URL 中解析出接口所需参数
     *
     * 示例输入：
     *   https://www.g2g.com/cn/categories/wow-gold/offer/group?fa=lgc_2299_platform%3Algc_2299_platform_39979&region_id=xxx
     * 解析出：
     *   seo_term=wow-gold, filter_attr=lgc_2299_platform:lgc_2299_platform_39979, region_id=xxx
     */
    protected function parseTargetUrl(string $url): array
    {
        $parts = parse_url($url);
        if (empty($parts['path']) || empty($parts['query'])) {
            throw new RuntimeException('目标链接格式不正确，无法解析参数');
        }

        parse_str($parts['query'], $queryParams);

        // 从路径 /cn/categories/{seo_term}/offer/group 中提取 seo_term
        $seoTerm = '';
        if (preg_match('#/categories/([^/]+)/#', $parts['path'], $m)) {
            $seoTerm = $m[1];
        }

        $regionId = $queryParams['region_id'] ?? '';
        $filterAttr = $queryParams['fa'] ?? ($queryParams['filter_attr'] ?? '');

        if ($seoTerm === '' || $regionId === '') {
            throw new RuntimeException('目标链接缺少必要参数(分类/region_id)，无法爬取');
        }

        $params = [
            'seo_term' => $seoTerm,
            'region_id' => $regionId,
            'currency' => $queryParams['currency'] ?? 'USD',
            'country' => $queryParams['country'] ?? 'CN',
        ];
        if ($filterAttr !== '') {
            $params['filter_attr'] = $filterAttr;
        }

        return $params;
    }

    /**
     * 根据 offer_id 拼接商品详情页链接
     */
    protected function buildOfferUrl(string $offerId): string
    {
        if ($offerId === '') {
            return '';
        }
        return 'https://www.g2g.com/offer/' . $offerId;
    }
}
