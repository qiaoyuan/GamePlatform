-- 爬取目标链接表
CREATE TABLE `crawl_target` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `game_product_id` int unsigned NOT NULL DEFAULT 0 COMMENT '关联游戏产品ID',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '任务名称',
  `url` varchar(1024) NOT NULL DEFAULT '' COMMENT '目标链接',
  `category` varchar(64) NOT NULL DEFAULT '' COMMENT '产品分类',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态 0-停用 1-启用',
  `last_crawl_at` datetime DEFAULT NULL COMMENT '最后爬取时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `active_game_product_id` int unsigned GENERATED ALWAYS AS (IF(`deleted_at` IS NULL AND `game_product_id` > 0, `game_product_id`, NULL)) STORED,
  PRIMARY KEY (`id`),
  KEY `idx_game_product_id` (`game_product_id`),
  UNIQUE KEY `uk_crawl_target_active_game_product` (`active_game_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='爬取目标链接';

-- 竞品产品分析表
CREATE TABLE `competitor_product` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `crawl_target_id` int unsigned NOT NULL DEFAULT 0 COMMENT '爬取目标ID',
  `store_name` varchar(128) NOT NULL DEFAULT '' COMMENT '店铺唯一标识',
  `store_url` varchar(512) NOT NULL DEFAULT '' COMMENT '店铺链接',
  `store_level` varchar(32) NOT NULL DEFAULT '' COMMENT '店铺等级',
  `rating` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '好评率(%)，改价策略好评率过滤用',
  `stock` varchar(32) NOT NULL DEFAULT '' COMMENT '库存',
  `price` decimal(12,5) NOT NULL DEFAULT 0.00000 COMMENT '销售单价',
  `currency` varchar(8) NOT NULL DEFAULT 'USD' COMMENT '币种',
  `crawl_at` datetime DEFAULT NULL COMMENT '爬取时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_crawl_target_id` (`crawl_target_id`),
  KEY `idx_store_name` (`store_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='竞品产品分析';
