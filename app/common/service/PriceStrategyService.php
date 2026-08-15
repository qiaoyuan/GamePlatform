<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\CrawlData;
use app\common\model\CrawlNotify;
use app\common\model\CrawlTarget;
use app\common\model\GameProduct;
use app\common\model\PriceStrategy;
use app\common\model\PriceStrategyLog;
use think\facade\Log;

/**
 * 改价策略执行服务
 *
 * 架构：Python 爬虫把竞品数据写入 crawl_data，并写一条 crawl_notify 通知；
 *       PHP 用 price:strategy:consume 命令消费通知，执行绑定该竞品池的策略，
 *       算出出价后复用 GameProductPriceService::change() 调 G2G 改价（改价在 PHP 侧）。
 *
 * 职责：
 * 1. 读取策略的维度配置(config.dimensions)，首期支持 type=lowest（跟竞品最低价）。
 * 2. 对爬虫目标绑定的唯一游戏产品，基于该目标当前版本的 crawl_data 算出出价，做保底价夹逼后改价。
 * 3. 每个目标产品一条执行日志（成功/跳过/失败）落 price_strategy_log。
 *
 * 维度配置结构（对应「正常模板」）：
 * {
 *   "dimensions": [
 *     {
 *       "type": "lowest",
 *       // 一、目标店铺过滤
 *       "blacklist_stores": [],   // 黑名单：命中 seller_id/seller_name 即剔除(永不竞价)
 *       "whitelist_stores": [],   // 白名单：命中则强制纳入(跳过库存过滤)
 *       "minimum_price": null,    // 竞品参考价下限：价格小于等于此值的竞品不参与最低价计算
 *       "min_stock": 0,           // 库存过滤：低于此库存(stock_num)的店铺不竞价，0=不限
 *       "min_rating": 0,          // 好评率过滤：crawl_data 暂无该字段，忽略
 *       // 二、保底出价
 *       "floor_price": null,      // 最低出价，出价低于此值则不再竞价(跳过)
 *       "ceiling_price": null,    // 价格上限(可选，超过则封顶)
 *       // 三、竞价幅度
 *       "bid_mode": "amount",     // amount 幅度值 | equal 等值
 *       "amplitude": 1,           // 幅度值：出价 = 目标价 - amplitude（负值即加价）
 *       "round_precision": 4
 *     }
 *   ]
 * }
 * // 改价频率(interval_minutes) 与状态(status/auto_run) 是策略级字段，不在 config 内。
 */
class PriceStrategyService
{
    /**
     * 消费爬取完成通知：取待处理通知，逐条执行绑定该竞品池的策略，并标记处理结果。
     * 供 price:strategy:consume 命令调用（信号驱动的主入口）。
     *
     * @return array{notifies:int, strategies:int}
     */
    public function consumeNotify(): array
    {
        $pending = CrawlNotify::where('status', CrawlNotify::STATUS_PENDING)
            ->order('id', 'asc')
            ->select();
        $processedTargets = [];
        $result = ['notifies' => 0, 'strategies' => 0];
        foreach ($pending as $notify) {
            $targetId = (int) $notify->crawl_target_id;
            try {
                $target = CrawlTarget::find($targetId);
                if (!$target) {
                    throw new \RuntimeException('爬虫目标不存在: ' . $targetId);
                }
                // 以本次消费开始时的目标版本作为快照，避免执行过程中目标版本变化导致混用数据。
                $version = (int) ($target->version ?? 0);
                $executionKey = $targetId . ':' . $version;
                if (isset($processedTargets[$executionKey])) {
                    // 同一轮消费中，同一目标同一版本只执行一次，避免重复通知重复改价。
                    $agg = ['strategies' => 0, 'success' => 0, 'skip' => 0, 'fail' => 0];
                    $duplicated = true;
                } else {
                    $agg = $this->runByCrawlTarget($targetId, $version);
                    $processedTargets[$executionKey] = true;
                    $duplicated = false;
                }
                $notify->status = CrawlNotify::STATUS_DONE;
                $notify->processed_at = date('Y-m-d H:i:s');
                $notify->message = $duplicated
                    ? sprintf('目标%d版本%d已执行，跳过重复策略', $targetId, $version)
                    : sprintf(
                        '目标%d版本%d执行策略%d个: 成功%d/跳过%d/失败%d',
                        $targetId,
                        $version,
                        $agg['strategies'],
                        $agg['success'],
                        $agg['skip'],
                        $agg['fail']
                    );
                $notify->save();
                $result['notifies']++;
                $result['strategies'] += $agg['strategies'];
            } catch (\Throwable $e) {
                $notify->status = CrawlNotify::STATUS_FAIL;
                $notify->processed_at = date('Y-m-d H:i:s');
                $notify->message = mb_substr($e->getMessage(), 0, 480);
                $notify->save();
                Log::error('[PriceStrategyService] 消费通知异常 notifyId=' . $notify->id . ': ' . $e->getMessage());
            }
        }
        return $result;
    }

