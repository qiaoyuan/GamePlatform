<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\service\PriceStrategyService;
use think\console\Input;
use think\console\Output;

/**
 * 消费爬取完成通知 -> 执行改价策略（信号驱动的主入口）
 *
 * 架构：Python 爬完写 crawl_notify(status=0)；本命令取待处理通知，
 *       执行绑定该竞品池的启用策略，改价在 PHP 侧完成，然后标记通知已处理。
 *
 * 建议 crontab 每分钟触发：
 *   * * * * * cd /path/to/base_admin && php think price:strategy:consume >> runtime/price_strategy.log 2>&1
 */
class PriceStrategyConsume extends Base
{
    protected function configure(): void
    {
        $this->setName('price:strategy:consume')
            ->setDescription('消费爬取完成通知并执行改价策略');
    }

    protected function execute(Input $input, Output $output): void
    {
        try {
            $r = (new PriceStrategyService)->consumeNotify();
            $output->writeln('[' . date('Y-m-d H:i:s') . "] 处理通知 {$r['notifies']} 条, 执行策略 {$r['strategies']} 个");
        } catch (\Throwable $e) {
            $output->writeln('[' . date('Y-m-d H:i:s') . '] 执行异常: ' . $e->getMessage());
        }
    }
}
