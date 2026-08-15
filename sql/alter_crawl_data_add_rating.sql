-- 为 crawl_data 增加竞品好评率。
ALTER TABLE `crawl_data`
  ADD COLUMN `rating` VARCHAR(10) DEFAULT NULL COMMENT '好评率（如 96.00）' AFTER `delivery_time`;