    private function getCrawlTargetVersion(int $crawlTargetId): int
    {
        $target = CrawlTarget::find($crawlTargetId);
        if (!$target) {
            throw new \RuntimeException('爬虫目标不存在: ' . $crawlTargetId);
        }
        return (int) ($target->version ?? 0);
    }

    /**
     * 执行绑定了该竞品池且已启用(status=1)的全部策略。
     * 单个策略异常不影响其它策略；auto_run 不参与筛选。
     *
     * @return array{strategies:int, success:int, skip:int, fail:int}
     */
    public function runByCrawlTarget(int $crawlTargetId, ?int $version = null): array
    {
        $version = $version ?? $this->getCrawlTargetVersion($crawlTargetId);
        $strategies = PriceStrategy::where('crawl_target_id', $crawlTargetId)
            ->where('status', PriceStrategy::STATUS_ON)
            ->select();
        $agg = ['strategies' => 0, 'success' => 0, 'skip' => 0, 'fail' => 0];
        foreach ($strategies as $strategy) {
            // 先统计已匹配并尝试执行的策略，避免策略内部异常时错误显示为 0 个。
            $agg['strategies']++;
            try {
                $stat = $this->runStrategy($strategy, $version);
                $agg['success'] += $stat['success'];
                $agg['skip']    += $stat['skip'];
                $agg['fail']    += $stat['fail'];
            } catch (\Throwable $e) {
                $agg['fail']++;
                Log::error('[PriceStrategyService] 策略执行异常 strategyId=' . $strategy->id
                    . ' targetId=' . $crawlTargetId . ' version=' . $version . ': ' . $e->getMessage());
            }
        }
        return $agg;
    }

    /**
     * 定时任务：执行所有到期且已启用(status=1)的策略（interval_minutes>0）。
     * 供 price:strategy:run 命令调用（可选的按频率触发，与信号驱动互补）。
     *
     * @return int 本次执行的策略数
     */
    public function runDue(): int
    {
        $strategies = PriceStrategy::where('status', PriceStrategy::STATUS_ON)
            ->where('interval_minutes', '>', 0)
            ->select();
        $count = 0;
        $now = time();
        foreach ($strategies as $strategy) {
            $last = $strategy->last_run_at ? strtotime($strategy->last_run_at) : 0;
            if ($last && ($now - $last) < $strategy->interval_minutes * 60) {
                continue; // 未到频率
            }
            try {
                $this->runStrategy($strategy);
                $count++;
            } catch (\Throwable $e) {
                Log::error('[PriceStrategyService] 定时策略执行异常 strategyId=' . $strategy->id . ': ' . $e->getMessage());
            }
        }
        return $count;
    }

