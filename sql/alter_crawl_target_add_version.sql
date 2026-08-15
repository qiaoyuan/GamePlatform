-- 为爬虫目标增加数据版本控制字段。
-- 版本 0 兼容现有目标和竞品数据；切换到新版本前，应先让爬虫写入对应版本的 crawl_data。
ALTER TABLE `crawl_target`
  ADD COLUMN `version` int unsigned NOT NULL DEFAULT 0 COMMENT '爬虫数据版本' AFTER `game_product_id`;
