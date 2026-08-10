<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\service\PriceStrategyService;
use think\console\Input;
use think\console\Output;

/**
 * 定时改价命令
 *
 * 按各策略配置的「改价频率(interval_minutes)」执行到期的启用策略。
 * 建议用 crontab 每分钟触发（命令内部判断是否到期）：
 *   * * * * * cd /path/to/base_admin && php think price:strategy:run >> runtime/price_strategy.log 2>&1
 */
class PriceStrategyRun extends Base
{
    protected function configure(): void
    {
        $this->setName('price:strategy:run')
            ->setDescription('按改价频率执行到期的改价策略');
    }

    protected function execute(Input $input, Output $output): void
    {
        try {
            $count = (new PriceStrategyService)->runDue();
            $output->writeln('[' . date('Y-m-d H:i:s') . '] 执行到期策略数: ' . $count);
        } catch (\Throwable $e) {
            $output->writeln('[' . date('Y-m-d H:i:s') . '] 执行异常: ' . $e->getMessage());
        }
    }
}
