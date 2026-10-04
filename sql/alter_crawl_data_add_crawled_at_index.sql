-- 支持竞品数据默认按抓取时间倒序读取。
ALTER TABLE `crawl_data`
  ADD KEY `idx_crawled_at` (`crawled_at`);
