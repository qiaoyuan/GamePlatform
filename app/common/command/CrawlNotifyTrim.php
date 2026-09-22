<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Input;
use think\console\Output;
use think\facade\Db;

/** 超过 50 条时跳过积压的待处理通知；每分钟由 cron 调用一次。 */
class CrawlNotifyTrim extends Base
{
    protected function configure(): void
    {
        $this->setName('crawl:notify:trim')
            ->setDescription('待处理爬取通知超过50条时，将本批待处理通知标记为已处理（跳过改价）');
    }

    protected function execute(Input $input, Output $output): int
    {
        try {
            // 同一次查询确定数量和边界，避免把检查后新产生的通知一起跳过。
            $snapshot = Db::table('crawl_notify')->where('status', 0)
                ->fieldRaw('COUNT(*) AS pending_count, MAX(id) AS max_id')->find();
            $count = (int) ($snapshot['pending_count'] ?? 0);
            if ($count <= 50) {
                $output->writeln(sprintf('[%s] 待处理%d条，未超过50条，无需清理', date('Y-m-d H:i:s'), $count));
                return 0;
            }

            $now = date('Y-m-d H:i:s');
            // 再次限定 status=0，保留已由 Worker 领取的 status=3 通知。
            $updated = Db::table('crawl_notify')->where('status', 0)
                ->where('id', '<=', (int) $snapshot['max_id'])
                ->update([
                    'status' => 1,
                    'message' => '积压自动清理：待处理通知超过50条，本通知已跳过，未执行改价',
                    'processed_at' => $now,
                    'updated_at' => $now,
                    'available_at' => null,
                    'started_at' => null,
                    'heartbeat_at' => null,
                    'worker_id' => '',
                ]);
            $output->writeln(sprintf('[%s] 检测到待处理%d条，已跳过%d条（标记status=1）', $now, $count, $updated));
            return 0;
        } catch (\Throwable $e) {
            $output->writeln(sprintf('[%s] 积压清理失败：%s', date('Y-m-d H:i:s'), $e->getMessage()));
            return 1;
        }
    }
}
