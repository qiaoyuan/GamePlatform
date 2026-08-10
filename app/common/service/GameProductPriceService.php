<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\GameProduct;

/**
 * 游戏产品改价（内部共享逻辑）
 *
 * 把「调用 G2G 改价接口 + 同步本地价格」这一段核心逻辑收敛到一处，
 * 供 GameProduct::updatePrice() 控制器与 PriceStrategyService 复用，
 * 保证手动改价与策略自动改价走的是完全相同的逻辑（而非 HTTP 互调）。
 */
class GameProductPriceService
{
    /**
     * 改价：调用 G2G 平台接口，成功后同步更新本地价格。
     *
     * @param GameProduct $product 需已加载 gameAccount 关联
     * @param float       $price   新价格
     * @throws \RuntimeException 参数非法 / 无有效账号 / G2G 改价失败时抛出，调用方需捕获
     */
    public static function change(GameProduct $product, float $price): void
    {
        if ($price <= 0) {
            throw new \RuntimeException('价格必须大于0');
        }
        if (!$product->gameAccount) {
            throw new \RuntimeException('该产品未关联有效的游戏账号');
        }

        $client = new G2gClient($product->gameAccount);
        $client->updatePrice($product->product_id, $price, $product->id);

        $product->price = $price;
        $product->save();
    }
}
