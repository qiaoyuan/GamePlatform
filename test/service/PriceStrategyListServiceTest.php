<?php
declare(strict_types=1);

namespace test\service;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\service\PriceStrategyListService;
use app\common\model\CrawlData;
use PHPUnit\Framework\TestCase;
use think\Container;
use think\DbManager;
use think\facade\Db;
use think\Model;

/** 独立内存库，不读取项目的线上连接配置。 */
final class PriceStrategyListServiceTest extends TestCase
{
    private Container $previousContainer;
    private DbManager $previousDb;
    private PriceStrategyListService $service;

    protected function setUp(): void
    {
        $this->previousContainer = Container::getInstance();
        $this->previousDb = $this->previousContainer->make(DbManager::class);
        $container = new Container();
        Container::setInstance($container);
        $db = new DbManager();
        $db->setConfig([
            'default' => 'sqlite',
            'connections' => ['sqlite' => ['type' => 'sqlite', 'database' => ':memory:', 'prefix' => '']],
        ]);
        $container->instance(DbManager::class, $db);
        Db::execute('CREATE TABLE price_strategy (id INTEGER PRIMARY KEY, sort INTEGER, deleted_at TEXT)');
        Db::execute('CREATE TABLE price_strategy_log (id INTEGER PRIMARY KEY, price_strategy_id INTEGER, game_product_id INTEGER, status INTEGER, new_price TEXT)');
        foreach (range(1, 6) as $id) {
            Db::table('price_strategy')->insert(['id' => $id]);
        }
        $this->service = new PriceStrategyListService();
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);
        Model::setDb($this->previousDb);
    }

    private function order(): array
    {
        return array_map('intval', Db::table('price_strategy')->whereNull('deleted_at')
            ->orderRaw(PriceStrategyListService::ORDER)->column('id'));
    }

    public function testSortPersistsInBothDirectionsAndNewStrategyIsFirst(): void
    {
        self::assertSame([6, 5, 4, 3, 2, 1], $this->order());
        $this->service->reorder(Db::table('price_strategy'), [6, 5, 4], [4, 6, 5]);
        self::assertSame([4, 6, 5, 3, 2, 1], $this->order());
        $this->service->reorder(Db::table('price_strategy'), [4, 6, 5], [6, 5, 4]);
        self::assertSame([6, 5, 4, 3, 2, 1], $this->order());
        Db::table('price_strategy')->insert(['id' => 7]);
        self::assertSame([7, 6, 5, 4, 3, 2, 1], $this->order());
    }

    public function testFilteredRowsOnlyExchangeTheirOwnPositions(): void
    {
        $this->service->reorder(Db::table('price_strategy'), [5, 3, 1], [1, 5, 3]);
        self::assertSame([6, 1, 4, 5, 2, 3], $this->order());
        self::assertNull(Db::table('price_strategy')->where('id', 4)->value('sort'));
    }

    public function testStaleOrderDoesNotOverwriteSavedOrder(): void
    {
        $this->service->reorder(Db::table('price_strategy'), [6, 5, 4], [4, 6, 5]);
        try {
            $this->service->reorder(Db::table('price_strategy'), [6, 5, 4], [5, 4, 6]);
            self::fail('旧页面排序应被拒绝');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('顺序已变更', $e->getMessage());
        }
        self::assertSame([4, 6, 5, 3, 2, 1], $this->order());
    }

    public function testOutOfScopeAndDeletedRecordsCannotBeReordered(): void
    {
        foreach ([Db::table('price_strategy')->where('id', '<', 6), Db::table('price_strategy')] as $query) {
            if (!$query->getOptions('where')) {
                Db::table('price_strategy')->where('id', 6)->update(['deleted_at' => '2026-09-20']);
            }
            try {
                $this->service->reorder($query, [6, 5], [5, 6]);
                self::fail('不应修改不可见记录');
            } catch (\InvalidArgumentException $e) {
                self::assertStringContainsString('不存在或无权', $e->getMessage());
            }
        }
        self::assertNull(Db::table('price_strategy')->where('id', 5)->value('sort'));
    }

    public function testInvalidIdListsAreRejectedWithoutWrites(): void
    {
        foreach ([[[6, 5], [5, 5]], [[6, 5], [5, 4]], [[6], [6]], [[6, 'bad'], [6, 'bad']]] as [$before, $after]) {
            try {
                $this->service->reorder(Db::table('price_strategy'), $before, $after);
                self::fail('应拒绝无效排序参数');
            } catch (\InvalidArgumentException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
        self::assertSame([6, 5, 4, 3, 2, 1], $this->order());
    }

    public function testPriceUsesLatestSuccessfulLogAndRespectsDataScope(): void
    {
        Db::table('price_strategy_log')->insertAll([
            ['id' => 1, 'price_strategy_id' => 6, 'game_product_id' => 10, 'status' => 1, 'new_price' => '0.01000000'],
            ['id' => 2, 'price_strategy_id' => 6, 'game_product_id' => 10, 'status' => 1, 'new_price' => '0.00000001'],
            ['id' => 3, 'price_strategy_id' => 6, 'game_product_id' => 10, 'status' => 2, 'new_price' => '99.00'],
            ['id' => 4, 'price_strategy_id' => 6, 'game_product_id' => 10, 'status' => 0, 'new_price' => '88.00'],
            ['id' => 5, 'price_strategy_id' => 5, 'game_product_id' => 10, 'status' => 0, 'new_price' => '77.00'],
            ['id' => 6, 'price_strategy_id' => 6, 'game_product_id' => 11, 'status' => 1, 'new_price' => '22.00'],
        ]);
        self::assertSame([6 => '0.00000001'], $this->service->latestPrices(
            Db::table('price_strategy_log')->where('game_product_id', 10), [6, 5, 4]
        ));
        self::assertSame([6 => '22.00'], $this->service->latestPrices(Db::table('price_strategy_log'), [6]));
        self::assertSame([], $this->service->latestPrices(Db::table('price_strategy_log'), []));
    }

    public function testCompetitorMessagesUseLatestSnapshotBoundCurrencyAndStrategyFilters(): void
    {
        Db::execute('CREATE TABLE crawl_data (id INTEGER PRIMARY KEY, target_id INTEGER, version INTEGER, price REAL,
            currency TEXT, seller_id TEXT, seller_name TEXT, stock TEXT, stock_num INTEGER, rating TEXT)');
        Db::execute('CREATE TABLE game_product (id INTEGER PRIMARY KEY, currency TEXT, deleted_at TEXT)');
        Db::execute('CREATE TABLE price_strategy_product (price_strategy_id INTEGER, game_product_id INTEGER)');
        Db::table('game_product')->insertAll([['id' => 1, 'currency' => 'USD'], ['id' => 2, 'currency' => 'EUR']]);
        Db::table('price_strategy_product')->insertAll([
            ['price_strategy_id' => 6, 'game_product_id' => 1],
            ['price_strategy_id' => 5, 'game_product_id' => 2],
        ]);
        foreach ([[1, 1, 0.1, 'USD', '1000'], [2, 2, 0.2, 'USD', '100'], [3, 2, 0.7, 'USD', '1K'],
            [4, 2, 0.9, 'EUR', '1K'], [5, 3, 0.3, 'USD', '1K']] as [$id, $version, $price, $currency, $stock]) {
            Db::table('crawl_data')->insert([
                'id' => $id, 'target_id' => 29, 'version' => $version, 'price' => $price,
                'currency' => $currency, 'seller_id' => 'shop-' . $id, 'seller_name' => '店铺' . $id,
                'stock' => $stock, 'stock_num' => 1000, 'rating' => '99',
            ]);
        }
        $strategies = [];
        foreach ([6, 5, 4] as $id) {
            $strategies[] = (object) [
                'id' => $id, 'crawl_target_id' => 29,
                'crawlTarget' => (object) ['version' => 2, 'deleted_at' => null],
                'config' => ['dimensions' => [['min_stock' => 101, 'filter_price' => 0.77]]],
            ];
        }
        $messages = $this->service->competitorMessages($strategies, CrawlData::where('target_id', 29), Db::table('game_product'));
        self::assertSame(['msg' => '0.7 USD · 店铺3', 'msg_color' => '#F56C6C'], $messages[6]);
        self::assertSame(['msg' => '0.9 EUR · 店铺4', 'msg_color' => ''], $messages[5]);
        self::assertSame('未绑定产品', $messages[4]['msg']);
        $scoped = $this->service->competitorMessages($strategies,
            CrawlData::where('id', '<>', 3), Db::table('game_product')->where('game_product.id', 1));
        self::assertSame('暂无符合条件的竞品', $scoped[6]['msg']);
        self::assertSame('未绑定产品', $scoped[5]['msg']);
        // 所有策略共享目标，模拟最新轮没有数据时不回退旧版本。
        foreach ($strategies as $strategy) {
            $strategy->crawlTarget->version = 4;
        }
        $empty = $this->service->competitorMessages($strategies, CrawlData::where('target_id', 29), Db::table('game_product'));
        self::assertSame('暂无符合条件的竞品', $empty[6]['msg']);
        self::assertSame('', $empty[6]['msg_color']);
        self::assertSame([], $this->service->competitorMessages([], CrawlData::where('target_id', 29), Db::table('game_product')));
    }
}
