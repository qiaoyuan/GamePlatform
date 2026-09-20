<?php
declare(strict_types=1);

namespace app\common\service;

/** Worker 已失去通知/目标的处理权，必须停止本轮平台操作。 */
class PriceStrategyLeaseLostException extends \RuntimeException
{
}
