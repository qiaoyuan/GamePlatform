-- 为 crawl_target 增加爬取间隔字段。
-- 实际总间隔由爬虫按「基础 30 秒 + 本字段秒数」计算。
ALTER TABLE `crawl_target`
  ADD COLUMN `crawl_interval` int unsigned NOT NULL DEFAULT 0
    COMMENT '基础30秒之外追加的爬取间隔（秒），0=不追加'
    AFTER `category`;
