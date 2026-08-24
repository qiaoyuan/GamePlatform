-- 为 crawl_target 增加爬取间隔字段。
-- ELD 分类默认 5 分钟，G2G 分类默认 0（不限制间隔）。
ALTER TABLE `crawl_target`
  ADD COLUMN `crawl_interval` int unsigned NOT NULL DEFAULT 0
    COMMENT '爬取间隔（分钟），0=不限制；ELD 建议设为 5'
    AFTER `category`;