    /**
     * 执行单个策略：使用爬虫目标绑定的唯一产品，按目标当前版本竞品算价并记录日志。
     *
     * @return array{total:int, success:int, skip:int, fail:int}
     */
    public function runStrategy(PriceStrategy $strategy, ?int $version = null): array
    {
        $stat = ['total' => 0, 'success' => 0, 'skip' => 0, 'fail' => 0];

        $target = CrawlTarget::find($strategy->crawl_target_id);
        if (!$target) {
            throw new \RuntimeException('策略绑定的爬虫目标不存在: ' . $strategy->crawl_target_id);
        }
        $currentVersion = (int) ($target->version ?? 0);
        if ($version !== null && $version !== $currentVersion) {
            throw new \RuntimeException(sprintf(
                '爬虫目标版本已变化，拒绝使用版本%d，当前版本为%d',
                $version,
                $currentVersion
            ));
        }
        $version = $version ?? $currentVersion;
        $gameProductId = (int) $target->game_product_id;
        if ($gameProductId <= 0) {
            throw new \RuntimeException('爬虫目标未绑定游戏产品: ' . $target->id);
        }

        $dimension = $this->firstDimension($strategy->config);

        // 一个爬虫目标只服务一个游戏产品，策略执行不再使用旧的多产品绑定表决定改价对象。
        $products = GameProduct::with(['gameAccount'])
            ->where('id', $gameProductId)
            ->select();
        if (count($products) === 0) {
            throw new \RuntimeException('爬虫目标绑定的游戏产品不存在: ' . $gameProductId);
        }

        // 只取该目标当前版本、且属于该目标游戏产品的竞品，避免历史版本或其他产品参与竞价。
        $competitors = CrawlData::where('target_id', $strategy->crawl_target_id)
            ->where('game_product_id', $gameProductId)
            ->where('version', $version)
            ->select();

        foreach ($products as $product) {
            $stat['total']++;
            // 改价前价格必须在 handleProduct 之前取：改价成功时 handleProduct 会把 $product->price 改成新价
            $oldPrice = (float) $product->price;
            [$status, $newPrice, $refPrice, $message, $competitorId] = $this->handleProduct($product, $competitors, $dimension);
            $message = mb_substr(
                sprintf('目标ID=%d，版本=%d；%s', $strategy->crawl_target_id, $version, $message),
                0,
                500
            );

            try {
                PriceStrategyLog::record([
                    'price_strategy_id' => $strategy->id,
                    'game_product_id'   => $product->id,
                    'competitor_id'     => $competitorId,
                    'old_price'         => $oldPrice,
                    'new_price'         => $newPrice,
                    'ref_price'         => $refPrice,
                    'status'            => $status,
                    'message'           => $message,
                ]);
            } catch (\Throwable $e) {
                Log::error('[PriceStrategyService] 改价日志写入失败 strategyId=' . $strategy->id
                    . ' productId=' . $product->id . ': ' . $e->getMessage());
                throw $e;
            }

            if ($status === PriceStrategyLog::STATUS_SUCCESS) {
                $stat['success']++;
            } elseif ($status === PriceStrategyLog::STATUS_FAIL) {
                $stat['fail']++;
            } else {
                $stat['skip']++;
            }
        }

        $strategy->last_run_at = date('Y-m-d H:i:s');
        $strategy->save();

        return $stat;
    }

    /**
     * 处理单个产品：算目标价 -> 竞价幅度 -> 保底/上限夹逼 -> 应用改价。
     *
     * @param CrawlData[]|\think\Collection $competitors
     * @return array{0:int,1:float,2:float,3:string,4:int|null} [日志状态, 新价格, 参考价, 说明, 竞品数据ID]
     */
    protected function handleProduct(GameProduct $product, $competitors, array $dimension): array
    {
        $current = (float) $product->price;

        // 1. 计算目标价（过滤后竞品最低价）
        $lowestInfo = $this->calcLowest($product, $competitors, $dimension);
        if ($lowestInfo === null) {
            // 区分“真没竞品”和“币种不匹配”，给更友好的原因
            $currencies = [];
            foreach ($competitors as $c) {
                if ((float) $c->price > 0 && $c->currency) {
                    $currencies[$c->currency] = true;
                }
            }
            $productCurrency = $product->currency ?: GameProduct::DEFAULT_CURRENCY;
            if ($currencies && !isset($currencies[$productCurrency])) {
                $msg = '竞品币种(' . implode('/', array_keys($currencies)) . ')与产品币种(' . $productCurrency . ')不一致，未竞价';
            } else {
                $msg = '无可竞价的竞品（过滤后为空）';
            }
            $minimumPrice = $this->numOrNull($dimension['minimum_price'] ?? null);
            if ($minimumPrice !== null) {
                $msg .= '；已排除价格小于等于' . $minimumPrice . '的竞品';
            }
            return [PriceStrategyLog::STATUS_SKIP, $current, 0.0, $msg, null];
        }

        $lowest = (float) $lowestInfo['price'];
        $competitorId = (int) $lowestInfo['id'];
        $competitorContext = sprintf(
            '参考竞品ID=%d，seller_id=%s，seller_name=%s',
            $competitorId,
            $lowestInfo['seller_id'] !== '' ? $lowestInfo['seller_id'] : '--',
            $lowestInfo['seller_name'] !== '' ? $lowestInfo['seller_name'] : '--'
        );
        $minimumPrice = $this->numOrNull($dimension['minimum_price'] ?? null);
        if ($minimumPrice !== null) {
            $competitorContext .= '，已排除价格≤' . $minimumPrice . '的竞品';
        }

        // 2. 竞价幅度：算出我们的出价
        $bid = $this->applyBid($lowest, $dimension);

        // 3. 保底出价：出价低于保底价则不再竞价
        $floor = $this->numOrNull($dimension['floor_price'] ?? null);
        if ($floor !== null && $bid < $floor) {
            return [PriceStrategyLog::STATUS_SKIP, $current, $lowest, $this->withCompetitorContext("出价({$bid})低于保底价({$floor})，不竞价", $competitorContext), $competitorId];
        }

        // 4. 价格上限（可选）
        $ceiling = $this->numOrNull($dimension['ceiling_price'] ?? null);
        if ($ceiling !== null && $bid > $ceiling) {
            $bid = $ceiling;
        }

        // 5. 取整
        $precision = (int) ($dimension['round_precision'] ?? 4);
        $bid = round($bid, $precision);

        if ($bid <= 0) {
            return [PriceStrategyLog::STATUS_SKIP, $current, $lowest, '出价非正数，已跳过；' . $competitorContext, $competitorId];
        }
        // 与现价一致则不改。阈值按取整精度取「半个最小单位」，
        // 否则像 0.000479 vs 0.00048 这类 6 位小数的真实差异会被误判为相同而跳过。
        $epsilon = 0.5 * pow(10, -$precision);
        if (abs($bid - $current) < $epsilon) {
            return [PriceStrategyLog::STATUS_SKIP, $current, $lowest, '出价与现价一致，无需改价；' . $competitorContext, $competitorId];
        }

        // 6. 应用改价（复用与手动改价相同的内部逻辑，改价在 PHP 侧调 G2G）
        try {
            GameProductPriceService::change($product, $bid);
            return [PriceStrategyLog::STATUS_SUCCESS, $bid, $lowest, '改价成功；' . $competitorContext, $competitorId];
        } catch (\Throwable $e) {
            return [PriceStrategyLog::STATUS_FAIL, $current, $lowest, $this->withCompetitorContext(mb_substr($e->getMessage(), 0, 420), $competitorContext), $competitorId];
        }
    }

