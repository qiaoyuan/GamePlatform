<?php
declare(strict_types=1);

namespace test\service;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\service\EldoradoClient;
use PHPUnit\Framework\TestCase;

final class EldoradoRecreateTest extends TestCase
{
    private function offerData(): array
    {
        return [
            'details' => [
                'description' => 'Delivered within 10 minutes',
                'guaranteedDeliveryTime' => 'Minute20',
                'deliveryMethod' => 'InGameTrade',
                'pricing' => [
                    'quantity' => 214,
                    'minQuantity' => 1,
                    'volumeDiscounts' => [],
                    'pricePerUnit' => ['amount' => 12.50, 'currency' => 'USD'],
                ],
            ],
            'augmentedGame' => [
                'gameId' => '220',
                'category' => 'Currency',
                'tradeEnvironmentId' => '0',
                'offerAttributes' => [],
            ],
        ];
    }

    public function testAStillChangesPriceWithoutDeleting(): void
    {
        $client = new FakeEldoradoClient();
        $used = null;

        $client->updatePriceWithFallback('old-id', $this->offerData(), 12.25, 7, $used);

        self::assertSame('A', $used);
        self::assertSame(['A'], $client->events);
    }

    public function testCreatePayloadReplacesOldPriceWithRequestedPrice(): void
    {
        $client = new FakeEldoradoClient();

        $payload = $client->createPayload($this->offerData(), 12.25);

        self::assertSame(12.25, $payload['details']['pricing']['pricePerUnit']['amount']);
        self::assertSame('USD', $payload['details']['pricing']['pricePerUnit']['currency']);
        self::assertSame(214, $payload['details']['pricing']['quantity']);
    }

    public function testBothRateLimitedDeleteThenCreateAtNewPrice(): void
    {
        $client = new FakeEldoradoClient();
        $client->rateLimited = ['A', 'B'];
        $used = null;

        $result = $client->updatePriceWithFallback('old-id', $this->offerData(), 12.25, 7, $used);

        self::assertSame('C', $used);
        self::assertSame(['A', 'B', 'delete:old-id', 'create:12.25'], $client->events);
        self::assertSame('new-id', $result['offer']['id']);
        self::assertSame(12.25, $client->submittedPrice);
    }

    public function testAIsCooledThenBChangesPriceWithoutDeleting(): void
    {
        $client = new FakeEldoradoClient();
        $client->cooled = ['A'];
        $used = null;

        $client->updatePriceWithFallback('old-id', $this->offerData(), 12.25, 7, $used);

        self::assertSame('B', $used);
        self::assertSame(['B'], $client->events);
    }

    public function testInvalidPayloadNeverDeletesOldOffer(): void
    {
        $client = new FakeEldoradoClient();
        $client->cooled = ['A', 'B'];

        try {
            $used = null;
            $client->updatePriceWithFallback('old-id', [], 12.25, 7, $used);
            self::fail('缺少建单资料应在删除前失败');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('线上数据不完整', $e->getMessage());
            self::assertSame([], $client->events);
        }
    }

    public function testCooledCreateInterfaceNeverDeletesOldOffer(): void
    {
        $client = new FakeEldoradoClient();
        $client->cooled = ['A', 'B', 'C'];

        try {
            $used = null;
            $client->updatePriceWithFallback('old-id', $this->offerData(), 12.25, 7, $used);
            self::fail('C 冷却时不应删除');
        } catch (\RuntimeException $e) {
            self::assertSame(429, $e->getCode());
            self::assertSame([], $client->events);
        }
    }

    public function testMissingNewIdIsNotReportedAsSuccess(): void
    {
        $client = new FakeEldoradoClient();
        $client->cooled = ['A', 'B'];
        $client->createResult = ['offersCreatedCount' => 1, 'offersUpdatedCount' => 0, 'offer' => []];

        try {
            $used = null;
            $client->updatePriceWithFallback('old-id', $this->offerData(), 12.25, 7, $used);
            self::fail('没有新 ID 不应返回成功');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('没有新的 offer ID', $e->getMessage());
            self::assertSame(['delete:old-id', 'create:12.25'], $client->events);
            self::assertNull($used);
        }
    }

    public function testDeleteFailureNeverCallsCreate(): void
    {
        $client = new FakeEldoradoClient();
        $client->cooled = ['A', 'B'];
        $client->deleteFailure = true;

        try {
            $used = null;
            $client->updatePriceWithFallback('old-id', $this->offerData(), 12.25, 7, $used);
            self::fail('删除失败后不能创建');
        } catch (\RuntimeException $e) {
            self::assertSame(['delete:old-id'], $client->events);
            self::assertNull($client->submittedPrice);
            self::assertNull($used);
        }
    }
}

final class FakeEldoradoClient extends EldoradoClient
{
    public array $events = [];
    public array $cooled = [];
    public array $rateLimited = [];
    public bool $deleteFailure = false;
    public ?float $submittedPrice = null;
    public array $createResult = [
        'offersCreatedCount' => 1,
        'offersUpdatedCount' => 0,
        'offer' => ['id' => 'new-id', 'pricePerUnit' => ['amount' => 12.25, 'currency' => 'USD']],
    ];

    public function __construct()
    {
        // 所有网络与缓存方法均由本测试替身覆盖，不构建真实 HTTP 客户端。
    }

    public function createPayload(array $offerData, float $price): array
    {
        return $this->buildOfferPayload($offerData, $price);
    }

    protected function isOfferInterfaceCooled(string $interface, string $offerId): bool
    {
        return in_array($interface, $this->cooled, true);
    }

    public function updatePrice(string $offerId, float $price, int $gameProductId = 0, ?string $interface = null): array
    {
        $this->events[] = (string) $interface;
        if (in_array($interface, $this->rateLimited, true)) {
            throw new \RuntimeException('429', 429);
        }
        return ['ok' => true];
    }

    protected function deleteOffer(string $offerId, int $gameProductId): void
    {
        $this->events[] = 'delete:' . $offerId;
        if ($this->deleteFailure) {
            throw new \RuntimeException('删除失败');
        }
    }

    public function updateOfferPrice(string $offerId, array $offerData, float $price, int $gameProductId = 0): array
    {
        $this->submittedPrice = $price;
        $this->events[] = 'create:' . $price;
        return $this->createResult;
    }
}
