<?php
declare(strict_types=1);

namespace test\service;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\model\CrawlNotify;
use app\common\service\PriceStrategyLeaseLostException;
use app\common\service\PriceStrategyService;
use app\common\service\PriceStrategyTargetLease;
use PHPUnit\Framework\TestCase;
use think\Container;
use think\DbManager;
use think\facade\Db;
use think\Model;

/** 使用内存 SQLite 和显式指定的测试 Redis socket，不读取项目数据库或 Redis 配置。 */
final class PriceStrategyTargetLeaseTest extends TestCase
{
    private \Redis $redis;
    private string $prefix;
    private Container $previousContainer;
    private DbManager $previousDb;

    protected function setUp(): void
    {
        $socket = getenv('PRICE_STRATEGY_TEST_REDIS_SOCKET');
        if (!$socket || !extension_loaded('redis') || !extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('需要独立测试 Redis socket 和 pdo_sqlite');
        }
        $this->redis = new \Redis();
        $this->redis->connect($socket);
        $this->prefix = 'price_strategy_test_' . bin2hex(random_bytes(8)) . '_';
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
        Db::execute('CREATE TABLE crawl_notify (
            id INTEGER PRIMARY KEY, crawl_target_id INTEGER, version INTEGER,
            crawled_count INTEGER DEFAULT 0, status INTEGER DEFAULT 0, attempts INTEGER DEFAULT 0,
            available_at TEXT, started_at TEXT, heartbeat_at TEXT, worker_id TEXT DEFAULT "",
            dedupe_key TEXT UNIQUE, message TEXT, crawled_at TEXT, processed_at TEXT,
            created_at TEXT, updated_at TEXT
        )');
    }

    protected function tearDown(): void
    {
        if (isset($this->redis, $this->prefix)) {
            // 仅清理本测试两个明确命名的 key；不 flush 数据库。
            $this->redis->del($this->prefix . '24', $this->prefix . '25');
            $this->redis->close();
        }
        if (isset($this->previousContainer, $this->previousDb)) {
            Container::setInstance($this->previousContainer);
            Model::setDb($this->previousDb);
        }
    }

    private function worker(): LeaseTestConsumer
    {
        return new LeaseTestConsumer($this->redis, $this->prefix);
    }

    private function notify(int $id, int $target, ?string $availableAt = null): void
    {
        CrawlNotify::insert([
            'id' => $id, 'crawl_target_id' => $target, 'version' => $id,
            'available_at' => $availableAt, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function testTwoWorkersClaimDifferentTargetsAndLeaveBusyNotificationPending(): void
    {
        $this->notify(1, 24);
        $this->notify(2, 24);
        $this->notify(3, 25);
        $first = $this->worker();
        $second = $this->worker();
        $claimedBySecond = null;
        $first->duringRun = function () use ($second, &$claimedBySecond): void {
            $claimedBySecond = $second->claimNextNotify('worker-B');
        };

        $result = $first->consumeOneNotify('worker-A');

        self::assertSame('done', $result['status']);
        self::assertSame(1, $result['notify_id']);
        self::assertSame(3, $claimedBySecond->id);
        $waiting = CrawlNotify::find(2);
        self::assertSame(CrawlNotify::STATUS_PENDING, $waiting->status);
        self::assertSame(0, $waiting->attempts);
        self::assertSame(CrawlNotify::STATUS_PROCESSING, CrawlNotify::find(3)->status);
    }

    public function testCompletedNotificationReleasesTargetForNextWorker(): void
    {
        $this->notify(1, 24);
        $this->notify(2, 24);
        $this->worker()->consumeOneNotify('worker-A');

        $claimed = $this->worker()->claimNextNotify('worker-B');

        self::assertSame(2, $claimed->id);
        self::assertSame(1, $claimed->attempts);
    }

    public function testOlderDeferredVersionBlocksOnlyItsOwnTarget(): void
    {
        $this->notify(1, 24, date('Y-m-d H:i:s', time() + 60));
        $this->notify(2, 24);
        $this->notify(3, 25);

        $claimed = $this->worker()->claimNextNotify('worker-A');

        self::assertSame(3, $claimed->id);
        self::assertSame(0, CrawlNotify::find(1)->attempts);
        self::assertSame(0, CrawlNotify::find(2)->attempts);
        self::assertSame(CrawlNotify::STATUS_PENDING, CrawlNotify::find(2)->status);
    }

    public function testOldLeaseCannotDeleteOrRenewAnotherWorkersLease(): void
    {
        $key = $this->prefix . '24';
        $first = PriceStrategyTargetLease::acquire($this->redis, $key);
        self::assertNotNull($first);
        self::assertNull(PriceStrategyTargetLease::acquire($this->redis, $key));
        // 模拟旧 lease 过期后另一个 Worker 接手。
        $this->redis->del($key);
        $second = PriceStrategyTargetLease::acquire($this->redis, $key);
        $first->release();
        self::assertNotNull($second);
        self::assertNull(PriceStrategyTargetLease::acquire($this->redis, $key));
        try {
            $first->renew();
            self::fail('旧租约不应续租成功');
        } catch (PriceStrategyLeaseLostException $e) {
            $second->renew();
            self::assertGreaterThan(0, $this->redis->ttl($key));
        }
    }
}

final class LeaseTestConsumer extends PriceStrategyService
{
    public ?\Closure $duringRun = null;

    public function __construct(private \Redis $redis, private string $prefix)
    {
    }

    protected function acquireTargetLease(int $targetId): ?PriceStrategyTargetLease
    {
        return PriceStrategyTargetLease::acquire($this->redis, $this->prefix . $targetId);
    }

    public function runByCrawlTarget(int $crawlTargetId, ?int $version = null, ?callable $heartbeat = null): array
    {
        $heartbeat && $heartbeat();
        if ($this->duringRun !== null) {
            ($this->duringRun)();
        }
        return ['strategies' => 0, 'success' => 0, 'skip' => 0, 'fail' => 0, 'recreated' => []];
    }
}