    /**
     * 按「目标店铺过滤」规则筛选 crawl_data 竞品后取最低价，并保留命中的竞品信息。
     *
     * 过滤优先级：黑名单剔除 > 白名单强制纳入(跳过库存过滤) > 其余按库存过滤。
     * 店铺标识同时匹配 crawl_data 的 seller_id 与 seller_name。
     *
     * @return array{price:float,id:int,seller_id:string,seller_name:string}|null
     */
    protected function calcLowest(GameProduct $product, $competitors, array $dimension): ?array
    {
        $blacklist = $this->normalizeStoreIdentifiers($dimension['blacklist_stores'] ?? []);
        $whitelist = $this->normalizeStoreIdentifiers($dimension['whitelist_stores'] ?? []);
        $minimumPrice = $this->numOrNull($dimension['minimum_price'] ?? null);
        $minStock  = (int) ($dimension['min_stock'] ?? 0);
        $currency  = $product->currency ?: GameProduct::DEFAULT_CURRENCY;

        $lowest = null;
        foreach ($competitors as $c) {
            // 同币种才可比
            if (($c->currency ?: '') !== $currency) {
                continue;
            }
            $price = (float) $c->price;
            if ($price <= 0) {
                continue;
            }
            // 店铺标识：seller_id 或 seller_name 命中即视为同一店铺，并统一大小写/空白
            $ids = $this->normalizeStoreIdentifiers([$c->seller_id, $c->seller_name]);

            // 黑名单：任何时候都不竞价
            if ($blacklist && array_intersect($ids, $blacklist)) {
                continue;
            }
            // 白名单：任何时候都竞价（跳过库存过滤）；非白名单才走后续过滤
            if (!($whitelist && array_intersect($ids, $whitelist))) {
                // 库存数量过滤：低于阈值不竞价（用解析好的 stock_num）
                if ($minStock > 0 && (int) $c->stock_num < $minStock) {
                    continue;
                }
            }

            // 价格小于等于门槛的竞品不参与最低价计算；只有严格大于门槛才是候选。
            if ($minimumPrice !== null && $price <= $minimumPrice) {
                continue;
            }

            $candidate = [
                'price'      => $price,
                'id'         => (int) $c->id,
                'seller_id'  => trim((string) $c->seller_id),
                'seller_name'=> trim((string) $c->seller_name),
            ];
            // 价格相同时使用较小的 crawl_data.id，保证命中记录稳定可追溯
            if ($lowest === null
                || $candidate['price'] < $lowest['price']
                || ($candidate['price'] === $lowest['price'] && $candidate['id'] < $lowest['id'])) {
                $lowest = $candidate;
            }
        }

        return $lowest;
    }

