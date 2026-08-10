<?php
declare(strict_types=1);

namespace app\common\service;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * G2G 分类页 DOM 解析器
 *
 * G2G 分类页是服务端渲染(SSR)的，原始 HTML 里就含全部 Product Card，
 * 因此可用 PHP 直接 HTTP 抓取 + DOM 解析，无需无头浏览器。
 *
 * 用法：
 *   $html  = (new G2gPageParser)->fetch($url);
 *   $items = (new G2gPageParser)->parse($html);   // 每个元素对应一个 Product Card
 */
class G2gPageParser
{
    /**
     * 直接抓取页面 HTML
     */
    public function fetch(string $url): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => ['Accept-Language: zh-CN,zh;q=0.9,en;q=0.8'],
        ]);
        $html = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($html === false || $code !== 200) {
            throw new \RuntimeException("抓取失败: HTTP {$code} {$err}");
        }
        return (string) $html;
    }

    /**
     * 解析 HTML 里的所有 Product Card
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $cards = $xpath->query('//*[@aria-label="Product Card"]');
        $items = [];
        foreach ($cards as $card) {
            /** @var DOMElement $card */
            $items[] = $this->parseCard($xpath, $card);
        }
        return $items;
    }

    private function parseCard(DOMXPath $xpath, DOMElement $card): array
    {
        // 价格：加粗数字 span
        $priceNode = $xpath->query(".//span[contains(@class,'text-base') and contains(@class,'font-bold')]", $card)->item(0);
        $priceText = $priceNode ? trim($priceNode->textContent) : '';
        // 币种：价格 span 后面的兄弟 span
        $currency = '';
        if ($priceNode) {
            $cur = $xpath->query("following-sibling::span[1]", $priceNode)->item(0);
            $currency = $cur ? trim($cur->textContent) : '';
        }

        // 标题
        $titleNode = $xpath->query(".//span[contains(@class,'line-clamp-2')]", $card)->item(0);
        $title = $titleNode ? trim($titleNode->textContent) : '';

        // offer 链接
        $offerNode = $xpath->query(".//a[contains(@href,'/offer/')]/@href", $card)->item(0);
        $offerUrl = $offerNode ? trim($offerNode->nodeValue) : '';

        // 卖家名
        $sellerNameNode = $xpath->query(".//div[contains(@class,'truncate') and contains(@class,'text-xs') and contains(@class,'font-medium')]", $card)->item(0);
        $sellerName = $sellerNameNode ? trim($sellerNameNode->textContent) : '';

        // 卖家主页链接 -> seller_id（排除 offer / categories 链接）
        $sellerId = '';
        $sellerUrl = '';
        $links = $xpath->query(".//a[contains(@href,'.g2g.com/')]/@href", $card);
        foreach ($links as $href) {
            $url = trim($href->nodeValue);
            if (strpos($url, '/offer/') !== false || strpos($url, '/categories/') !== false) {
                continue;
            }
            if (preg_match('#g2g\.com/([A-Za-z0-9_-]+)$#', $url, $m)) {
                $sellerId = $m[1];
                $sellerUrl = $url;
                break;
            }
        }

        // 卖家等级：形如「等级 140」
        $sellerLevel = '';
        $levelNode = $xpath->query(".//span[starts-with(normalize-space(.),'等级')]", $card)->item(0);
        if ($levelNode) {
            $sellerLevel = trim($levelNode->textContent);
        }

        // 是否在线
        $isOnline = $xpath->query(".//div[contains(@class,'online-indicator')]", $card)->length > 0 ? 1 : 0;

        // 好评率 + 各类 chip（已售/最小起订/库存/交货时间）
        $rating = null;
        $soldCount = '';
        $minOrder = '';
        $stock = '';
        $deliveryTime = '';
        $chips = $xpath->query(".//div[contains(@class,'h-chip__content')]", $card);
        foreach ($chips as $chip) {
            $t = trim(preg_replace('/\s+/', ' ', $chip->textContent));
            if ($t === '') {
                continue;
            }
            // chip 文本可能拼接了 tooltip（形如「最小 3,000 最低购买量：3000」），先去掉 tooltip 部分
            $t = $this->stripTooltip($t);
            if (preg_match('/(\d+(?:\.\d+)?)%/', $t, $m)) {
                $rating = (float) $m[1];
            } elseif (mb_strpos($t, '已售') !== false || stripos($t, 'sold') !== false) {
                $soldCount = $t;
            } elseif (mb_strpos($t, '最小') !== false || stripos($t, 'min') === 0) {
                $minOrder = $t;
            } elseif (preg_match('/(分钟|小时|min|hour|hr)/i', $t)) {
                $deliveryTime = $t;
            } elseif (preg_match('/^([\d.,]+[kKmMbB]?)\b/', $t, $m)) {
                $stock = $m[1];
            }
        }

        return [
            'seller_id'      => $sellerId,
            'seller_name'    => $sellerName ?: $sellerId,
            'seller_level'   => $sellerLevel,
            'seller_url'     => $sellerUrl,
            'is_online'      => $isOnline,
            'product_title'  => $title,
            'offer_url'      => $offerUrl,
            'rating'         => $rating,
            'sold_count'     => $soldCount,
            'sold_count_num' => $this->parseInt($soldCount),
            'min_order'      => $minOrder,
            'stock'          => $stock,
            'stock_num'      => $this->parseAbbrNum($stock),
            'delivery_time'  => $deliveryTime,
            'price'          => $priceText === '' ? null : (float) $priceText,
            'currency'       => $currency,
        ];
    }

    /** 去掉 chip 里拼接的 tooltip 文本（形如「可见文本 提示标签：提示值」，提示部分含全角冒号） */
    private function stripTooltip(string $text): string
    {
        return trim(preg_replace('/\s*\S*：.*$/u', '', $text));
    }

    /** 从「17,232 已售出」提取 17232 */
    private function parseInt(string $text): ?int
    {
        if ($text === '' || !preg_match('/([\d,]+)/', $text, $m)) {
            return null;
        }
        return (int) str_replace(',', '', $m[1]);
    }

    /** 解析缩写数字：119k -> 119000, 5.1M -> 5100000, 450.4M -> 450400000 */
    private function parseAbbrNum(string $text): ?int
    {
        $text = trim(str_replace(',', '', $text));
        if ($text === '' || !preg_match('/^([\d.]+)([kKmMbB]?)$/', $text, $m)) {
            return null;
        }
        $num = (float) $m[1];
        switch (strtolower($m[2])) {
            case 'k': $num *= 1_000; break;
            case 'm': $num *= 1_000_000; break;
            case 'b': $num *= 1_000_000_000; break;
        }
        return (int) $num;
    }
}
