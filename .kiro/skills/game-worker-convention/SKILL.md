---
name: game-worker-convention
description: 游戏数据平台常驻 Worker 与数据库通知队列规范。当修改 price:strategy:consume、PriceStrategyService、CrawlNotify、crawl_notify 表、Python 爬虫通知生产，或部署和排查 Supervisor 时使用。
---

# 改价通知 Worker 规范

当前链路是：Python 每完成一个爬虫目标，写入带准确 `version` 的 `crawl_notify`；Supervisor 常驻运行一个 PHP Worker，领取通知并执行该目标绑定的改价策略。

参考实现：

- `app/common/command/PriceStrategyConsume.php`
- `app/common/service/PriceStrategyService.php`
- `app/common/model/CrawlNotify.php`
- `sql/crawl_notify.sql`
- `sql/alter_crawl_notify_add_worker_fields.sql`

## 队列状态与生产契约

`crawl_notify.status` 固定含义：

- `0`：待处理或等待重试
- `1`：已完成
- `2`：达到最大次数后的最终失败
- `3`：已领取、处理中

Python 生产通知时必须同时写入本次已完整保存的 `crawl_target_id`、`version` 和 `crawled_count`。PHP 必须按通知的 `version` 查询不可变的 `crawl_data` 快照，不得改为读取目标的最新版本。旧通知缺少版本的回退逻辑只用于迁移兼容，不应成为新生产者的默认行为。

### Top3 入库筛选

后台 `crawl_target.crawl_type` 为 `tinyint unsigned`：0 默认、1 Top3，Model 和接口使用整数，新增默认 0。独立 g2g 项目的 Python 生产者支持整数 0/1 和数字字符串 "0"/"1"，迁移期间兼容旧 default/top3 字符串；缺失或空值按 0 处理，其他值报错，不回退默认。默认类型保持现有入库行为，不查询改价策略。生产者实现位于独立 g2g 项目的 `g2g/crawl_filter.py`、`g2g/db.py::get_crawl_strategies()` 和 `tools/crawl_from_db.py::run()`；后台 `CrawlService` 是写 `competitor_product` 的旧手工路径，不能当作写 `crawl_data` 的生产者。

- 关联为 `crawl_target.id=price_strategy.crawl_target_id`，只读取启用且未删除策略，通过 `price_strategy_product` 取得未删除绑定产品的币种；未设置币种按 USD 处理。不根据目标自身产品币种替代各策略绑定产品币种。
- 与 PHP 实际改价的首维度规则对齐：只支持 `dimensions[0].type=lowest`（未设置时默认 lowest），兼容旧的顶层维度和 JSON 字符串维度，不把后续维度当作独立策略。
- 候选价格必须为有效正数且与绑定产品同币种。店铺同时匹配 `seller_id/seller_name`，统一大小写、HTML 实体和空白；黑名单优先，白名单只跳过库存/好评率，不能绕过价格门槛。
- 库存解析支持 K/M/G/B；库存或好评率缺失、非法且对应阈值启用时剔除。最低价要求候选价格严格大于门槛，字段优先级为 `filter_price → price → minimum_price → floor_price`，按首个非 null 值读取。`amplitude/bid_mode/round_precision` 是出价设置，不参与竞品过滤。
- 每策略/币种先过滤再取最低三条，价格相同时按原始抓取顺序选择。多策略取同一原始记录的去重合集，总条数可以超过三条；不按店铺合并报价，合并结果保留原始抓取顺序入库。
- 无绑定有效产品的启用策略时跳过目标；配置 JSON 无效或首维度类型不支持时报错。两者均发生在版本递增前，不更新版本、最后爬取时间或发送通知，不回退全量入库。
- 筛选成功后零条仍完成该版本，发送 `crawled_count=0` 的通知；其余通知条数必须等于实际保存条数。快照反映抓取时的配置，后续放宽条件需等新一轮抓取。

部署此字段时，未添加字段的库执行 `sql/alter_crawl_target_add_crawl_type.sql`；已有 varchar 字段的库暂停相关写入后执行 `sql/alter_crawl_target_crawl_type_to_tinyint.sql`，将 default/top3 转为 0/1，再修改列类型。两种迁移按现有 schema 二选一，不重复执行。后台、前端和每台 Python 生产者需统一数字枚举后再恢复爬虫，最后按需将目标切换为 1（Top3）。创建迁移文件不代表已执行迁移。验证覆盖 Python `tests/test_crawl_filter.py` 的过滤/查询/通知流程、PHP CrawlTarget 校验和前端 `test/frontend/crawlType.test.cjs`；此路径当前只减少写库量，不提前停止页面抓取。

## 防止重复消费的硬性约束

- 领取必须使用带 `id + status=pending + available_at` 条件的原子 UPDATE（compare-and-set），不能先 SELECT 后直接执行。
- 领取后写入唯一 `dedupe_key={crawl_target_id}:{version}`；重复通知只允许一条占用该键。
- 执行和最终状态更新必须同时校验 `status=processing` 与当前 `worker_id`，防止失去租约的进程覆盖新 Worker。
- `attempts` 在成功领取时递增，不在空轮询或领取竞争失败时递增。
- 当前实现区分产品结果与通知级异常：产品改价失败记入 `PriceStrategyLog` 和汇总 `fail`，整批处理完仍将通知置为 done，避免单个额度耗尽产品重跑整批。通知级异常（如租约丢失、日志写入失败）才经 `retryOrFailNotify()` 回到 pending 并设置 `available_at`，达到最大次数后进入 failed。不要把产品级失败无条件提升成整条通知重试。

