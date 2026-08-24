<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Input;
use think\console\Output;
use think\facade\Db;

/**
 * 定时清理过期爬虫数据
 *
 * 清理范围（默认 7 天前，可通过参数覆盖）：
 *   - crawl_data        爬虫竞品原始数据
 *   - price_strategy_log 改价策略执行日志
 *   - crawl_notify       爬取完成通知
 *
 * 为避免长时间锁表，每次分批删除（默认每批 1000 条），批次间 sleep 10ms。
 *
 * 建议 crontab 每天凌晨 3 点执行：
 *   0 3 * * * cd /path/to/base_admin && php think data:clean >> runtime/data_clean.log 2>&1
 *
 * 也可手动指定保留天数（如只保留 3 天）：
 *   php think data:clean 3
 */
class DataClean extends Base
{
    /** 每批删除的行数，避免长事务锁表 */
    private const BATCH = 1000;

    /** 批次间隔（毫秒），减轻主库写压力 */
    private const SLEEP_MS = 10;

    protected function configure(): void
    {
        $this->setName('data:clean')
            ->setDescription('清理过期爬虫数据（crawl_data / price_strategy_log / crawl_notify）');
    }

    protected function execute(Input $input, Output $output): void
    {
        // 支持从命令行参数覆盖保留天数，不传则默认 7 天
        $days = (int) ($input->hasArgument('action') ? $input->getArgument('action') : 0);
        if ($days <= 0) {
            $days = 7;
        }

        $before = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $this->mLog("开始清理 {$before} 之前的数据（保留最近 {$days} 天）");

        $total = [
            'crawl_data'          => 0,
            'price_strategy_log'  => 0,
            'crawl_notify'        => 0,
        ];

        $tables = [
            // [表名, 时间字段, 额外条件]
            // crawl_notify 只清理已处理或处理失败的，待处理(status=0)的保留
            'crawl_data'         => ['created_at', []],
            'price_strategy_log' => ['created_at', []],
            'crawl_notify'       => ['created_at', [['status', 'in', [1, 2]]]],
        ];

        foreach ($tables as $table => [$timeField, $extraWhere]) {
            $deleted = $this->cleanTable($table, $timeField, $before, $extraWhere);
            $total[$table] = $deleted;
            $this->mLog("  {$table}: 删除 {$deleted} 条");
        }

        $sum = array_sum($total);
        $this->mLog("清理完成，共删除 {$sum} 条");
    }

    /**
     * 分批删除指定表中超过 $before 时间的记录。
     *
     * @param string $table      表名
     * @param string $timeField  时间字段
     * @param string $before     删除此时间点之前的数据
     * @param array  $extraWhere 额外的 where 条件（ThinkPHP 数组格式）
     * @return int 总共删除行数
     */
    private function cleanTable(string $table, string $timeField, string $before, array $extraWhere = []): int
    {
        $deleted = 0;

        while (true) {
            // 先查出一批要删的主键，再按主键删除，避免 LIMIT 和 DELETE 的组合问题
            $ids = Db::table($table)
                ->where($timeField, '<', $before)
                ->where($extraWhere)
                ->limit(self::BATCH)
                ->column('id');

            if (empty($ids)) {
                break;
            }

            $rows = Db::table($table)->whereIn('id', $ids)->delete();
            $deleted += $rows;

            // 批次间短暂休眠，降低主库写压力
            if (self::SLEEP_MS > 0) {
                usleep(self::SLEEP_MS * 1000);
            }
        }

        return $deleted;
    }
}
