-- 将已有 crawl_notify 升级为可供多个常驻 Worker 安全领取的数据库队列。
-- 上线顺序：先执行本文件，再部署新版 Python 生产者和 PHP Worker。
ALTER TABLE `crawl_notify`
  ADD COLUMN `version` int unsigned DEFAULT NULL COMMENT '本次爬取数据版本' AFTER `crawl_target_id`,
  ADD COLUMN `attempts` int unsigned NOT NULL DEFAULT 0 COMMENT '已领取次数' AFTER `status`,
  ADD COLUMN `available_at` datetime DEFAULT NULL COMMENT '下次可领取时间' AFTER `attempts`,
  ADD COLUMN `started_at` datetime DEFAULT NULL COMMENT '本次领取时间' AFTER `available_at`,
  ADD COLUMN `heartbeat_at` datetime DEFAULT NULL COMMENT 'Worker最近心跳时间' AFTER `started_at`,
  ADD COLUMN `worker_id` varchar(100) NOT NULL DEFAULT '' COMMENT '当前Worker标识' AFTER `heartbeat_at`,
  ADD COLUMN `dedupe_key` varchar(100) DEFAULT NULL COMMENT '目标版本幂等键' AFTER `worker_id`,
  MODIFY COLUMN `status` tinyint NOT NULL DEFAULT 0 COMMENT '处理状态 0-待处理 1-已处理 2-处理失败 3-处理中',
  ADD KEY `idx_claim` (`status`, `available_at`, `id`),
  ADD KEY `idx_stale` (`status`, `heartbeat_at`),
  ADD UNIQUE KEY `uk_dedupe_key` (`dedupe_key`);

