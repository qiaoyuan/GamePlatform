<?php
declare(strict_types=1);

namespace test\service;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\model\CrawlData;
use app\common\model\GameProduct;
use app\common\service\PriceStrategyService;
use PHPUnit\Framework\TestCase;

final class PriceStrategyPreviewTest extends TestCase
{
    private function competitor(int $id, float $price, array $extra = []): CrawlData
    {
        return new CrawlData(array_merge([
            'id' => $id, 'price' => $price, 'currency' => 'USD',
            'seller_id' => 'shop-' . $id, 'seller_name' => '店铺' . $id,
            'stock' => '1K', 'stock_num' => 1000, 'rating' => '99%',
        ], $extra));
    }

    private function preview(array $competitors, array $dimension): ?array
    {
        return (new PriceStrategyService())->previewLowest(
            new GameProduct(['currency' => 'USD']), $competitors, ['dimensions' => [$dimension]]
        );
    }

    public function testStockRatingBlacklistAndCurrencyAreAppliedBeforeSelectingMinimum(): void
    {
        $result = $this->preview([
            $this->competitor(1, 0.1, ['stock' => '100', 'stock_num' => 9999]),
            $this->competitor(2, 0.2, ['rating' => '80%']),
            $this->competitor(3, 0.3, ['seller_name' => 'Blocked']),
            $this->competitor(4, 0.4, ['currency' => 'EUR']),
            $this->competitor(5, 0.7),
            $this->competitor(6, 0.8),
        ], ['min_stock' => 101, 'min_rating' => 95, 'blacklist_stores' => [' blocked '], 'filter_price' => 0.77]);
        self::assertSame(5, $result['id']);
        self::assertSame('店铺5', $result['seller_name']);
        self::assertTrue($result['below_minimum']);
    }

    public function testWhitelistAndBlacklistPriorityMatchWorker(): void
    {
        $rows = [$this->competitor(1, 0.5, ['stock' => '100', 'rating' => '0']), $this->competitor(2, 0.7)];
        $config = ['min_stock' => 1000, 'min_rating' => 95, 'whitelist_stores' => ['SHOP-1']];
        self::assertSame(1, $this->preview($rows, $config)['id']);
        $config['blacklist_stores'] = ['店铺1'];
        self::assertSame(2, $this->preview($rows, $config)['id']);
    }

    public function testThresholdBoundariesAndLegacyConfig(): void
    {
        $rows = [$this->competitor(1, 0.77)];
        self::assertTrue($this->preview($rows, ['filter_price' => 0.77])['below_minimum']);
        self::assertFalse($this->preview($rows, ['filter_price' => 0.7])['below_minimum']);
        self::assertFalse($this->preview($rows, [])['below_minimum']);
        $legacy = (new PriceStrategyService())->previewLowest(new GameProduct(['currency' => 'USD']), $rows,
            json_encode(['minimum_price' => 0.8, 'min_stock' => 101]));
        self::assertTrue($legacy['below_minimum']);
    }

    public function testNoEligibleCompetitorAndTiedPrices(): void
    {
        self::assertNull($this->preview([$this->competitor(1, 0.1, ['stock' => '100'])], ['min_stock' => 101]));
        self::assertNull($this->preview([], []));
        self::assertSame(1, $this->preview([$this->competitor(2, 0.7), $this->competitor(1, 0.7)], [])['id']);
    }

    public function testPreviewDoesNotChangeRealWorkerPriceGate(): void
    {
        $service = new class extends PriceStrategyService {
            public function actual(GameProduct $product, array $rows, array $config): ?array
            {
                return $this->calcLowest($product, $rows, $this->firstDimension($config));
            }
        };
        $product = new GameProduct(['currency' => 'USD']);
        $rows = [$this->competitor(1, 0.7), $this->competitor(2, 0.8)];
        $config = ['dimensions' => [['filter_price' => 0.77]]];
        self::assertSame(1, $service->previewLowest($product, $rows, $config)['id']);
        self::assertSame(2, $service->actual($product, $rows, $config)['id']);
    }

    public function testTopThreeRemainFilteredSortedAndIndividuallyFlagged(): void
    {
        $service = new PriceStrategyService();
        $product = new GameProduct(['currency' => 'USD']);
        $rows = [
            $this->competitor(9, 0.1, ['stock' => '100']),
            $this->competitor(4, 0.9), $this->competitor(3, 0.8),
            $this->competitor(2, 0.7), $this->competitor(1, 0.7),
        ];
        $result = $service->previewLowestThree($product, $rows, ['min_stock' => 101, 'filter_price' => 0.8]);
        self::assertSame([1, 2, 3], array_column($result, 'id'));
        self::assertSame([true, true, true], array_column($result, 'below_minimum'));
        self::assertCount(1, $service->previewLowestThree($product, [$rows[1]], []));
        self::assertSame([], $service->previewLowestThree($product, [], []));
    }
}
