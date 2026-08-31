<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\GameAccount;
use app\common\model\GameProduct;

/**
 * 同步线上平台 offer 数据（目前仅 Eldorado 支持，G2G 无此接口）
 *
 * 拉取 GET /api/v1/currency-management/me/offers/{offerId}，从原始响应里裁出业务需要的字段，
 * 以精简 JSON 写入 game_product.offer_data，同时把价格/库存/币种同步成线上现值。
 *
 * offer_data 结构（固定形状，字段缺失时给零值，便于下游直接取值）：
 * {
 *   "details": {
 *     "description": "...",
 *     "guaranteedDeliveryTime": "Hour1",
 *     "pricing": {
 *       "quantity": 40000,
 *       "minQuantity": 1000,
 *       "volumeDiscounts": [],
 *       "pricePerUnit": {"amount": 1.0, "currency": "USD"}
 *     },
 *     "deliveryMethod": "MailTrade"
 *   },
 *   "augmentedGame": {
 *     "gameId": "92",
 *     "category": "Currency",
 *     "tradeEnvironmentId": "6-6-1",
 *     "offerAttributes": []
 *   }
 * }
 */
class GameProductOfferSyncService
{
    /**
     * 同步单个产品的线上数据。
     *
     * @param GameProduct $product 需已加载 gameAccount 关联
     * @return array 写入 offer_data 的精简数据
     * @throws \RuntimeException 非 ELD 平台 / 无有效账号 / 平台接口失败时抛出
     */
    public static function sync(GameProduct $product): array
    {
        if (!$product->gameAccount) {
            throw new \RuntimeException('该产品未关联有效的游戏账号');
        }
        $account = $product->gameAccount;
        if ((int) $account->platform !== GameAccount::PLATFORM_ELDORADO) {
            throw new \RuntimeException('仅 Eldorado 平台支持同步线上数据');
        }
        if ((string) $product->product_id === '') {
            throw new \RuntimeException('该产品未填写平台产品ID，无法同步');
        }

        $client = new EldoradoClient($account);
        $detail = $client->getOfferDetail((string) $product->product_id, (int) $product->id);

        $offer = $detail['offer'] ?? [];
        if (!is_array($offer) || !$offer) {
            throw new \RuntimeException('线上响应中没有 offer 数据');
        }

        $offerData = self::buildOfferData($offer);

        // 同步线上现值：单价、库存、币种（价格只落本地，不回调平台改价接口）
        $pricePerUnit = $offerData['details']['pricing']['pricePerUnit'];
        if ($pricePerUnit['amount'] > 0) {
            $product->price = $pricePerUnit['amount'];
        }
        if ($pricePerUnit['currency'] !== '') {
            $product->currency = $pricePerUnit['currency'];
        }
        $product->stock     = $offerData['details']['pricing']['quantity'];
        $product->offer_data = $offerData;
        $product->save();

        return $offerData;
    }

    /**
     * 从平台原始 offer 里裁出需要的字段。
     */
    protected static function buildOfferData(array $offer): array
    {
        $pricePerUnit = is_array($offer['pricePerUnit'] ?? null) ? $offer['pricePerUnit'] : [];

        return [
            'details' => [
                'description'            => (string) ($offer['description'] ?? ''),
                'guaranteedDeliveryTime' => (string) ($offer['guaranteedDeliveryTime'] ?? ''),
                'pricing'                => [
                    'quantity'        => (int) ($offer['quantity'] ?? 0),
                    'minQuantity'     => (int) ($offer['minQuantity'] ?? 0),
                    'volumeDiscounts' => is_array($offer['volumeDiscounts'] ?? null) ? $offer['volumeDiscounts'] : [],
                    'pricePerUnit'    => [
                        'amount'   => (float) ($pricePerUnit['amount'] ?? 0),
                        'currency' => (string) ($pricePerUnit['currency'] ?? ''),
                    ],
                ],
                'deliveryMethod'         => (string) ($offer['deliveryMethod'] ?? ''),
            ],
            'augmentedGame' => [
                'gameId'             => (string) ($offer['gameId'] ?? ''),
                'category'           => (string) ($offer['category'] ?? ''),
                'tradeEnvironmentId' => self::pickTradeEnvironmentId($offer['tradeEnvironmentValues'] ?? []),
                'offerAttributes'    => self::pickOfferAttributes($offer),
            ],
        ];
    }

    /**
     * 取最具体的交易环境ID。
     *
     * tradeEnvironmentValues 是逐级细化的层级结构（Region "6" → Realm "6-6" → Faction "6-6-1"），
     * 需要的是最末级那个（对应 Horde / Wild Growth / NA 这套组合），按 '-' 段数最多者取，
     * 段数相同时取靠后的一条。
     *
     * @param mixed $values
     */
    protected static function pickTradeEnvironmentId($values): string
    {
        if (!is_array($values)) {
            return '';
        }
        $best      = '';
        $bestDepth = -1;
        foreach ($values as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = trim((string) ($item['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $depth = substr_count($id, '-');
            if ($depth >= $bestDepth) {
                $bestDepth = $depth;
                $best      = $id;
            }
        }
        return $best;
    }

    /**
     * 取 offer 属性，直接产出改价接口可提交的结构（已用真实商品验证 HTTP 201）：
     *   {"value":"divine-orb","name":"Orbs","id":"path-of-exile-2-orbs",
     *    "type":"Select","display":"NameWithValue"}
     *
     * 与 GET 响应的唯一差别：GET 的 attributes[].value 是对象
     *   {"name":"Divine Orb","id":"divine-orb","imageLocation":"Divine-Orb.png"}
     * 提交时必须降级成字符串 value.id（"divine-orb"），直接回传对象会被平台以
     * "invalid values: value" 拒绝。外层 name/id/type/display 原样保留，
     * 其中 id 是属性定义 id（path-of-exile-2-orbs），别和值 id 混淆。
     *
     * 无属性的商品（如魔兽金币）attributes 为空，这里返回空数组，
     * 改价时提交 "offerAttributes": [] —— 与线上一致，不会误清配置。
     */
    protected static function pickOfferAttributes(array $offer): array
    {
        // offerAttributeIdValues 非空时，视为平台已给出可提交结构，原样采用
        if (!empty($offer['offerAttributeIdValues']) && is_array($offer['offerAttributeIdValues'])) {
            return $offer['offerAttributeIdValues'];
        }

        $attributes = $offer['attributes'] ?? null;
        if (!is_array($attributes)) {
            return [];
        }

        $result = [];
        foreach ($attributes as $attr) {
            if (!is_array($attr)) {
                continue;
            }
            $value = $attr['value'] ?? null;
            // 单选(Select)：value 是对象，取其 id；平台若已给字符串则原样用。
            // 多选类型暂无真实样本，value 为列表时取第一项 id 兜底。
            if (is_array($value)) {
                $valueId = isset($value['id'])
                    ? trim((string) $value['id'])
                    : trim((string) ($value[0]['id'] ?? ''));
            } else {
                $valueId = trim((string) $value);
            }
            if ($valueId === '') {
                continue;
            }
            $result[] = [
                'value'   => $valueId,
                'name'    => (string) ($attr['name'] ?? ''),
                'id'      => (string) ($attr['id'] ?? ''),
                'type'    => (string) ($attr['type'] ?? ''),
                'display' => (string) ($attr['display'] ?? ''),
            ];
        }
        return $result;
    }
}
