<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\CrawlData;
use app\common\model\CrawlNotify;
use app\common\model\CrawlTarget;
use app\common\model\GameProduct;
use app\common\model\PriceStrategy;
use app\common\model\PriceStrategyLog;
use app\common\model\PriceStrategyProduct;
use think\facade\Db;
use think\facade\Log;

/**
 * 改价策略执行服务
 *
 * 架构：Python 爬虫把竞品数据写入 crawl_data，并写一条 crawl_notify 通知；
 *       PHP 用 price:strategy:consume 命令消费通知，执行绑定该竞品池的策略，
 *       算出出价后复用 GameProductPriceService::change() 调平台接口改价（改价在 PHP 侧）。
 *
 * 职责：
 * 1. 读取策略的维度配置(config.dimensions)，首期支持 type=lowest（跟竞品最低价）。
 * 2. 对爬虫目标绑定的唯一游戏产品，基于该目标当前版本的 crawl_data 算出出价，做上限夹逼后改价。
 * 3. 每个目标产品一条执行日志（成功/跳过/失败）落 price_strategy_log。
 *
 * 维度配置结构（对应「正常模板」）：
 * {
 *   "dimensions": [
 *     {
 *       "type": "lowest",
 *       // 一、目标店铺过滤
 *       "blacklist_stores": [],   // 黑名单：命中 seller_id/seller_name 即剔除(永不竞价)
 *       "whitelist_stores": [],   // 白名单：命中则强制纳入(跳过库存/好评率过滤)
 *       "filter_price": null,      // 最低价：价格小于等于此值的竞品不参与最低价计算（仅过滤，不影响出价）
 *       "min_stock": 0,           // 策略库存阈值：填写无单位非负整数；竞品原始 stock 支持 K/M/G，0=不限
 *       "min_rating": 0,          // 好评率过滤：低于此好评率的店铺不竞价，0=不限
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
    private const MAX_ATTEMPTS = 5;
    private const RETRY_DELAYS = [5, 30, 120, 600, 1800];

    /**
     * 兼容一次性调用：持续领取，直至当前没有可执行通知。
     *
     * @return array{notifies:int, strategies:int}
     */
    public function consumeNotify(): array
    {
        $workerId = $this->makeWorkerId();
        $result = ['notifies' => 0, 'strategies' => 0];
        while (($one = $this->consumeOneNotify($workerId)) !== null) {
            $result['notifies']++;
            $result['strategies'] += $one['strategies'];
        }
        return $result;
    }

    /**
     * 原子领取并消费一条通知。返回 null 表示当前队列为空。
     *
     * @return array{notify_id:int,status:string,strategies:int,attempts:int}|null
     */
    public function consumeOneNotify(string $workerId, ?callable $heartbeat = null): ?array
    {
        $notify = $this->claimNextNotify($workerId);
        if ($notify === null) {
            return null;
        }

        $targetId = (int) $notify->crawl_target_id;
        $version = (int) $notify->version;
        $touch = function () use ($notify, $workerId, $heartbeat): void {
            $this->touchNotify((int) $notify->id, $workerId);
            if ($heartbeat !== null) {
                $heartbeat();
            }
        };

        try {
            $touch();
            $agg = $this->runByCrawlTarget($targetId, $version, $touch);
            if ($agg['fail'] > 0) {
                throw new \RuntimeException(sprintf(
                    '目标%d版本%d存在%d个改价失败项',
                    $targetId,
                    $version,
                    $agg['fail']
                ));
            }

            $message = sprintf(
                '目标%d版本%d执行策略%d个: 成功%d/跳过%d/失败%d',
                $targetId,
                $version,
                $agg['strategies'],
                $agg['success'],
                $agg['skip'],
                $agg['fail']
            );
            $updated = CrawlNotify::where('id', $notify->id)
                ->where('status', CrawlNotify::STATUS_PROCESSING)
                ->where('worker_id', $workerId)
                ->update([
                    'status'       => CrawlNotify::STATUS_DONE,
                    'message'      => mb_substr($message, 0, 500),
                    'processed_at' => date('Y-m-d H:i:s'),
                    'available_at' => null,
                    'worker_id'    => '',
                    'started_at'   => null,
                    'heartbeat_at' => null,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
            if ($updated !== 1) {
                throw new \RuntimeException('通知处理权已丢失，拒绝覆盖状态: notifyId=' . $notify->id);
            }
            return [
                'notify_id'  => (int) $notify->id,
                'status'     => 'done',
                'strategies' => $agg['strategies'],
                'attempts'   => (int) $notify->attempts,
            ];
        } catch (\Throwable $e) {
            $status = $this->retryOrFailNotify($notify, $workerId, $e);
            return [
                'notify_id'  => (int) $notify->id,
                'status'     => $status,
                'strategies' => 0,
                'attempts'   => (int) $notify->attempts,
            ];
        }
    }

    /**
     * 用 compare-and-set 领取任务；多个 Worker 读到同一行时只有一个能更新成功。
     */
    public function claimNextNotify(string $workerId): ?CrawlNotify
    {
        for ($i = 0; $i < 20; $i++) {
            $now = date('Y-m-d H:i:s');
            $candidate = CrawlNotify::where('status', CrawlNotify::STATUS_PENDING)
                ->where(function ($query) use ($now) {
                    $query->whereNull('available_at')->whereOr('available_at', '<=', $now);
                })
                ->order('id', 'asc')
                ->find();
            if (!$candidate) {
                return null;
            }

            $claimed = CrawlNotify::where('id', $candidate->id)
                ->where('status', CrawlNotify::STATUS_PENDING)
                ->where(function ($query) use ($now) {
                    $query->whereNull('available_at')->whereOr('available_at', '<=', $now);
                })
                ->update([
                    'status'       => CrawlNotify::STATUS_PROCESSING,
                    'attempts'     => Db::raw('attempts + 1'),
                    'worker_id'    => $workerId,
                    'started_at'   => $now,
                    'heartbeat_at' => $now,
                    'updated_at'   => $now,
                ]);
            if ($claimed !== 1) {
                continue;
            }

            /** @var CrawlNotify $notify */
            $notify = CrawlNotify::find($candidate->id);
            if (!$notify) {
                continue;
            }
            $resolvedVersion = null;
            try {
                $resolvedVersion = $this->resolveNotifyVersion($notify);
                $dedupeKey = $notify->crawl_target_id . ':' . $resolvedVersion;
                CrawlNotify::where('id', $notify->id)
                    ->where('status', CrawlNotify::STATUS_PROCESSING)
                    ->where('worker_id', $workerId)
                    ->update([
                        'version'    => $resolvedVersion,
                        'dedupe_key' => $dedupeKey,
                        'updated_at' => $now,
                    ]);
                // MySQL 默认返回实际发生变化的行数。重试时 version/dedupe_key 已经相同，
                // 或同一秒内 updated_at 未变化，UPDATE 返回 0 也可能仍然持有处理权，
                // 因此必须读取当前状态确认，不能把 affected rows=0 直接视为丢失租约。
                $notify = CrawlNotify::where('id', $notify->id)
                    ->where('status', CrawlNotify::STATUS_PROCESSING)
                    ->where('worker_id', $workerId)
                    ->find();
                if (!$notify
                    || (int) $notify->version !== $resolvedVersion
                    || (string) $notify->dedupe_key !== $dedupeKey) {
                    throw new \RuntimeException('设置通知幂等键失败或处理权已丢失: notifyId=' . $candidate->id);
                }
                return $notify;
            } catch (\Throwable $e) {
                $dedupeVersion = $resolvedVersion ?? (int) ($notify->version ?? 0);
                $dedupeKey = $notify->crawl_target_id . ':' . $dedupeVersion;
                $duplicate = CrawlNotify::where('dedupe_key', $dedupeKey)
                    ->where('id', '<>', $notify->id)
                    ->find();
                if ($duplicate) {
                    CrawlNotify::where('id', $notify->id)
                        ->where('status', CrawlNotify::STATUS_PROCESSING)
                        ->where('worker_id', $workerId)
                        ->update([
                            'status'       => CrawlNotify::STATUS_DONE,
                            'message'      => mb_substr('重复通知，已由通知' . $duplicate->id . '占用幂等键' . $dedupeKey, 0, 500),
                            'processed_at' => $now,
                            'available_at' => null,
                            'worker_id'    => '',
                            'started_at'   => null,
                            'heartbeat_at' => null,
                            'updated_at'   => $now,
                        ]);
                    continue;
                }
                $this->retryOrFailNotify($notify, $workerId, $e);
            }
        }

        return null;
    }

    /**
     * 回收真正失去心跳的处理中任务。运行中的 Worker 会在每个策略/产品前后续租。
     */
    public function recoverStaleNotifies(int $staleAfterSeconds): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - max(60, $staleAfterSeconds));
        $now = date('Y-m-d H:i:s');
        return CrawlNotify::where('status', CrawlNotify::STATUS_PROCESSING)
            ->whereRaw('COALESCE(heartbeat_at, started_at) < ?', [$cutoff])
            ->update([
                'status'       => CrawlNotify::STATUS_PENDING,
                'available_at' => $now,
                'worker_id'    => '',
                'started_at'   => null,
                'heartbeat_at' => null,
                'message'      => 'Worker心跳超时，已重新进入待处理队列',
                'updated_at'   => $now,
            ]);
    }

    public function makeWorkerId(): string
    {
        $host = gethostname() ?: 'unknown-host';
        return mb_substr($host . ':' . getmypid() . ':' . bin2hex(random_bytes(4)), 0, 100);
    }

    private function resolveNotifyVersion(CrawlNotify $notify): int
    {
        if ($notify->version !== null && $notify->version !== '') {
            return (int) $notify->version;
        }

        // 只为旧生产者保留兼容路径；新版 Python 必须把实际 version 写入通知。
        $target = CrawlTarget::find((int) $notify->crawl_target_id);
        if (!$target) {
            throw new \RuntimeException('爬虫目标不存在: ' . $notify->crawl_target_id);
        }
        Log::warning('[PriceStrategyService] 通知缺少version，临时使用目标当前版本 notifyId=' . $notify->id);
        return (int) ($target->version ?? 0);
    }

    private function touchNotify(int $notifyId, string $workerId): void
    {
        $updated = CrawlNotify::where('id', $notifyId)
            ->where('status', CrawlNotify::STATUS_PROCESSING)
            ->where('worker_id', $workerId)
            ->update([
                'heartbeat_at' => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        // 同一秒内连续心跳时字段值可能没有变化，MySQL 会返回 0；此时再查租约归属。
        if ($updated !== 1 && !CrawlNotify::where('id', $notifyId)
            ->where('status', CrawlNotify::STATUS_PROCESSING)
            ->where('worker_id', $workerId)
            ->find()) {
            throw new \RuntimeException('通知处理权已丢失: notifyId=' . $notifyId);
        }
    }

    private function retryOrFailNotify(CrawlNotify $notify, string $workerId, \Throwable $e): string
    {
        $attempts = max(1, (int) $notify->attempts);
        $failed = $attempts >= self::MAX_ATTEMPTS;
        $delay = self::RETRY_DELAYS[min($attempts - 1, count(self::RETRY_DELAYS) - 1)];
        $now = date('Y-m-d H:i:s');
        CrawlNotify::where('id', $notify->id)
            ->where('status', CrawlNotify::STATUS_PROCESSING)
            ->where('worker_id', $workerId)
            ->update([
                'status'       => $failed ? CrawlNotify::STATUS_FAIL : CrawlNotify::STATUS_PENDING,
                'available_at' => $failed ? null : date('Y-m-d H:i:s', time() + $delay),
                'processed_at' => $failed ? $now : null,
                'worker_id'    => '',
                'started_at'   => null,
                'heartbeat_at' => null,
                'message'      => mb_substr(($failed ? '最终失败: ' : '等待重试: ') . $e->getMessage(), 0, 500),
                'updated_at'   => $now,
            ]);
        Log::error('[PriceStrategyService] 消费通知异常 notifyId=' . $notify->id
            . ' attempts=' . $attempts . ': ' . $e->getMessage());
        return $failed ? 'failed' : 'retry';
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
    public function runByCrawlTarget(int $crawlTargetId, ?int $version = null, ?callable $heartbeat = null): array
    {
        $version = $version ?? $this->getCrawlTargetVersion($crawlTargetId);
        $strategies = PriceStrategy::where('crawl_target_id', $crawlTargetId)
            ->where('status', PriceStrategy::STATUS_ON)
            ->whereNull('deleted_at')
            ->select();
        $agg = ['strategies' => 0, 'success' => 0, 'skip' => 0, 'fail' => 0];
        foreach ($strategies as $strategy) {
            $heartbeat && $heartbeat();
            // 先统计已匹配并尝试执行的策略，避免策略内部异常时错误显示为 0 个。
            $agg['strategies']++;
            try {
                $stat = $this->runStrategy($strategy, $version, $heartbeat);
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
            ->whereNull('deleted_at')
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
     * 执行单个策略：从 price_strategy_product 取该策略绑定的所有产品，
     * 每个产品按策略绑定的爬虫目标当前版本竞品算价并各自记录日志。
     *
     * @return array{total:int, success:int, skip:int, fail:int}
     */
    public function runStrategy(PriceStrategy $strategy, ?int $version = null, ?callable $heartbeat = null): array
    {
        $stat = ['total' => 0, 'success' => 0, 'skip' => 0, 'fail' => 0];

        $target = CrawlTarget::find($strategy->crawl_target_id);
        if (!$target) {
            throw new \RuntimeException('策略绑定的爬虫目标不存在: ' . $strategy->crawl_target_id);
        }
        // 通知绑定的是不可变快照版本；即便目标已经开始下一轮爬取，也必须按通知版本读取。
        $version = $version ?? (int) ($target->version ?? 0);

        // 从 price_strategy_product 取该策略绑定的所有产品 ID
        $boundProductIds = PriceStrategyProduct::where('price_strategy_id', $strategy->id)
            ->column('game_product_id');
        if (empty($boundProductIds)) {
            throw new \RuntimeException('策略未绑定任何产品: strategyId=' . $strategy->id);
        }

        $dimension = $this->firstDimension($strategy->config);

        $products = GameProduct::with(['gameAccount'])
            ->whereIn('id', $boundProductIds)
            ->select();
        if (count($products) === 0) {
            throw new \RuntimeException('策略绑定的产品均不存在: strategyId=' . $strategy->id);
        }

        // 竞品数据按爬虫目标+版本查，所有绑定产品共用同一批竞品
        $competitors = CrawlData::where('target_id', $strategy->crawl_target_id)
            ->where('version', $version)
            ->select();

        foreach ($products as $product) {
            $heartbeat && $heartbeat();
            $stat['total']++;
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
            $heartbeat && $heartbeat();
        }

        $strategy->last_run_at = date('Y-m-d H:i:s');
        $strategy->save();

        return $stat;
    }

    /**
     * 处理单个产品：算目标价 -> 竞价幅度 -> 上限夹逼 -> 应用改价。
     * 「最低价」只在筛选竞品阶段生效（过滤价格≤该值的竞品），不参与出价计算。
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
            $filterPrice = $this->numOrNull($dimension['filter_price'] ?? null);
            if ($filterPrice !== null) {
                $msg .= '；已排除价格小于等于' . $filterPrice . '的竞品';
            }
            $minStock = $this->normalizeMinStock($dimension['min_stock'] ?? 0);
            if ($minStock > 0) {
                $msg .= '；已排除库存低于' . $minStock . '的竞品（无库存数据也排除）';
            }
            $minRating = $this->normalizeMinRating($dimension['min_rating'] ?? 0);
            if ($minRating > 0) {
                $msg .= '；已排除好评率低于' . $minRating . '或无效的竞品';
            }
            return [PriceStrategyLog::STATUS_SKIP, $current, 0.0, $msg, null];
        }

        $lowest = (float) $lowestInfo['price'];
        $competitorId = (int) $lowestInfo['id'];
        $competitorContext = sprintf(
            '参考竞品ID=%d，seller_id=%s，seller_name=%s，库存=%s，好评率=%s',
            $competitorId,
            $lowestInfo['seller_id'] !== '' ? $lowestInfo['seller_id'] : '--',
            $lowestInfo['seller_name'] !== '' ? $lowestInfo['seller_name'] : '--',
            $lowestInfo['stock_num'] === null ? '--' : (string) $lowestInfo['stock_num'],
            $lowestInfo['rating'] === null ? '--' : (string) $lowestInfo['rating']
        );
        $minStock = $this->normalizeMinStock($dimension['min_stock'] ?? 0);
        if ($minStock > 0) {
            $competitorContext .= '，库存要求≥' . $minStock;
        }
        $minRating = $this->normalizeMinRating($dimension['min_rating'] ?? 0);
        if ($minRating > 0) {
            $competitorContext .= '，好评率要求≥' . $minRating;
        }
        $filterPrice = $this->numOrNull($dimension['filter_price'] ?? null);
        if ($filterPrice !== null) {
            $competitorContext .= '，最低价=' . $filterPrice . '（已排除价格≤此值的竞品）';
        }

        // 2. 竞价幅度：算出我们的出价（过滤价不参与出价计算）
        $bid = $this->applyBid($lowest, $dimension);

        // 3. 价格上限（可选）
        $ceiling = $this->numOrNull($dimension['ceiling_price'] ?? null);
        if ($ceiling !== null && $bid > $ceiling) {
            $bid = $ceiling;
        }

        // 4. 取整
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

        // 5. 应用改价（按产品关联账号的平台路由到对应客户端）
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
     * 过滤优先级：黑名单剔除 > 白名单强制纳入(跳过库存/好评率过滤) > 其余按库存、好评率过滤。
     * 「最低价」独立生效：价格小于等于该值的竞品一律不参与最低价计算（白名单也不例外）。
     * 库存优先使用原始 stock 解析，stock_num 作为已规范化数据的回退值；rating 缺失或非法时，
     * 在启用 min_rating 的情况下视为不满足。店铺标识同时匹配 crawl_data 的 seller_id 与 seller_name。
     *
     * @return array{price:float,id:int,seller_id:string,seller_name:string,stock_num:int|null,rating:float|null}|null
     */
    protected function calcLowest(GameProduct $product, $competitors, array $dimension): ?array
    {
        $blacklist = $this->normalizeStoreIdentifiers($dimension['blacklist_stores'] ?? []);
        $whitelist = $this->normalizeStoreIdentifiers($dimension['whitelist_stores'] ?? []);
        $filterPrice = $this->numOrNull($dimension['filter_price'] ?? null);
        $minStock  = $this->normalizeMinStock($dimension['min_stock'] ?? 0);
        $minRating = $this->normalizeMinRating($dimension['min_rating'] ?? 0);
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

            $stockNum = $this->parseStockNumber($c->stock ?? null, $c->stock_num ?? null);
            $rating = $this->parseRating($c->rating ?? null);
            $isWhitelisted = $whitelist && array_intersect($ids, $whitelist);

            // 白名单保持兼容：命中后跳过库存/好评率过滤，但仍受黑名单和价格门槛限制。
            if (!$isWhitelisted) {
                // 库存低于阈值，或库存无法解析时，不参与最低价计算。
                if ($minStock > 0 && ($stockNum === null || $stockNum < $minStock)) {
                    continue;
                }
                // 好评率低于阈值、缺失或非法时，不参与最低价计算。
                if ($minRating > 0 && ($rating === null || $rating < $minRating)) {
                    continue;
                }
            }

            // 最低价：价格小于等于门槛的竞品不参与最低价计算，只有严格大于门槛才是候选。
            if ($filterPrice !== null && $price <= $filterPrice) {
                continue;
            }

            $candidate = [
                'price'      => $price,
                'id'         => (int) $c->id,
                'seller_id'  => trim((string) $c->seller_id),
                'seller_name'=> trim((string) $c->seller_name),
                'stock_num'  => $stockNum,
                'rating'     => $rating,
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
     * 解析竞品库存：无单位为最小单位，K/M/G 分别乘以 10^3/10^6/10^9。
     * 优先解析原始 stock；原始值为空或无法解析时，回退到已规范化的 stock_num。
     */
    protected function parseStockNumber($stock, $stockNum = null): ?int
    {
        $stockText = trim((string) $stock);
        if ($stockText !== '') {
            $parsed = $this->parseAbbreviatedNumber($stockText);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        if ($stockNum === null || $stockNum === '') {
            return null;
        }
        if (is_numeric($stockNum)) {
            $number = (float) $stockNum;
            return $number >= 0 ? (int) $number : null;
        }
        return $this->parseAbbreviatedNumber((string) $stockNum);
    }

    /**
     * 解析库存缩写数字：例如 8880、8.88K、2.5M、1.2G。
     */
    protected function parseAbbreviatedNumber(string $value): ?int
    {
        $value = preg_replace('/\\s+/u', '', trim($value)) ?? trim($value);
        $value = str_replace(',', '', $value);
        if ($value === '' || !preg_match('/^(\\d+(?:\\.\\d+)?|\\.\\d+)([kKmMgGbB]?)$/', $value, $matches)) {
            return null;
        }

        $number = (float) $matches[1];
        switch (strtolower($matches[2])) {
            case 'k':
                $number *= 1_000;
                break;
            case 'm':
                $number *= 1_000_000;
                break;
            case 'g':
            case 'b':
                $number *= 1_000_000_000;
                break;
        }
        if (!is_finite($number) || $number < 0 || $number > PHP_INT_MAX) {
            return null;
        }
        return (int) $number;
    }

    /**
     * 解析好评率，支持 96、96.00、96.00% 三种常见格式；非法或超出 0-100 返回 null。
     */
    protected function parseRating($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $text = trim((string) $value);
        $text = str_replace('%', '', $text);
        $text = preg_replace('/\\s+/u', '', $text) ?? $text;
        if ($text === '' || !preg_match('/^(\\d+(?:\\.\\d+)?|\\.\\d+)$/', $text)) {
            return null;
        }
        $rating = (float) $text;
        return is_finite($rating) && $rating >= 0 && $rating <= 100 ? $rating : null;
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

        $dimension = $dimensions[0] ?? [];
        // 兼容早期直接把黑白名单、库存、好评率或最低价放在 config 顶层的旧数据。
        if (!$dimension && (
            array_key_exists('blacklist_stores', $config)
            || array_key_exists('whitelist_stores', $config)
            || array_key_exists('filter_price', $config)
            || array_key_exists('price', $config)
            || array_key_exists('minimum_price', $config)
            || array_key_exists('min_stock', $config)
            || array_key_exists('min_rating', $config)
        )) {
            $dimension = $config;
        }
        if (is_string($dimension)) {
            $decoded = json_decode($dimension, true);
            $dimension = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        $dimension = is_array($dimension) ? $dimension : [];
        // 最低价统一读 filter_price；旧数据的 price/minimum_price/floor_price 一并视为最低价。
        $filterPrice = $this->numOrNull(
            $dimension['filter_price']
            ?? $dimension['price']
            ?? $dimension['minimum_price']
            ?? $dimension['floor_price']
            ?? null
        );

        $dimension = array_merge([
            'type'             => PriceStrategy::DIMENSION_LOWEST,
            'blacklist_stores' => [],
            'whitelist_stores' => [],
            'filter_price'     => null,
            'min_stock'        => 0,
            'min_rating'       => 0,
            'ceiling_price'    => null,
            'bid_mode'         => PriceStrategy::BID_AMOUNT,
            'amplitude'        => 0,
            'round_precision'  => 4,
        ], $dimension);
        $dimension['filter_price'] = $filterPrice;
        // 最低价只做竞品过滤，不再作为出价下限。
        unset($dimension['price'], $dimension['minimum_price'], $dimension['floor_price']);
        $dimension['blacklist_stores'] = $this->normalizeStoreIdentifiers($dimension['blacklist_stores']);
        $dimension['whitelist_stores'] = $this->normalizeStoreIdentifiers($dimension['whitelist_stores']);
        $dimension['min_stock'] = $this->normalizeMinStock($dimension['min_stock']);
        $dimension['min_rating'] = $this->normalizeMinRating($dimension['min_rating']);
        return $dimension;
    }

    /**
     * 规范化策略库存阈值：配置只接受无单位的非负整数。
     * 竞品原始 stock 的 K/M/G 换算由 parseStockNumber() 单独处理。
     */
    protected function normalizeMinStock($value): int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return 0;
        }
        $number = (float) $value;
        if (!is_finite($number) || $number < 0) {
            return 0;
        }
        return (int) floor($number);
    }

    /**
     * 规范化策略好评率：限制在 0-100，并保留两位小数；配置不使用百分号。
     */
    protected function normalizeMinRating($value): float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return 0.0;
        }
        $number = (float) $value;
        if (!is_finite($number)) {
            return 0.0;
        }
        return round(min(100.0, max(0.0, $number)), 2);
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
