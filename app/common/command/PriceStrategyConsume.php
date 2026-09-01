<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\service\PriceStrategyService;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * 消费爬取完成通知 -> 执行改价策略（信号驱动的主入口）
 *
 * 架构：Python 爬完写 crawl_notify(status=0)；本命令取待处理通知，
 *       执行绑定该竞品池的启用策略，改价在 PHP 侧完成，然后标记通知已处理。
 *
 * 默认作为常驻 Worker 运行，由 Supervisor 负责启动和保活。
 */
class PriceStrategyConsume extends Base
{
    private bool $running = true;

    protected function configure(): void
    {
        $this->setName('price:strategy:consume')
            ->setDescription('常驻消费爬取完成通知并执行改价策略')
            ->addOption('once', null, Option::VALUE_NONE, '只领取并处理一条通知后退出')
            ->addOption('sleep', null, Option::VALUE_OPTIONAL, '空队列轮询间隔（秒）', '1')
            ->addOption('max-jobs', null, Option::VALUE_OPTIONAL, '处理多少条后主动退出，0表示不限', '1000')
            ->addOption('max-runtime', null, Option::VALUE_OPTIONAL, '运行多少秒后主动退出，0表示不限', '3600')
            ->addOption('stale-after', null, Option::VALUE_OPTIONAL, '多少秒无心跳后回收处理中任务', '900');
    }

    protected function execute(Input $input, Output $output): int
    {
        $sleepSeconds = max(0.1, (float) $input->getOption('sleep'));
        $maxJobs = max(0, (int) $input->getOption('max-jobs'));
        $maxRuntime = max(0, (int) $input->getOption('max-runtime'));
        $staleAfter = max(60, (int) $input->getOption('stale-after'));
        $once = (bool) $input->getOption('once');

        $this->registerSignals();
        $service = new PriceStrategyService();
        $workerId = $service->makeWorkerId();
        $startedAt = time();
        $processed = 0;
        $lastRecoveryAt = 0;
        $output->writeln(sprintf('[%s] Worker启动 id=%s', date('Y-m-d H:i:s'), $workerId));

        try {
            while ($this->running) {
                if (time() - $lastRecoveryAt >= 60) {
                    $recovered = $service->recoverStaleNotifies($staleAfter);
                    if ($recovered > 0) {
                        $output->writeln(sprintf('[%s] 回收失去心跳的通知%d条', date('Y-m-d H:i:s'), $recovered));
                    }
                    $lastRecoveryAt = time();
                }

                $result = $service->consumeOneNotify($workerId);
                if ($result !== null) {
                    $processed++;
                    $output->writeln(sprintf(
                        '[%s] 通知%d %s，尝试%d次，执行策略%d个',
                        date('Y-m-d H:i:s'),
                        $result['notify_id'],
                        $result['status'],
                        $result['attempts'],
                        $result['strategies']
                    ));
                } elseif ($once) {
                    break;
                } else {
                    usleep((int) round($sleepSeconds * 1_000_000));
                }

                if ($once
                    || ($maxJobs > 0 && $processed >= $maxJobs)
                    || ($maxRuntime > 0 && time() - $startedAt >= $maxRuntime)) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            $output->writeln('[' . date('Y-m-d H:i:s') . '] Worker异常退出: ' . $e->getMessage());
            return 1;
        }

        $output->writeln(sprintf('[%s] Worker退出，共处理%d条通知', date('Y-m-d H:i:s'), $processed));
        return 0;
    }

    private function registerSignals(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
        }
        pcntl_signal(SIGTERM, function (): void {
            $this->running = false;
        });
        pcntl_signal(SIGINT, function (): void {
            $this->running = false;
        });
    }
}