    /**
     * 在日志说明中追加实际参考竞品信息，并限制在日志字段长度内。
     */
    protected function withCompetitorContext(string $message, string $context): string
    {
        return mb_substr($message . '；' . $context, 0, 500);
    }

    /**
     * 规范化店铺标识，避免大小写、首尾空白或 Unicode 空白导致黑白名单匹配失败。
     *
     * 支持数组、JSON 数组字符串，以及换行/逗号分隔的文本，避免配置格式变化时
     * 整个黑名单被当成一个无法命中的字符串。
     *
     * @param mixed $values
     * @return array<int, string>
     */
    protected function normalizeStoreIdentifiers($values): array
    {
        $normalized = [];
        foreach ($this->expandStoreIdentifiers($values) as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $value = preg_replace('/\\s+/u', ' ', $value) ?? $value;
            $value = trim($value);
            $value = function_exists('mb_strtolower')
                ? mb_strtolower($value, 'UTF-8')
                : strtolower($value);
            $normalized[$value] = true;
        }
        return array_keys($normalized);
    }

    /**
     * 将黑白名单配置展开成店铺标识数组。
     *
     * @param mixed $values
     * @return array<int, string>
     */
    protected function expandStoreIdentifiers($values): array
    {
        if ($values === null || $values === '') {
            return [];
        }

        if (is_string($values)) {
            $text = trim($values);
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->expandStoreIdentifiers($decoded);
            }
            $values = preg_split('/[\\r\\n,，、]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        } elseif (!is_array($values)) {
            $values = [$values];
        }

        $expanded = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                $expanded = array_merge($expanded, $this->expandStoreIdentifiers($value));
            } elseif ($value !== null && $value !== '') {
                $expanded[] = (string) $value;
            }
        }
        return $expanded;
    }

    /**
     * 竞价幅度：把目标店铺价换算成我们的出价。
     * - amount 幅度值：出价 = 目标价 - amplitude（amplitude 为负则加价）
     * - equal  等值：出价 = 目标价
     */
    protected function applyBid(float $target, array $dimension): float
    {
        $mode = $dimension['bid_mode'] ?? PriceStrategy::BID_AMOUNT;
        if ($mode === PriceStrategy::BID_EQUAL) {
            return $target;
        }
        $amplitude = (float) ($dimension['amplitude'] ?? 0);
        return $target - $amplitude;
    }

    /**
     * 取第一个维度配置（首期只处理首个维度），并补齐默认值。
     *
     * @param mixed $config
     */
    protected function firstDimension($config): array
    {
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            $config = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array($config)) {
            $config = [];
        }

        $dimensions = $config['dimensions'] ?? [];
        if (is_string($dimensions)) {
            $decoded = json_decode($dimensions, true);
            $dimensions = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array($dimensions)) {
            $dimensions = [];
        }

        // 兼容早期直接把黑白名单放在 config 顶层的旧数据。
        $dimension = $dimensions[0] ?? [];
        if (!$dimension && (array_key_exists('blacklist_stores', $config) || array_key_exists('minimum_price', $config))) {
            $dimension = $config;
        }
        if (is_string($dimension)) {
            $decoded = json_decode($dimension, true);
            $dimension = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        $dimension = is_array($dimension) ? $dimension : [];

        $dimension = array_merge([
            'type'             => PriceStrategy::DIMENSION_LOWEST,
            'blacklist_stores' => [],
            'whitelist_stores' => [],
            'minimum_price'   => null,
            'min_stock'        => 0,
            'min_rating'       => 0,
            'floor_price'      => null,
            'ceiling_price'    => null,
            'bid_mode'         => PriceStrategy::BID_AMOUNT,
            'amplitude'        => 0,
            'round_precision'  => 4,
        ], $dimension);
        $dimension['blacklist_stores'] = $this->normalizeStoreIdentifiers($dimension['blacklist_stores']);
        $dimension['whitelist_stores'] = $this->normalizeStoreIdentifiers($dimension['whitelist_stores']);
        return $dimension;
    }

    /**
     * 把可能为 ''/null 的配置值转成 float 或 null。
     */
    protected function numOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float) $value;
    }
}
