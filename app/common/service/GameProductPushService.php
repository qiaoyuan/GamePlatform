<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\GameAccount;
use app\common\model\GameProduct;
use think\facade\Log;

/** 从现有 ERP 产品资料向 Eldorado 提交 offer，不新增本地产品行。 */
class GameProductPushService
{
    /** @return array{product_id:string,created:bool} */
    public static function push(GameProduct $product): array
    {
        if (!$product->gameAccount || (int) $product->gameAccount->platform !== GameAccount::PLATFORM_ELDORADO) {
            throw new \RuntimeException('仅 Eldorado 产品支持推送平台');
        }
        $offerId = trim((string) $product->product_id);
        if ($offerId === '') {
            throw new \RuntimeException('ERP 平台 product_id 为空，无法按产品隔离请求；请先填写原平台 ID');
        }
        $store = cache()->store('redis');
        $redis = $store->handler();
        // 与策略/手动改价共用锁，防止推送期间另一进程删除或替换 offer ID。
        $lockKey = $store->getCacheKey('eld_offer_lock_v2_' . $offerId);
        $token = bin2hex(random_bytes(16));
        if (!$redis->set($lockKey, $token, ['nx', 'ex' => 180])) {
            throw new \RuntimeException('该产品正在改价或推送，请稍后再试');
        }

        try {
            $current = GameProduct::with(['gameAccount'])->find($product->id);
            if (!$current) {
                throw new \RuntimeException('产品不存在');
            }
            $offerData = is_array($current->offer_data) ? $current->offer_data : [];
            if (!$offerData) {
                throw new \RuntimeException('ERP 缺少线上产品资料，无法推送；请先补全 offer_data');
            }
            if ((float) $current->price <= 0 || (int) $current->stock <= 0) {
                throw new \RuntimeException('价格和库存必须大于 0，无法推送');
            }
            $offerData['details']['pricing']['quantity'] = (int) $current->stock;
            $offerData['details']['pricing']['pricePerUnit']['amount'] = (float) $current->price;
            $offerData['details']['pricing']['pricePerUnit']['currency'] = (string) $current->currency;
            $client = new EldoradoClient($current->gameAccount);
            $oldId = trim((string) $current->product_id);
            if ($oldId !== $offerId) {
                throw new \RuntimeException('产品平台 ID 已变化，请重新加载后再推送');
            }
            $missing = false;
            try {
                $client->getOfferDetail($oldId, (int) $current->id);
            } catch (\RuntimeException $e) {
                if ($e->getCode() !== 404) {
                    throw $e; // 401/429/超时不能误判成已删除并新建
                }
                $missing = true;
            }

            // 此 POST 由平台按 offer 属性创建或更新；不执行 DELETE。
            try {
                $result = $client->updateOfferPrice($oldId, $offerData, (float) $current->price, (int) $current->id);
            } catch (\RuntimeException $e) {
                if ($e->getCode() === 0 && $e->getPrevious() instanceof \GuzzleHttp\Exception\GuzzleException) {
                    throw new \RuntimeException('平台请求结果不确定，请先核查线上产品，避免重复创建：' . $e->getMessage(), 0, $e);
                }
                throw $e;
            }
            $offer = $result['offer'] ?? null;
            $newId = is_array($offer) ? trim((string) ($offer['id'] ?? '')) : '';
            if ($newId === '') {
                throw new \RuntimeException('平台已响应但未返回 offer ID，请人工核查，勿重复推送');
            }
            $created = (int) ($result['offersCreatedCount'] ?? 0) > 0;
            $updated = (int) ($result['offersUpdatedCount'] ?? 0) > 0;
            if (!$created && !$updated) {
                throw new \RuntimeException('平台未确认创建或更新 offer，请人工核查，返回 ID=' . $newId);
            }
            if (!$missing && $created) {
                throw new \RuntimeException('原 offer 仍存在，但平台又创建了新 offer；可能出现重复产品，请人工核查。原 ID='
                    . $oldId . '，新 ID=' . $newId);
            }
            if (!$missing && $newId !== $oldId && !$created) {
                throw new \RuntimeException('平台返回了不同的 offer ID 且未确认创建，请人工核查，返回 ID=' . $newId);
            }
            $actualPrice = $offer['pricePerUnit']['amount'] ?? null;
            if (!is_numeric($actualPrice) || abs((float) $actualPrice - (float) $current->price) > 0.000001) {
                throw new \RuntimeException('平台响应价格与 ERP 价格不一致，请人工核查，返回 ID=' . $newId);
            }
            $current->product_id = $newId;
            $current->offer_data = $offerData;
            try {
                if (!$current->save()) {
                    throw new \RuntimeException('数据库未保存产品记录');
                }
            } catch (\Throwable $e) {
                Log::error('[GameProductPushService] 推送成功但 ERP 保存失败 productId=' . $current->id
                    . ' oldId=' . $oldId . ' newId=' . $newId . ': ' . $e->getMessage());
                throw new \RuntimeException('平台已接受推送，但 ERP 保存新 ID 失败；请人工同步，新 ID=' . $newId, 0, $e);
            }
            return ['product_id' => $newId, 'created' => $created];
        } finally {
            try {
                $redis->eval(
                    'if redis.call("get", KEYS[1]) == ARGV[1] then return redis.call("del", KEYS[1]) else return 0 end',
                    [$lockKey, $token],
                    1
                );
            } catch (\Throwable $e) {
                Log::warning('[GameProductPushService] 产品锁释放失败 productId=' . $product->id . ': ' . $e->getMessage());
            }
        }
    }
}