### MySQL affected rows 陷阱

MySQL 默认返回实际发生变化的行数。同一秒内刷新相同的 `heartbeat_at/updated_at`，或重试时再次写入相同 `version/dedupe_key`，UPDATE 可能返回 `0`，但租约仍然有效。

因此：

- 状态发生确定变化的 CAS（pending→processing、processing→done）可以要求返回 1。
- 心跳和幂等字段等允许同值写入的 UPDATE 返回 0 时，必须重新 SELECT，核对 `id + status + worker_id` 及预期字段；不能直接判定“处理权丢失”。

## 心跳、崩溃恢复与幂等边界

- Worker 在每个策略和产品处理前后更新 `heartbeat_at`。
- 只有 `COALESCE(heartbeat_at, started_at)` 超过 `stale-after` 的 processing 通知才能回收为 pending；超时必须大于单次平台请求的最大合理耗时。
- 当前已有目标租约和共享产品锁，但部署仍从一个 Worker 开始；增加 `numprocs` 前验证多 Worker 领取、目标串行和平台限流，不能仅凭产品锁判断并发安全。
- 远程改价接口与本地数据库无法组成一个事务，严格 exactly-once 不可保证。重试前依赖“目标价与本地现价一致则跳过”降低重复调用风险；若平台支持幂等键，应优先传递稳定的业务幂等键。

## 常驻命令与 Supervisor

- 正式环境由 Supervisor 直接运行 `php think price:strategy:consume`，不再用计划任务启动 PHP 消费者。
- Python 爬虫调度仍可使用计划任务。
- 保持 `autorestart=true`、`stopasgroup=true`、`killasgroup=true`，并让 Worker 支持 SIGTERM/SIGINT 优雅退出。
- 低配服务器从 `numprocs=1`、`--sleep=2` 开始；通过 `max-jobs` 或 `max-runtime` 定期主动退出，由 Supervisor 重启以释放长期积累的内存。
- `scripts/price_strategy_execute.php --once` 只用于手工诊断，不得同时与常驻 Worker 周期运行。

## 部署与运维不变量

上线顺序：暂停爬虫与旧 PHP 计划任务 → 清完旧通知 → 执行 `alter_crawl_notify_add_worker_fields.sql` → 同时发布 PHP 与新版 Python 生产者 → 启动 Supervisor → 验证首条通知 → 恢复爬虫计划任务。

清理历史通知时只删除 `status IN (1, 2)` 且超过保留期的数据，不删除 pending 或 processing。排障优先检查：

```sql
SELECT status, COUNT(*) FROM crawl_notify GROUP BY status;
```

若出现 retry，查看通知 `message`、`attempts`、`available_at`、`heartbeat_at` 和 `worker_id`，不要只根据进程是否存在判断任务是否健康。

## 修改后自检

- PHP 与 Python 语法检查通过，ThinkPHP `price:strategy:consume --help` 可加载。
- 新通知的 `version` 有值，状态能按 pending→processing→done 流转。
- 并发启动两个诊断 Worker 时，同一 `dedupe_key` 不会执行两次。
- 同一秒连续心跳或重试写相同幂等键不会误进入 retry。
- 产品改价失败进入产品日志和通知汇总；通知级异常进入退避重试。done 不等于所有产品改价成功，排障要检查汇总 fail 和策略日志。
- Supervisor 重启后，失去心跳的任务能在超时后恢复，正常长任务不会被提前抢走。

## 目标租约、撞锁与积压处理

- `PriceStrategyTargetLease` 在 CAS 领取通知前获取目标级 Redis 锁；同一目标串行，不同目标可独立处理。撞目标锁仍保留 pending 且不增加 attempts。
- 目标锁使用随机 token、TTL 和 Lua 条件续租/释放；心跳同时续目标租约和通知租约。续租失败抛 `PriceStrategyLeaseLostException` 停止本轮改价，不继续平台写操作。
- 产品级锁由 `GameProductPriceService` 统一管理；`handleProductWithWait()` 对未发起 API 的撞锁产品重载重算，不重跑同一通知已处理的产品。当前同一策略实际改价请求至少间隔一秒，跳过产品不占用这一间隔。
- `crawl:notify:trim` 是独立的积压跳过命令：pending 超过 50 时，只把检查快照 `max_id` 以内仍为 pending 的通知标为 done，message 明确“跳过改价”；保留 processing 和检查后新增通知。它不同于删除历史终态记录，修改时保持这两个边界。
- 队列、锁、异常分层变化时检查 `test/service/PriceStrategyConcurrencyTest.php`、`PriceStrategyTargetLeaseTest.php`，并同步此 skill 和外部 API skill；未在本仓库验证外部 Python 生产者时明确说明。
