-- 确保一个未删除的爬虫目标只能绑定一个游戏产品。
-- active_game_product_id 对已删除目标和未绑定值(0)返回 NULL，允许产品在软删除后重新绑定。
-- 执行 ALTER TABLE 前必须确认下面的重复查询返回空结果：
-- SELECT game_product_id, COUNT(*) AS total
-- FROM crawl_target
-- WHERE deleted_at IS NULL AND game_product_id > 0
-- GROUP BY game_product_id
-- HAVING COUNT(*) > 1;

ALTER TABLE `crawl_target`
  ADD COLUMN `active_game_product_id` int unsigned
    GENERATED ALWAYS AS (IF(`deleted_at` IS NULL AND `game_product_id` > 0, `game_product_id`, NULL)) STORED,
  ADD UNIQUE KEY `uk_crawl_target_active_game_product` (`active_game_product_id`);
