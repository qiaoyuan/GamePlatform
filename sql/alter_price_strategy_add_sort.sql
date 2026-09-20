-- 现有数据库部署时执行一次；先执行 SQL，再发布 PHP。
-- NULL 按原 ID 倒序排列；拖动后保存权重。新增策略默认排到最前。
ALTER TABLE `price_strategy`
  ADD COLUMN `sort` int unsigned DEFAULT NULL COMMENT '列表拖拽排序权重，NULL使用ID' AFTER `name`;

-- 加速列表读取各策略最近一次成功改价的日志。
ALTER TABLE `price_strategy_log`
  ADD INDEX `idx_strategy_status_id` (`price_strategy_id`, `status`, `id`);
