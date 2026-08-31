-- 为 game_product 增加线上平台 offer 数据字段（仅 ELD 使用）。
-- 存放从 Eldorado 同步下来的精简 JSON：details(描述/交付/定价) + augmentedGame(游戏/分类/交易环境ID)。
-- G2G 平台不同步该字段，保持为 NULL。
ALTER TABLE `game_product`
  ADD COLUMN `offer_data` json DEFAULT NULL
    COMMENT '线上平台offer数据(JSON)，ELD同步写入'
    AFTER `status`;
