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

## 防止重复消费的硬性约束

- 领取必须使用带 `id + status=pending + available_at` 条件的原子 UPDATE（compare-and-set），不能先 SELECT 后直接执行。
- 领取后写入唯一 `dedupe_key={crawl_target_id}:{version}`；重复通知只允许一条占用该键。
- 执行和最终状态更新必须同时校验 `status=processing` 与当前 `worker_id`，防止失去租约的进程覆盖新 Worker。
- `attempts` 在成功领取时递增，不在空轮询或领取竞争失败时递增。
- 单个改价失败不能把整条通知标为完成；可重试错误回到 pending 并设置 `available_at`，达到最大次数后才进入 failed。

### MySQL affected rows 陷阱

MySQL 默认返回实际发生变化的行数。同一秒内刷新相同的 `heartbeat_at/updated_at`，或重试时再次写入相同 `version/dedupe_key`，UPDATE 可能返回 `0`，但租约仍然有效。

因此：

- 状态发生确定变化的 CAS（pending→processing、processing→done）可以要求返回 1。
- 心跳和幂等字段等允许同值写入的 UPDATE 返回 0 时，必须重新 SELECT，核对 `id + status + worker_id` 及预期字段；不能直接判定“处理权丢失”。

## 心跳、崩溃恢复与幂等边界

- Worker 在每个策略和产品处理前后更新 `heartbeat_at`。
- 只有 `COALESCE(heartbeat_at, started_at)` 超过 `stale-after` 的 processing 通知才能回收为 pending；超时必须大于单次平台请求的最大合理耗时。
- Supervisor 默认只运行一个 Worker；没有产品锁、账号限流和平台限速前，不要增加 `numprocs`。
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
- 平台调用失败时通知进入退避重试，成功或无需改价时才完成。
- Supervisor 重启后，失去心跳的任务能在超时后恢复，正常长任务不会被提前抢走。
