<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\PriceStrategyLog;
use app\common\model\GameProduct;
use think\db\Query;
use think\facade\Db;

/** 仅用于后台列表，不影响 Worker 的执行顺序。 */
class PriceStrategyListService
{
    public const ORDER = 'COALESCE(price_strategy.sort, price_strategy.id) DESC, price_strategy.id DESC';

    /** 批量读取当前页最新版本的竞品，再按每套策略的配置筛选；不调用平台 API。 */
    public function competitorMessages($strategies, Query $ownedCompetitors, Query $ownedProducts): array
    {
        $versions = [];
        $ids = [];
        foreach ($strategies as $strategy) {
            $ids[] = (int) $strategy->id;
            if ($strategy->crawlTarget && !$strategy->crawlTarget->deleted_at) {
                $versions[(int) $strategy->crawl_target_id] = (int) $strategy->crawlTarget->version;
            }
        }
        if (!$ids || !$versions) {
            return [];
        }
        $competitors = $ownedCompetitors->where(function (Query $query) use ($versions): void {
            foreach ($versions as $targetId => $version) {
                $query->whereOr(function (Query $snapshot) use ($targetId, $version): void {
                    $snapshot->where('target_id', $targetId)->where('version', $version);
                });
            }
        })->select();
        $byTarget = [];
        foreach ($competitors as $competitor) {
            $byTarget[(int) $competitor->target_id][] = $competitor;
        }
        $currencies = [];
        $bindings = $ownedProducts->join('price_strategy_product binding', 'binding.game_product_id = game_product.id')
            ->whereIn('binding.price_strategy_id', $ids)->whereNull('game_product.deleted_at')
            ->field(['binding.price_strategy_id', 'game_product.currency'])->select();
        foreach ($bindings as $binding) {
            $currencies[(int) $binding['price_strategy_id']][$binding['currency'] ?: GameProduct::DEFAULT_CURRENCY] = true;
        }
        $engine = new PriceStrategyService();
        $messages = [];
        foreach ($strategies as $strategy) {
            $candidates = [];
            foreach (array_keys($currencies[$strategy->id] ?? []) as $currency) {
                $candidates = array_merge($candidates, $engine->previewLowestThree(
                    new GameProduct(['currency' => $currency]),
                    $byTarget[$strategy->crawl_target_id] ?? [],
                    $strategy->config
                ));
            }
            // 不跨币种比较价格；多币种先分组，整个单元格最多三行。
            usort($candidates, static fn (array $a, array $b): int =>
                strcmp($a['currency'], $b['currency']) ?: ($a['price'] <=> $b['price']) ?: ($a['id'] <=> $b['id']));
            $lines = [];
            foreach (array_slice($candidates, 0, 3) as $lowest) {
                $price = rtrim(rtrim(number_format($lowest['price'], 8, '.', ''), '0'), '.');
                $shop = $lowest['seller_name'] ?: ($lowest['seller_id'] ?: '未知店铺');
                $lines[] = [
                    'id' => $lowest['id'],
                    'price' => $price,
                    'currency' => $lowest['currency'],
                    'shop' => $shop,
                    'text' => $price . ' ' . $lowest['currency'] . ' · ' . $shop,
                    'below_minimum' => $lowest['below_minimum'],
                ];
            }
            $messages[$strategy->id] = [
                'msg' => $lines ? implode("\n", array_column($lines, 'text')) : (isset($currencies[$strategy->id]) ? '暂无符合条件的竞品' : '未绑定产品'),
                'msg_lines' => $lines,
            ];
        }
        return $messages;
    }

    /** 在原有排序位置间交换权重，分页或筛选之外的记录保持不变。 */
    public function reorder(Query $ownedQuery, array $before, array $after): void
    {
        $before = $this->normalizeIds($before);
        $after = $this->normalizeIds($after);
        if (count($before) < 2 || count($before) !== count($after)
            || array_diff($before, $after) || array_diff($after, $before)) {
            throw new \InvalidArgumentException('排序数据不完整，请刷新列表后重试');
        }

        Db::transaction(function () use ($ownedQuery, $before, $after): void {
            // 固定加锁顺序；锁内校验旧顺序，避免两个浏览器覆盖彼此的排序。
            $rows = $ownedQuery->whereNull('deleted_at')->whereIn('id', $before)
                ->field(['id', 'sort'])->order('id')->lock(true)->select()->toArray();
            if (count($rows) !== count($before)) {
                throw new \InvalidArgumentException('策略不存在或无权排序，请刷新列表');
            }
            usort($rows, static fn (array $a, array $b): int =>
                ($b['sort'] ?? $b['id']) <=> ($a['sort'] ?? $a['id']));
            if (array_map('intval', array_column($rows, 'id')) !== $before) {
                throw new \InvalidArgumentException('列表顺序已变更，请刷新后重新拖动');
            }
            foreach ($after as $index => $id) {
                Db::table('price_strategy')->where('id', $id)
                    ->update(['sort' => $rows[$index]['sort'] ?? $rows[$index]['id']]);
            }
        });
    }

    private function normalizeIds(array $ids): array
    {
        foreach ($ids as $id) {
            if ((!is_int($id) && !is_string($id)) || !ctype_digit((string) $id) || (int) $id <= 0) {
                throw new \InvalidArgumentException('无效的策略 ID');
            }
        }
        $ids = array_map('intval', array_values($ids));
        if (count($ids) !== count(array_unique($ids))) {
            throw new \InvalidArgumentException('策略 ID 不可重复');
        }
        return $ids;
    }

    /** 每个策略最近一次成功日志的改价后价格；失败和跳过的拟定价格不算。 */
    public function latestPrices(Query $ownedLogs, array $strategyIds): array
    {
        if (!$strategyIds) {
            return [];
        }
        $latestIds = $ownedLogs->whereIn('price_strategy_id', $strategyIds)
            ->where('status', PriceStrategyLog::STATUS_SUCCESS)
            ->group('price_strategy_id')->column('MAX(id)');
        if (!$latestIds) {
            return [];
        }
        // 不经过 float 转换，保留数据库中的 8 位小数精度。
        return Db::table('price_strategy_log')->whereIn('id', $latestIds)
            ->column('new_price', 'price_strategy_id');
    }
}
