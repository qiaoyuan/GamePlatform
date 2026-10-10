<?php
declare(strict_types=1);
namespace test\service;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\model\CrawlData;
use app\common\model\GameProduct;
use app\common\model\PriceStrategyLog;
use app\common\service\PriceStrategyService;
use PHPUnit\Framework\TestCase;

final class PriceStrategyMinimumFallbackTest extends TestCase
{
    private function row(float $price, array $extra = []): CrawlData
    {
        return new CrawlData(array_merge([
            'id' => 1, 'price' => $price, 'currency' => 'USD',
            'seller_id' => 'shop', 'seller_name' => 'Shop', 'stock' => '1K', 'rating' => '99%',
        ], $extra));
    }

    private function runPrice(float $current, array $rows, array $dimension): array
    {
        $service = new class extends PriceStrategyService {
            public array $prices = [];
            public function run(GameProduct $product, array $rows, array $dimension): array
            {
                return $this->handleProduct($product, $rows, $this->firstDimension(['dimensions' => [$dimension]]));
            }
            protected function applyProductPrice(GameProduct $product, float $price): void
            {
                $this->prices[] = $price;
            }
        };
        $result = $service->run(new GameProduct(['price' => $current, 'currency' => 'USD', 'product_id' => 'offer']), $rows, $dimension);
        return [$result, $service->prices];
    }

    public function testAllPricesAtOrBelowMinimumUseMinimumWithoutPositiveOrNegativeOffset(): void
    {
        foreach ([0.1, -0.1] as $offset) {
            [$result, $prices] = $this->runPrice(1.0, [$this->row(0.7), $this->row(0.8)], ['filter_price' => 0.8, 'amplitude' => $offset]);
            self::assertSame(PriceStrategyLog::STATUS_SUCCESS, $result[0]);
            self::assertSame([0.8], $prices);
            self::assertSame(0.8, $result[2]);
            self::assertNull($result[4]);
            self::assertStringContainsString('忽略偏移值', $result[3]);
        }
    }

    public function testAlreadyAtMinimumSkipsApi(): void
    {
        [$result, $prices] = $this->runPrice(0.8, [$this->row(0.7)], ['filter_price' => 0.8, 'amplitude' => 0.1]);
        self::assertSame(PriceStrategyLog::STATUS_SKIP, $result[0]);
        self::assertSame([], $prices);
    }

    public function testOtherFiltersAndMissingCompetitorsDoNotTriggerFallback(): void
    {
        foreach ([
            [[], []],
            [[$this->row(0.7, ['currency' => 'EUR'])], []],
            [[$this->row(0.7)], ['blacklist_stores' => ['shop']]],
            [[$this->row(0.7)], ['min_stock' => 2000]],
            [[$this->row(0.7, ['rating' => 80])], ['min_rating' => 95]],
        ] as [$rows, $extra]) {
            [$result, $prices] = $this->runPrice(1.0, $rows, $extra + ['filter_price' => 0.8, 'amplitude' => 0.1]);
            self::assertSame(PriceStrategyLog::STATUS_SKIP, $result[0]);
            self::assertSame([], $prices);
        }
    }

    public function testFallbackPreservesConfiguredMinimumPrecision(): void
    {
        [$result, $prices] = $this->runPrice(0.00048, [$this->row(0.0004)], [
            'filter_price' => 0.000479, 'round_precision' => 4, 'amplitude' => 0.1,
        ]);
        self::assertSame(PriceStrategyLog::STATUS_SUCCESS, $result[0]);
        self::assertSame([0.000479], $prices);
    }

    public function testNormalEligibleCompetitorStillUsesOffset(): void
    {
        [$result, $prices] = $this->runPrice(1.2, [$this->row(1.0)], ['filter_price' => 0.8, 'amplitude' => 0.1]);
        self::assertSame([0.9], $prices);
        self::assertSame(1, $result[4]);
    }

    public function testMissingMinimumStillSkipsAndConflictingCeilingDoesNotBidBelowMinimum(): void
    {
        foreach ([['min_stock' => 2000], ['filter_price' => 0.8, 'ceiling_price' => 0.6]] as $dimension) {
            [$result, $prices] = $this->runPrice(1.0, [$this->row(0.7)], $dimension);
            self::assertSame(PriceStrategyLog::STATUS_SKIP, $result[0]);
            self::assertSame([], $prices);
        }
    }
}
