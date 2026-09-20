<?php
declare(strict_types=1);

namespace test\service;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\model\GameProduct;
use app\common\model\PriceStrategyLog;
use app\common\service\PriceProductBusyException;
use app\common\service\PriceStrategyLeaseLostException;
use app\common\service\PriceStrategyService;
use PHPUnit\Framework\TestCase;

final class PriceStrategyConcurrencyTest extends TestCase
{
    private function product(int $id = 34, string $offerId = 'old-offer', float $price = 12.5): GameProduct
    {
        return new GameProduct(['id' => $id, 'product_id' => $offerId, 'price' => $price, 'currency' => 'USD']);
    }

    public function testBusyProductReloadsNewOfferIdAndPriceBeforeApplying(): void
    {
        $service = new ContentionStrategy();
        $service->outcomes = [new PriceProductBusyException('busy'), null];
        $service->fresh = $this->product(34, 'new-offer', 11.0);
        $product = $this->product();
        $heartbeatCount = 0;
        $result = $service->handle($product, function () use (&$heartbeatCount): void { $heartbeatCount++; });

        self::assertSame(PriceStrategyLog::STATUS_SUCCESS, $result['result'][0]);
        self::assertSame(['old-offer', 'new-offer'], $service->attemptedIds);
        self::assertSame(['new-offer'], $service->completedIds);
        self::assertSame(11.0, $result['old_price']);
        self::assertSame('new-offer', $result['old_product_id']);
        self::assertStringNotContainsString('新增改价成功', $result['result'][3]);
        self::assertSame(1, $service->waits);
        self::assertGreaterThanOrEqual(2, $heartbeatCount);
    }

    public function testOtherWorkerAlreadySetDesiredPriceDoesNotCallApiAgain(): void
    {
        $service = new ContentionStrategy();
        $service->outcomes = [new PriceProductBusyException('busy')];
        $service->fresh = $this->product(34, 'new-offer', 10.0);
        $product = $this->product();
        $result = $service->handle($product);

        self::assertSame(PriceStrategyLog::STATUS_SKIP, $result['result'][0]);
        self::assertSame(['old-offer'], $service->attemptedIds);
        self::assertSame([], $service->completedIds);
    }

    public function testActualApiFailureIsNotRetried(): void
    {
        $service = new ContentionStrategy();
        $service->outcomes = [new \RuntimeException('API 429', 429)];
        $product = $this->product();
        $result = $service->handle($product);

        self::assertSame(PriceStrategyLog::STATUS_FAIL, $result['result'][0]);
        self::assertSame(0, $service->waits);
        self::assertCount(1, $service->attemptedIds);
    }

    public function testLossOfOwnershipStopsWithoutSendingApiRequest(): void
    {
        $service = new ContentionStrategy();
        $product = $this->product();
        try {
            $service->handle($product, null, static function (): void {
                throw new PriceStrategyLeaseLostException('lost');
            });
            self::fail('失去租约不能转换为普通产品失败后继续执行');
        } catch (PriceStrategyLeaseLostException $e) {
            self::assertSame([], $service->attemptedIds);
            self::assertSame(0, $service->waits);
        }
    }

    public function testWaitingForSecondProductDoesNotReplayFirstProduct(): void
    {
        $service = new ContentionStrategy();
        $service->outcomes = [null, new PriceProductBusyException('busy'), null];
        $first = $this->product(1, 'first');
        $second = $this->product(2, 'second');
        $service->fresh = $this->product(2, 'second-new');

        $service->handle($first);
        $service->handle($second);

        self::assertSame(['first', 'second-new'], $service->completedIds);
        self::assertSame(1, $service->waits);
    }
}

final class ContentionStrategy extends PriceStrategyService
{
    public array $outcomes = [];
    public array $attemptedIds = [];
    public array $completedIds = [];
    public ?GameProduct $fresh = null;
    public int $waits = 0;

    public function handle(GameProduct &$product, ?callable $heartbeat = null, ?callable $beforePriceChange = null): array
    {
        return $this->handleProductWithWait($product, [], ['bid_mode' => 'equal'], $beforePriceChange, $heartbeat);
    }

    protected function calcLowest(GameProduct $product, $competitors, array $dimension): ?array
    {
        return ['price' => 10.0, 'id' => 7, 'seller_id' => 'seller', 'seller_name' => 'Seller', 'stock_num' => 20, 'rating' => 99];
    }

    protected function applyProductPrice(GameProduct $product, float $price): void
    {
        $this->attemptedIds[] = (string) $product->product_id;
        $outcome = array_shift($this->outcomes);
        if ($outcome instanceof \Throwable) {
            throw $outcome;
        }
        $this->completedIds[] = (string) $product->product_id;
        $product->price = $price;
    }

    protected function waitForBusyProduct(): void
    {
        $this->waits++;
    }

    protected function reloadStrategyProduct(int $productId): ?GameProduct
    {
        return $this->fresh;
    }
}
