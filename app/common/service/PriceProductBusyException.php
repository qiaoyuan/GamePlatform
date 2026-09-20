<?php
declare(strict_types=1);

namespace app\common\service;

/** 尚未调用平台：产品被占用或计算价格时使用的产品快照已变化。 */
class PriceProductBusyException extends \RuntimeException
{
}
