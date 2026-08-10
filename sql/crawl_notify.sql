-- 爬取完成通知表（Python 爬虫爬完一个目标后写入，PHP 定时消费后执行改价策略）
CREATE TABLE `crawl_notify` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `crawl_target_id` int unsigned NOT NULL DEFAULT 0 COMMENT '爬取目标ID(crawl_target.id)',
  `crawled_count` int NOT NULL DEFAULT 0 COMMENT '本次爬取条数',
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '处理状态 0-待处理 1-已处理 2-处理失败',
  `message` varchar(500) NOT NULL DEFAULT '' COMMENT '处理结果说明',
  `crawled_at` datetime DEFAULT NULL COMMENT 'Python 爬取完成时间',
  `processed_at` datetime DEFAULT NULL COMMENT 'PHP 处理时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_crawl_target_id` (`crawl_target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='爬取完成通知(Python->PHP 握手)';
