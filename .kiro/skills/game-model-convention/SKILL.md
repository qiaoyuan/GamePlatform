---
name: game-model-convention
description: 游戏数据平台 Model 开发规范。当新增/修改 app/common/model 下的模型文件，或涉及数据库字段设计（时间字段、软删除、$field/$type/@property、关联关系）时使用。
---

# Model 开发规范（app/common/model/）

参考实现见 `app/common/model/Questions.php`、`app/common/model/GameAccount.php`。

## 基本要求

- 继承 `app\common\model\Base`（`Base` 已配置：`createTime=created_at`、`updateTime=updated_at`、`autoWriteTimestamp='datetime'`）。
- 必填属性：`$table`、`$pk`、`$field`（显式列出所有数据库字段）、`$type`（字段类型转换，无则留空数组）。
- 顶部写 PHPDoc `@property` 注释，逐个声明字段及中文含义，方便 IDE 提示。
- 关联关系用方法定义并标注返回类型（`BelongsTo` / `HasMany` / `HasOne`）。

## 时间字段（硬性要求）

以后所有新建 Model 都必须包含以下三个字段，并写进 `$field` 数组：

- `created_at` 创建时间
- `updated_at` 修改时间
- `deleted_at` 删除时间（软删除）

软删除机制：`BaseController::mDelete()` 会自动判断 `deleted_at` 是否存在——存在则做软删除（写入 `deleted_at`），不存在则物理删除。`tableList()` 也会自动过滤 `deleted_at` 为 NULL 的记录，并支持 `_recycle` 回收站查询。因此新表带上 `deleted_at` 即可自动获得软删除/回收站/恢复能力，不需要额外代码。

**例外：纯日志表**（如操作日志、第三方 API 调用日志）不适用三时间字段规范——日志只增不改不删，只需要 `created_at`，显式设置 `protected $updateTime = false`，不要加 `updated_at`/`deleted_at`。参照既有的 `AdminLog`、`SmsReport`、`GameAccountApiLog`。

## 字段类型注意事项

- 平台账号标记等长数字字符串（如 `user_id: 1004238644`）用 `string` 类型，`$type` 中不要转成 int（避免精度丢失/前端大数问题）。
- 金额字段用 `float`（在 `$type` 中声明），数据库层建议用 `decimal`。
- 枚举/平台类字段：用 `const` + 静态 `$XXX_MAP` 数组定义，并提供 `getXxxList()` 静态方法返回 `[{label, value}]` 供前端下拉复用（参照 `GameAccount::$PLATFORM_MAP` / `getPlatformList()`）。**同一枚举语义不要在多个 Model 里重复定义**，优先复用已有 Model 的映射（如产品表的平台字段直接引用 `GameAccount::$PLATFORM_MAP`）。

## 示例：游戏账号字段基线

`game_account` 表字段基线：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int 自增 | 主键 |
| `user_id` | string | 用户ID / 平台账号标记，**字符串类型** |
| `platform` | tinyint | 平台，如 1:G2G |
| `active_device_token` | string | 设备活跃令牌 |
| `long_lived_token` | string | 长期访问令牌 |
| `refresh_token` | string | 刷新令牌 |
| `created_at` | datetime | 创建时间 |
| `updated_at` | datetime | 修改时间 |
| `deleted_at` | datetime | 删除时间（软删除） |

## 自检清单

- [ ] 继承 `Base`
- [ ] `$table`/`$pk`/`$field`/`$type` 齐全
- [ ] `@property` 注释完整
- [ ] `$field` 含 `created_at`/`updated_at`/`deleted_at`
- [ ] 枚举字段有 `getXxxList()`，且未与已有 Model 重复定义

## 当前字段与跨表契约

- 上方账号表是基础示例，不是完整现行 schema。以 `GameAccount::$field/$type` 和 `sql/` 为准；现有账号还含 `admin_id`、`account_name`、`client_id`、`client_secret`、`status`。`user_id` 及令牌/平台标识保持字符串。
- `GameProduct.product_id` 是远端 offer 标识，可能在 Eldorado 重建时变化或在旧 offer 不存在时为空；数据库 `id` 才是内部关联键。`offer_data` 用 `array` 转换，保留重建需要的完整载荷。
- CrawlTarget 包含 game_product_id、version、crawl_server、crawl_interval、crawl_type、enhance_stores 等字段。服务器1/2，类型0默认、1店铺加强，历史目标默认0；枚举复用 CRAWL_TYPE_MAP/getCrawlTypeList()，CRAWL_TYPE_TOP3 为历史别名。enhance_stores 是 varchar(2048) 字符串，默认空，输入多个名字支持英文/中文逗号及换行，Model 写入修改器去除首尾空白、空项和完全重复名字，统一保存为英文逗号分隔（保留名字大小写和内部空格）；仅类型1对前10条中的名单内竞品刷新详情，全部列表候选入库。分类仍为物品、游戏币、ELD物品、ELD游戏币，修改分类要同步验证器、统计和爬虫平台判断。
- `CrawlData` 以 `target_id + version` 对应一批竞品快照；通知中的版本用于锁定消费快照，不用目标最新版本覆盖历史通知。
- 通知队列表 `CrawlNotify` 会更新状态、重试和租约，不属于只增不改的纯日志例外。现有历史表按实际 schema 处理，不因三时间字段约定直接补列。
- 字段增删/类型变化要同步 SQL 迁移、`$field`、`$type`、PHPDoc、验证器和接口；关联字段变化同时检查管理员归属链。
