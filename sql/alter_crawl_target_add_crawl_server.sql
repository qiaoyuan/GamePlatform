-- 为已有爬取目标增加执行服务器选择；历史数据默认归属主服务器。
ALTER TABLE `crawl_target`
ADD COLUMN `crawl_server` tinyint unsigned NOT NULL DEFAULT 1
COMMENT '爬虫服务器 1-主服务器 2-爬虫1'
AFTER `category`;
