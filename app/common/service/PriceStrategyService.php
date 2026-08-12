<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\CrawlData;
use app\common\model\CrawlNotify;
use app\common\model\GameProduct;
use app\common\model\PriceStrategy;
use app\common\model\PriceStrategyLog;
use app\common\model\PriceStrategyProduct;
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
 * 2. 对策略绑定的每个产品，基于对标竞品池(crawl_data.target_id)算出出价，做保底价夹逼后改价。
 * 3. 每个产品一条执行日志（成功/跳过/失败）落 price_strategy_log。
 *
 * 维度配置结构（对应「正常模板」）：
 * {
 *   "dimensions": [
 *     {
 *       "type": "lowest",
 *       // 一、目标店铺过滤
 *       "blacklist_stores": [],   // 黑名单：命中 seller_id/seller_name 即剔除(永不竞价)
 *       "whitelist_stores": [],   // 白名单：命中则强制纳入(跳过库存过滤)
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
        $pending = CrawlNotify::where('status', CrawlNotify::STATUS_PENDING)->select();
        $result = ['notifies' => 0, 'strategies' => 0];
        foreach ($pending as $notify) {
            try {
                $agg = $this->runByCrawlTarget($notify->crawl_target_id);
                $notify->status = CrawlNotify::STATUS_DONE;
                $notify->processed_at = date('Y-m-d H:i:s');
                $notify->message = sprintf(
                    '执行策略%d个: 成功%d/跳过%d/失败%d',
                    $agg['strategies'], $agg['success'], $agg['skip'], $agg['fail']
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

    /**
     * 执行绑定了该竞品池且已启用(status=1)的全部策略。
     * 单个策略异常不影响其它策略；auto_run 不参与筛选。
     *
     * @return array{strategies:int, success:int, skip:int, fail:int}
     */
    public function runByCrawlTarget(int $crawlTargetId): array
    {
        $strategies = PriceStrategy::where('crawl_target_id', $crawlTargetId)
            ->where('status', PriceStrategy::STATUS_ON)
            ->select();
        $agg = ['strategies' => 0, 'success' => 0, 'skip' => 0, 'fail' => 0];
        foreach ($strategies as $strategy) {
            try {
                $stat = $this->runStrategy($strategy);
                $agg['strategies']++;
                $agg['success'] += $stat['success'];
                $agg['skip']    += $stat['skip'];
                $agg['fail']    += $stat['fail'];
            } catch (\Throwable $e) {
                Log::error('[PriceStrategyService] 策略执行异常 strategyId=' . $strategy->id . ': ' . $e->getMessage());
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
     * 执行单个策略：遍历绑定产品，逐个算价改价并记录日志。
     *
     * @return array{total:int, success:int, skip:int, fail:int}
     */
    public function runStrategy(PriceStrategy $strategy): array
    {
        $stat = ['total' => 0, 'success' => 0, 'skip' => 0, 'fail' => 0];

        $dimension = $this->firstDimension($strategy->config);

        // 取绑定的产品（含账号，用于改价）
        $productIds = PriceStrategyProduct::where('price_strategy_id', $strategy->id)->column('game_product_id');
        if (empty($productIds)) {
            $strategy->last_run_at = date('Y-m-d H:i:s');
            $strategy->save();
            return $stat;
        }
        $products = GameProduct::with(['gameAccount'])->whereIn('id', $productIds)->select();

        // 取对标竞品池：真实竞品数据来自 crawl_data(target_id)，由 Python 爬虫写入
        $competitors = CrawlData::where('target_id', $strategy->crawl_target_id)->select();

        foreach ($products as $product) {
            $stat['total']++;
            // 改价前价格必须在 handleProduct 之前取：改价成功时 handleProduct 会把 $product->price 改成新价
            $oldPrice = (float) $product->price;
            [$status, $newPrice, $refPrice, $message, $competitorId] = $this->handleProduct($product, $competitors, $dimension);

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
            return [PriceStrategyLog::STATUS_SKIP, $current, 0.0, $msg, null];
        }

        $lowest = (float) $lowestInfo['price'];
        $competitorId = (int) $lowestInfo['id'];

        // 2. 竞价幅度：算出我们的出价
        $bid = $this->applyBid($lowest, $dimension);

        // 3. 保底出价：出价低于保底价则不再竞价
        $floor = $this->numOrNull($dimension['floor_price'] ?? null);
        if ($floor !== null && $bid < $floor) {
            return [PriceStrategyLog::STATUS_SKIP, $current, $lowest, "出价({$bid})低于保底价({$floor})，不竞价", $competitorId];
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
            return [PriceStrategyLog::STATUS_SKIP, $current, $lowest, '出价非正数，已跳过', $competitorId];
        }
        // 与现价一致则不改。阈值按取整精度取「半个最小单位」，
        // 否则像 0.000479 vs 0.00048 这类 6 位小数的真实差异会被误判为相同而跳过。
        $epsilon = 0.5 * pow(10, -$precision);
        if (abs($bid - $current) < $epsilon) {
            return [PriceStrategyLog::STATUS_SKIP, $current, $lowest, '出价与现价一致，无需改价', $competitorId];
        }

        // 6. 应用改价（复用与手动改价相同的内部逻辑，改价在 PHP 侧调 G2G）
        try {
            GameProductPriceService::change($product, $bid);
            return [PriceStrategyLog::STATUS_SUCCESS, $bid, $lowest, '改价成功', $competitorId];
        } catch (\Throwable $e) {
            return [PriceStrategyLog::STATUS_FAIL, $current, $lowest, mb_substr($e->getMessage(), 0, 480), $competitorId];
        }
    }

    /**
     * 按「目标店铺过滤」规则筛选 crawl_data 竞品后取最低价，并保留命中的竞品信息。
     *
     * 过滤优先级：黑名单剔除 > 白名单强制纳入(跳过库存过滤) > 其余按库存过滤。
     * 店铺标识同时匹配 crawl_data 的 seller_id 与 seller_name。
     *
     * @return array{price:float,id:int}|null
     */
    protected function calcLowest(GameProduct $product, $competitors, array $dimension): ?array
    {
        $blacklist = $this->normalizeStoreIdentifiers((array) ($dimension['blacklist_stores'] ?? []));
        $whitelist = $this->normalizeStoreIdentifiers((array) ($dimension['whitelist_stores'] ?? []));
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

            $candidate = [
                'price' => $price,
                'id'    => (int) $c->id,
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
     * 规范化店铺标识，避免大小写或首尾空白导致黑白名单匹配失败。
     *
     * @param array<int, mixed> $values
     * @return array<int, string>
     */
    protected function normalizeStoreIdentifiers(array $values): array
    {
        $normalized = [];
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            $value = function_exists('mb_strtolower')
                ? mb_strtolower($value, 'UTF-8')
                : strtolower($value);
            $normalized[$value] = true;
        }
        return array_keys($normalized);
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
     */
    protected function firstDimension(?array $config): array
    {
        $dimensions = $config['dimensions'] ?? [];
        $dimension = $dimensions[0] ?? [];
        return array_merge([
            'type'             => PriceStrategy::DIMENSION_LOWEST,
            'blacklist_stores' => [],
            'whitelist_stores' => [],
            'min_stock'        => 0,
            'min_rating'       => 0,
            'floor_price'      => null,
            'ceiling_price'    => null,
            'bid_mode'         => PriceStrategy::BID_AMOUNT,
            'amplitude'        => 0,
            'round_precision'  => 4,
        ], is_array($dimension) ? $dimension : []);
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
