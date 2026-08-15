-- 为 crawl_data 增加竞品数据版本控制字段。
-- crawl_data.game_product_id 应同时保存爬虫目标绑定的产品 ID，策略会同时按产品和版本过滤。
ALTER TABLE `crawl_data`
  ADD COLUMN `version` int unsigned NOT NULL DEFAULT 0 COMMENT '爬虫数据版本' AFTER `target_id`,
  ADD KEY `idx_target_version` (`target_id`, `version`);

-- 若历史数据的 game_product_id 仍为 0，可在确认目标绑定关系后回填：
-- UPDATE crawl_data d
-- INNER JOIN crawl_target t ON t.id = d.target_id
-- SET d.game_product_id = t.game_product_id
-- WHERE d.game_product_id = 0;
