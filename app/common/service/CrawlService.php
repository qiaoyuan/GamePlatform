<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\CompetitorProduct;
use app\common\model\CrawlTarget;
use RuntimeException;
use think\facade\Log;

/**
 * G2G 竞品爬虫服务
 *
 * 工作流程：
 * 1. 根据 CrawlTarget 获取目标URL
 * 2. 通过 Node.js + Puppeteer 无头浏览器渲染页面并提取数据
 * 3. 将结构化数据写入 competitor_product 表
 */
class CrawlService
{
    /**
     * Node.js 爬虫脚本路径
     */
    protected string $scraperScript;

    /**
     * Node.js 可执行文件路径
     */
    protected string $nodeBin;

    public function __construct()
    {
        $this->scraperScript = app()->getRootPath() . 'scripts/crawl_g2g.mjs';
        $this->nodeBin       = $this->findNodeBin();
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

        if (! file_exists($this->scraperScript)) {
            throw new RuntimeException('爬虫脚本不存在: ' . $this->scraperScript);
        }

        $startTime = microtime(true);

        // ----- 1. 调用 Node.js Puppeteer 脚本 -----
        $url      = escapeshellarg($target['url']);
        $nodeBin  = escapeshellcmd($this->nodeBin);
        $script   = escapeshellarg($this->scraperScript);
        $command  = "{$nodeBin} {$script} --url={$url} 2>/dev/null";

        Log::info("[CrawlService] 执行爬虫: url={$target['url']}");

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            Log::error("[CrawlService] 爬虫执行失败, exitCode={$exitCode}, output=" . implode("\n", $output));
            throw new RuntimeException('爬虫执行失败, exitCode=' . $exitCode);
        }

        $json = implode("\n", $output);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("[CrawlService] JSON 解析失败: " . json_last_error_msg() . ", raw=" . substr($json, 0, 500));
            throw new RuntimeException('爬虫返回数据解析失败');
        }

        if (empty($data) || ! is_array($data)) {
            Log::warning("[CrawlService] 爬虫未提取到数据, url={$target['url']}");
            $crawlAt = date('Y-m-d H:i:s');
            CrawlTarget::where('id', $targetId)->update(['last_crawl_at' => $crawlAt]);
            return [
                'target'   => $target->toArray(),
                'products' => [],
                'count'    => 0,
                'elapsed'  => round(microtime(true) - $startTime, 2),
            ];
        }

        // ----- 2. 写入数据库（同一批次覆盖写入） -----
        $crawlAt = date('Y-m-d H:i:s');

        // 删除该目标的上次爬取结果
        CompetitorProduct::where('crawl_target_id', $targetId)->delete();

        $inserted = 0;
        foreach ($data as $item) {
            $product = new CompetitorProduct;
            $product->save([
                'crawl_target_id' => $targetId,
                'store_name'      => $item['store_name'] ?? '',
                'store_url'       => $this->buildFullUrl($item['store_url'] ?? ''),
                'store_level'     => $item['store_level'] ?? '',
                'stock'           => $item['stock'] ?? '',
                'price'           => (float) ($item['price'] ?? 0),
                'currency'        => $item['currency'] ?? 'USD',
                'crawl_at'        => $crawlAt,
            ]);
            $inserted++;
        }

        // 更新目标的最后爬取时间
        CrawlTarget::where('id', $targetId)->update(['last_crawl_at' => $crawlAt]);

        $elapsed = round(microtime(true) - $startTime, 2);
        Log::info("[CrawlService] 爬取完成, targetId={$targetId}, count={$inserted}, elapsed={$elapsed}s");

        return [
            'target'   => $target->toArray(),
            'products' => CompetitorProduct::where('crawl_target_id', $targetId)
                ->order('price', 'asc')
                ->select()
                ->toArray(),
            'count'   => $inserted,
            'elapsed' => $elapsed,
        ];
    }

    /**
     * 测试：直接用静态HTML演示解析逻辑（无需Puppeteer）
     */
    public function testParse(string $html): array
    {
        $script = app()->getRootPath() . 'scripts/crawl_g2g.mjs';
        if (! file_exists($script)) {
            throw new RuntimeException('爬虫脚本不存在');
        }

        // 将HTML写入临时文件
        $tmpFile = app()->getRuntimePath() . 'crawl_test.html';
        file_put_contents($tmpFile, $html);

        $nodeBin = escapeshellcmd($this->nodeBin);
        $script  = escapeshellarg($script);
        $tmp     = escapeshellarg($tmpFile);
        $command = "{$nodeBin} {$script} --file={$tmp} 2>/dev/null";

        $output = [];
        exec($command, $output, $exitCode);

        @unlink($tmpFile);

        if ($exitCode !== 0) {
            throw new RuntimeException('解析失败');
        }

        $data = json_decode(implode("\n", $output), true);
        return is_array($data) ? $data : [];
    }

    /**
     * 补全相对URL
     */
    protected function buildFullUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }
        if (preg_match('#^https?://#', $url)) {
            return $url;
        }
        return 'https://www.g2g.com' . $url;
    }

    /**
     * 查找 Node.js 可执行文件
     */
    protected function findNodeBin(): string
    {
        // 优先使用 nvm 管理的版本
        $candidates = [
            '/usr/local/bin/node',
            '/opt/homebrew/bin/node',
        ];

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        // 通过 which 查找
        $node = trim(shell_exec('which node 2>/dev/null') ?? '');
        if ($node && is_executable($node)) {
            return $node;
        }

        return 'node';
    }
}
