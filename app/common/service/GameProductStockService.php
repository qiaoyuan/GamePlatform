<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\GameProduct;

/**
 * ELD 库存同步。
 *
 * ELD 没有独立的库存修改请求，需要复用整单修改接口，
 * 用新库存替换 offer_data.details.pricing.quantity，其余字段原样提交。
 */
class GameProductStockService
{
    /**
     * 同步 ELD 线上库存，成功后更新本地 offer_data。
     *
     * @throws \RuntimeException 库存非法、offer_data 不完整或平台调用失败时抛出
     */
    public static function sync(GameProduct $product, int $stock): void
    {
        if ($stock <= 0) {
            throw new \RuntimeException('ELD库存必须大于0');
        }
        if (!$product->gameAccount) {
            throw new \RuntimeException('该产品未关联有效的游戏账号');
        }

        $offerData = $product->offer_data;
        if (!is_array($offerData) || !$offerData) {
            throw new \RuntimeException('该产品尚未同步线上数据，请先点「同步线上数据」再修改库存');
        }

        $offerData['details']['pricing']['quantity'] = $stock;

        // 整单接口同时要求价格；库存编辑时保持当前价格不变。
        $client = new EldoradoClient($product->gameAccount);
        $client->updateOfferPrice(
            (string) $product->product_id,
            $offerData,
            (float) $product->price,
            (int) $product->id
        );

        $product->stock = $stock;
        $product->offer_data = $offerData;
        $product->save();
    }
}
