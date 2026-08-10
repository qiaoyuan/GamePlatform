-- 改价策略模板表
CREATE TABLE `price_strategy` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '策略名称',
  `crawl_target_id` int unsigned NOT NULL DEFAULT 0 COMMENT '对标竞品池(爬取目标ID)',
  `config` text COMMENT '维度配置(JSON)，dimensions 有序数组，首期支持 type=lowest(含黑白名单/库存/好评率/保底/竞价幅度)',
  `auto_run` tinyint NOT NULL DEFAULT 1 COMMENT '爬取完成后自动执行一次 0-否 1-是',
  `interval_minutes` int NOT NULL DEFAULT 0 COMMENT '改价频率(分钟)，0=不定时，>0 则由定时任务按频率执行',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态 0-停用 1-启用',
  `last_run_at` datetime DEFAULT NULL COMMENT '最后执行时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_crawl_target_id` (`crawl_target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='改价策略模板';

-- 策略-产品绑定表（一个产品同时只能归属一套策略，用唯一索引强约束）
CREATE TABLE `price_strategy_product` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `price_strategy_id` int unsigned NOT NULL DEFAULT 0 COMMENT '策略ID',
  `game_product_id` int unsigned NOT NULL DEFAULT 0 COMMENT '游戏产品ID',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_game_product_id` (`game_product_id`),
  KEY `idx_price_strategy_id` (`price_strategy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='改价策略-产品绑定';

-- 策略执行日志表
CREATE TABLE `price_strategy_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `price_strategy_id` int unsigned NOT NULL DEFAULT 0 COMMENT '策略ID',
  `game_product_id` int unsigned NOT NULL DEFAULT 0 COMMENT '游戏产品ID',
  `old_price` decimal(20,8) NOT NULL DEFAULT 0 COMMENT '改价前价格',
  `new_price` decimal(20,8) NOT NULL DEFAULT 0 COMMENT '改价后价格',
  `ref_price` decimal(20,8) NOT NULL DEFAULT 0 COMMENT '参考价(竞品最低价)',
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '结果 0-跳过 1-成功 2-失败',
  `message` varchar(500) NOT NULL DEFAULT '' COMMENT '说明/原因',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_price_strategy_id` (`price_strategy_id`),
  KEY `idx_game_product_id` (`game_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='改价策略执行日志';
