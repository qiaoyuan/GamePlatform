<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\GameAccount;
use app\common\model\GameProduct;

/**
 * 游戏产品改价（内部共享逻辑）
 *
 * 把「调用平台改价接口 + 同步本地价格」这一段核心逻辑收敛到一处，
 * 供 GameProduct 控制器与 PriceStrategyService 复用。
 *
 * 平台路由：
 *   G2G       → G2gClient::updatePrice()
 *   Eldorado  → EldoradoClient::updateOfferPrice()（整单提交，依赖已同步的 offer_data）
 */
class GameProductPriceService
{
    /**
     * 改价：按账号平台路由到对应客户端，成功后同步更新本地价格。
     *
     * @param GameProduct $product 需已加载 gameAccount 关联
     * @param float       $price   新价格
     * @throws \RuntimeException 参数非法 / 无有效账号 / 平台改价失败时抛出，调用方需捕获
     */
    public static function change(GameProduct $product, float $price): void
    {
        if ($price <= 0) {
            throw new \RuntimeException('价格必须大于0');
        }
        if (!$product->gameAccount) {
            throw new \RuntimeException('该产品未关联有效的游戏账号');
        }

        $account = $product->gameAccount;

        switch ($account->platform) {
            case GameAccount::PLATFORM_ELDORADO:
                // ELD 改价走整单提交（POST /me/offers）：除价格外的参数全部来自
                // 「同步线上数据」写入的 offer_data，所以必须先同步过才能改价。
                $offerData = $product->offer_data;
                if (!is_array($offerData) || !$offerData) {
                    throw new \RuntimeException('该产品尚未同步线上数据，请先点「同步线上数据」再改价');
                }
                $client = new EldoradoClient($account);
                $client->updateOfferPrice($product->product_id, $offerData, $price, $product->id);
                break;

            case GameAccount::PLATFORM_G2G:
            default:
                $client = new G2gClient($account);
                $client->updatePrice($product->product_id, $price, $product->id);
                break;
        }

        $product->price = $price;
        $product->save();
    }
}
