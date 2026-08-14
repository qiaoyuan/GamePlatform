-- 为已有 crawl_target 表增加必填的游戏产品关联。
-- 执行前请先确认旧目标的 game_product_id 回填方案；默认 0 的旧目标在绑定产品前不可继续爬取。
ALTER TABLE `crawl_target`
  ADD COLUMN `game_product_id` int unsigned NOT NULL DEFAULT 0 COMMENT '关联游戏产品ID' AFTER `id`,
  ADD KEY `idx_game_product_id` (`game_product_id`);
