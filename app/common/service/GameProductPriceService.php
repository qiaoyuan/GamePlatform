<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\GameAccount;
use app\common\model\GameProduct;
use think\facade\Log;

/**
 * 游戏产品改价（内部共享逻辑）
 *
 * 把「调用平台改价接口 + 同步本地价格」这一段核心逻辑收敛到一处，
 * 供 GameProduct 控制器与 PriceStrategyService 复用。
 *
 * 平台路由：
 *   G2G       → G2gClient::updatePrice()
 *   Eldorado  → A/B 改价；均冷却后删除旧 offer 并由 C 创建新 offer
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
                self::changeEldorado($product, $price);
                return;

            case GameAccount::PLATFORM_G2G:
            default:
                $client = new G2gClient($account);
                $client->updatePrice($product->product_id, $price, $product->id);
                break;
        }

        $product->price = $price;
        $product->save();
    }

    /** 手工改价与策略改价共用同一把产品锁，防止两个流程同时删除同一个 offer。 */
    private static function changeEldorado(GameProduct $product, float $price): void
    {
        $store = cache()->store('redis');
        $redis = $store->handler();
        $offerId = trim((string) $product->product_id);
        if ($offerId === '') {
            throw new \RuntimeException('ELD 平台 product_id 为空，无法改价');
        }
        $lockKey = $store->getCacheKey('eld_offer_lock_v2_' . $offerId);
        $lockToken = bin2hex(random_bytes(16));
        if (!$redis->set($lockKey, $lockToken, ['nx', 'ex' => 180])) {
            throw new \RuntimeException('该产品正在改价，请稍后再试');
        }

        try {
            // Worker 可能持有旧模型；旧 ID 若已被另一进程替换，不能再对它执行删除。
            $currentId = GameProduct::where('id', $product->id)->value('product_id');
            if ((string) $currentId !== (string) $product->product_id) {
                throw new \RuntimeException('产品平台 ID 已变化，请重新加载后再改价');
            }

            $offerData = is_array($product->offer_data) ? $product->offer_data : [];
            $client = new EldoradoClient($product->gameAccount);
            $usedInterface = null;
            $result = $client->updatePriceWithFallback(
                (string) $product->product_id,
                $offerData,
                $price,
                (int) $product->id,
                $usedInterface
            );

            if ($usedInterface === 'C') {
                $offer = $result['offer'];
                $newId = (string) $offer['id'];
                $responsePrice = $offer['pricePerUnit']['amount'] ?? null;
                $actualPrice = is_numeric($responsePrice) && (float) $responsePrice > 0
                    ? (float) $responsePrice : $price;
                $currency = (string) ($offer['pricePerUnit']['currency'] ?? '');
                if ($currency === '') {
                    $currency = (string) ($offerData['details']['pricing']['pricePerUnit']['currency'] ?? $product->currency);
                }
                $quantity = (int) ($offer['quantity'] ?? 0);
                if ($quantity <= 0) {
                    $quantity = (int) $product->stock;
                }
                // 本地行沿用原有记录，仅替换平台 ID 与新价格；保留已校验的建单资料。
                $offerData['details']['pricing']['pricePerUnit'] = [
                    'amount' => $actualPrice,
                    'currency' => $currency,
                ];
                $offerData['details']['pricing']['quantity'] = $quantity;
                $product->product_id = $newId;
                $product->price = $actualPrice;
                $product->currency = $currency;
                $product->stock = $quantity;
                $product->offer_data = $offerData;
                try {
                    if (!$product->save()) {
                        throw new \RuntimeException('数据库未保存产品记录');
                    }
                } catch (\Throwable $e) {
                    Log::error('[GameProductPriceService] ELD 新 offer 已创建但本地保存失败 productId='
                        . $product->id . ' oldId=' . $currentId . ' newId=' . $newId . ': ' . $e->getMessage());
                    throw new \RuntimeException('ELD新 offer 已创建，但本地产品ID保存失败；请人工同步。新ID=' . $newId, 0, $e);
                }
                if ((int) ($result['offersCreatedCount'] ?? 0) !== 1
                    || (int) ($result['offersUpdatedCount'] ?? 0) !== 0
                    || !is_numeric($responsePrice) || abs($actualPrice - $price) > 0.000001) {
                    throw new \RuntimeException('ELD新 offer ID 已保存，但创建计数或线上价格不符合预期；请核查。新ID=' . $newId);
                }
                return;
            }

            $product->price = $price;
            if (is_array($offerData) && $offerData) {
                $offerData['details']['pricing']['pricePerUnit']['amount'] = $price;
                $product->offer_data = $offerData;
            }
            $product->save();
        } finally {
            // 只有锁仍属于本次调用时才释放，避免长请求锁过期后误删别人的锁。
            try {
                $redis->eval(
                    'if redis.call("get", KEYS[1]) == ARGV[1] then return redis.call("del", KEYS[1]) else return 0 end',
                    [$lockKey, $lockToken],
                    1
                );
            } catch (\Throwable $e) {
                Log::warning('[GameProductPriceService] ELD 产品锁释放失败 productId='
                    . $product->id . ': ' . $e->getMessage());
            }
        }
    }
}
