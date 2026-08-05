---
name: game-platform-dev
description: 游戏数据平台后台（ThinkPHP admin）的开发规范。当新增/修改 Model、Controller、Validate，或实现列表(columns)、增删改查(CRUD)、软删除、时间字段时使用。涉及 app/admin/controller、app/common/model、app/common/validate 目录下的开发都应遵循。
---

# 游戏数据平台后台开发规范

本项目为"游戏数据平台"，只有后台（ThinkPHP + Vue admin）。所有后台业务开发必须遵循以下约定。参考实现见 `app/admin/controller/Questions.php`（Controller 规范）与 `app/common/model/Questions.php`（Model 规范）。

## 1. Model 规范（app/common/model/）

- 继承 `app\common\model\Base`（`Base` 已配置：`createTime=created_at`、`updateTime=updated_at`、`autoWriteTimestamp='datetime'`）。
- 必填属性：`$table`、`$pk`、`$field`（显式列出所有数据库字段）、`$type`（字段类型转换，无则留空数组）。
- 顶部写 PHPDoc `@property` 注释，逐个声明字段及中文含义，方便 IDE 提示。
- **时间字段是硬性要求：以后所有新建 Model 都必须包含**
  - `created_at` 创建时间
  - `updated_at` 修改时间
  - `deleted_at` 删除时间（软删除）
  并把这三个字段写进 `$field` 数组。
- 软删除：`BaseController::mDelete()` 会自动判断 `deleted_at` 是否存在——存在则做软删除（写入 `deleted_at`），不存在则物理删除。`tableList()` 也会自动过滤 `deleted_at` 为 NULL 的记录，并支持 `_recycle` 回收站查询。因此新表带上 `deleted_at` 即可获得软删除/回收站/恢复能力。
- 关联关系用方法定义并标注返回类型（`BelongsTo` / `HasMany` / `HasOne`）。
- 如需按字符串主键存储平台账号标记，`user_id` 用 string 类型（在 `$type` 中不要转成 int）。

### 游戏账号 Model 字段基线（示例）

游戏账号管理表 `account` 字段基线：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int 自增 | 主键 |
| `user_id` | string | 用户ID / 平台账号标记，如 `1004238644`，**字符串类型** |
| `active_device_token` | string | 设备活跃令牌 |
| `long_lived_token` | string | 长期访问令牌 |
| `refresh_token` | string | 刷新令牌 |
| `created_at` | datetime | 创建时间 |
| `updated_at` | datetime | 修改时间 |
| `deleted_at` | datetime | 删除时间（软删除） |

## 2. Controller 规范（app/admin/controller/）

- 继承 `app\admin\BaseController`。
- 用 `use app\common\model\Xxx as Model;` 引入对应模型，别名统一为 `Model`。
- 用 `#[Permission(...)]` 注解声明权限/菜单（`title`、`isMenu`、`parentUrl`、`sort`、`isHideSub` 等）。`index` 通常挂菜单，`add/edit/delete/status` 挂操作权限。
- 标准方法命名与职责：
  - `index()`：`$this->tableList(Model::class, [排序], [模糊搜索字段])->with([...])->selectData();` 返回 `['list' => $lists]`。
  - `add()`：优先用 `$this->mAdd(Model::class, ['append'=>[...]], [一对一关联])`；复杂逻辑用 `transaction(function(){...}, $this)`，手动写入时记得 `created_at = time()`。
  - `edit()`：优先用 `$this->mEdit(Model::class, [], [关联])`。
  - `delete()`：`$this->mDelete(Model::class)`（自动软删除）。
  - `forceDelete()` / `restore()`：需要时调用 `parent::forceDelete()` / `parent::restore()`。
  - `status()`：`Model::update(['status'=>$status], ['id'=>$this->getInputPk()]);`
  - `select()`：给下拉框用，`->field('title as label,id as value')->select()`。
  - `get()`：详情。
- **每个列表页控制器都必须实现 `columns(): array`**（见下）——这是表头字段的唯一规范来源，前端表头/搜索项由该接口生成。

## 3. columns() 表头规范（核心）

`columns()` 返回一个数组，每项描述一列。字段生成表头 + 搜索 + 排序 + 渲染。常用键：

- `v`：字段名，支持关联点号取值，如 `questionnaire.title`。
- `label`：中文列名。
- `sort`：可排序时填字段名。
- `search`：搜索绑定的字段名（与 `searchType` 配合）。
- `searchType`：搜索控件类型，常见值：
  - `match` / `multiple`：精确 / 多选（IN），常配 `searchList`。
  - `number`：数字。
  - `daterange`：日期范围（配合时间字段）。
  - `like`：模糊；`range` / `between`：区间。
- `searchList`：下拉数据源，可为接口路径（如 `/questionnaires/select`）或数组。
- `render`：前端渲染方式，如 `image`、`status`、`boolean`。
- `replace`：`true` 时用 `searchList` 的映射替换显示值。

时间列固定写法：
```php
['v' => 'created_at', 'label' => '日期', 'search' => 'created_at', 'searchType' => 'daterange', 'sort' => 'created_at'],
```

## 4. Validate 规范（app/common/validate/）

- 继承 `app\common\validate\Base`。
- 定义 `$rule`（`'字段|中文名' => [规则]`）、`$message`、`$scene`（至少 `add`、`edit`；`edit` 场景要包含 `id`）。
- `mAdd`/`mEdit` 会按控制器同名验证器的 `add`/`edit` 场景自动校验。

## 5. 路由与菜单（前后端联动）

菜单/路由是**动态生成**的：后端 `#[Permission]` 注解 → `php think permission <controller>` 同步进权限表 → 前端 `/index/menus` 拉取 → 按 `url` 动态注册路由并映射到 `@/views/{url}.vue`。

关键规则速记：

- index 方法的 url 自动为 `{camelController}/index`，必须带 `parentUrl`；其它方法 url 为 `{camelController}/{action}`，parent 默认 `{camelController}/index`。
- `url` 含 `/`（如 `account/index`）才会加载真实视图 `@/views/account/index.vue`；路由 name = url 按 `/`、`_` 拆分后首字母大写拼接（`account/index` → `AccountIndex`），视图组件 `name` 要一致。
- 列表页统一 `<w-tabs-table :module="module">`，`module` 为控制器 camel 名，表头由后端 `columns()` 生成，前端不手写列。
- 新增控制器/方法后务必运行 `php think permission <controller>` 同步权限。

完整规则、url→view 映射细节与前端视图骨架见 `references/routing.md`。

## 6. 开发新业务的推荐顺序（全栈）

1. 建表（含 `created_at`、`updated_at`、`deleted_at`）。
2. 建 Model（`$field`、`@property`、关联、`$type`）。
3. 建 Validate（`$rule` + `add`/`edit` 场景）。
4. 建 Controller（`#[Permission]` + `index`/`add`/`edit`/`delete`/`status`/`select`/`get` + `columns()`）。
5. 运行 `php think permission <controller>` 同步权限/菜单。
6. 建前端视图 `admin/src/views/{module}/index.vue`（`name` 与 url 对应，`module` 绑控制器名）+ `dialog/` 弹窗。
7. 自检：新 Model 是否含三个时间字段？列表控制器是否实现了 `columns()`？`#[Permission]` 是否补全并已同步？前端视图 `name`/`module` 是否与 url 对应？
