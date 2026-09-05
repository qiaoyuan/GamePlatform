-- 游戏平台账号按后台登录账号隔离。
-- 历史数据统一归系统超级管理员（admin.id=1）；后续新增由接口写入当前登录管理员 ID。
ALTER TABLE `game_account`
  ADD COLUMN `admin_id` int unsigned NOT NULL DEFAULT 1 COMMENT '所属后台管理员ID' AFTER `id`,
  ADD KEY `idx_game_account_admin_id` (`admin_id`);
