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
 *   Eldorado  → EldoradoClient::updatePriceWithFallback()（A → B → C，C 依赖 offer_data）
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
                // 优先 A/B，仅降级到 C 整单提交时校验 offer_data。
                $offerData = $product->offer_data;
                $client = new EldoradoClient($account);
                $usedInterface = null;
                $client->updatePriceWithFallback($product->product_id, is_array($offerData) ? $offerData : [], $price, $product->id, $usedInterface);
                if ($usedInterface === 'C') {
                    // C 改价完成后，以重新查询的线上价格为准写回本地。
                    try {
                        $detail = $client->getOfferDetail((string) $product->product_id, (int) $product->id);
                        $onlinePrice = $detail['offer']['pricePerUnit'] ?? [];
                        $amount = $onlinePrice['amount'] ?? null;
                        $currency = $onlinePrice['currency'] ?? '';
                        if (!is_numeric($amount) || !is_finite((float) $amount)
                            || (float) $amount <= 0 || !is_string($currency) || $currency === '') {
                            throw new \RuntimeException('线上响应缺少有效价格或币种');
                        }
                        $product->price = (float) $amount;
                        $product->currency = $currency;
                        $offerData['details']['pricing']['pricePerUnit'] = [
                            'amount' => (float) $amount,
                            'currency' => $currency,
                        ];
                        $product->offer_data = $offerData;
                        $product->save();
                    } catch (\Throwable $e) {
                        throw new \RuntimeException('ELD C接口已改价，但同步线上价格到本地失败：' . $e->getMessage(), 0, $e);
                    }
                    return;
                }
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
