-- 为 game_account 表增加 ELD 平台 OAuth2 凭证字段。
-- G2G 账号此两列留空；ELD 账号填 clientId / clientSecret，三个 token 字段可留空。
ALTER TABLE `game_account`
  ADD COLUMN `client_id`     varchar(255) NOT NULL DEFAULT '' COMMENT 'OAuth2 Client ID（ELD 等平台使用）'     AFTER `refresh_token`,
  ADD COLUMN `client_secret` varchar(255) NOT NULL DEFAULT '' COMMENT 'OAuth2 Client Secret（ELD 等平台使用）' AFTER `client_id`;
