---
name: game-controller-convention
description: 游戏数据平台 Controller 开发规范。当新增/修改 app/admin/controller 下的控制器，或实现列表接口、增删改查(CRUD)、columns() 表头/搜索配置时使用。
---

# Controller 开发规范（app/admin/controller/）

参考实现见 `app/admin/controller/Questions.php`、`app/admin/controller/GameAccount.php`、`app/admin/controller/GameProduct.php`（含关联查询示例）。

## 基本约定

- 继承 `app\admin\BaseController`。
- 用 `use app\common\model\Xxx as Model;` 引入对应模型，别名统一为 `Model`。
- 权限用 `#[Permission(...)]` 注解声明（`title`、`isMenu`、`parentUrl`、`sort`、`isHideSub` 等）。权限注解的具体生成规则见 `game-routing-convention` skill。

## 标准方法

| 方法 | 职责 | 写法 |
|------|------|------|
| `index()` | 列表 | `$this->tableList(Model::class, [排序], [模糊搜索字段])->with([...])->selectData();` 返回 `['list' => $lists]` |
| `add()` | 新增 | 优先 `$this->mAdd(Model::class, ['append'=>[...]], [一对一关联])`；复杂逻辑用 `transaction(function(){...}, $this)` |
| `edit()` | 编辑 | 优先 `$this->mEdit(Model::class, [], [关联])` |

**踩坑提醒**：`mEdit()` 的 `except` 参数是在**校验之前**执行的（先剔除字段，再走 Validate 的 `edit` 场景）。如果某个字段被 `except` 排除但 Validate 的 `edit` 场景仍把它列为必填，会导致所有编辑请求都报"XX不能为空"。典型场景：某字段只能通过专用接口修改（如改价需同步第三方平台），此时要同步把该字段从 Validate 的 `edit` 场景里去掉（`add` 场景可以保留必填）。
| `delete()` | 删除 | `$this->mDelete(Model::class)`（自动软删除，取决于 Model 是否有 `deleted_at`） |
| `status()` | 改状态 | `Model::update(['status'=>$status], ['id'=>$this->getInputPk()]);` |
| `select()` | 下拉数据 | `->field('title as label,id as value')->select()` |
| `get()` | 详情 | `Model::find(input('id'))`，有关联时用 `Model::with([...])->find(...)` |
| `forceDelete()`/`restore()` | 彻底删除/恢复 | 调用 `parent::forceDelete()` / `parent::restore()` |

**每个列表页控制器都必须实现 `columns(): array`** —— 这是表头字段的唯一规范来源，前端表头/搜索项由该接口生成，前端不手写列。

## 关联字段处理（虚拟字段/关联表字段）

两种情况需要区分：

1. **直接读关联模型的字段**（如关联账号的 `user_id`）：`index()` 里 `->with(['关联方法名'])`，`columns()` 中直接用点号取值 `'v' => 'gameAccount.user_id'`。搭配 `search` 指向本表外键字段（如 `game_account_id`），`searchList` 指向对应的 `/xxx/select` 接口。

   如果关联表的展示字段本身可能为空（如账号名称可选，为空时应回退显示别的标识字段，或没有关联数据时显示 `--`），不要直接用点号取值，改用虚拟字段承载"展示优先级"逻辑：
   ```php
   $item->account_display = $item->gameAccount
       ? ($item->gameAccount->account_name ?: $item->gameAccount->user_id)
       : '--';
   ```
   `columns()` 中对应写 `'v' => 'account_display', 'search' => 'game_account_id', ...`（`search` 仍绑定外键字段，不受展示字段影响）。

2. **枚举翻译字段**（如平台 `platform` → 显示 `platform_name`）：这是虚拟字段，取不到时会报 `Undefined array key`，必须在 `index()` 里遍历赋值并做空值防御：
   ```php
   $lists = $this->tableList(Model::class, ...)->selectData();
   if (!is_numeric($lists)) {
       $lists->each(function (Model $item) {
           $item->platform_name = GameAccount::$PLATFORM_MAP[$item->platform] ?? '';
       });
   }
   ```
   `columns()` 中对应写 `'v' => 'platform_name', 'search' => 'platform', 'searchType' => 'multiple', 'searchList' => GameAccount::getPlatformList()`。

## columns() 表头规范（核心）

`columns()` 返回数组，每项描述一列，生成表头 + 搜索 + 排序 + 渲染。常用键：

- `v`：字段名，支持关联点号取值，如 `questionnaire.title`。
- `label`：中文列名。
- `sort`：可排序时填字段名。
- `search`：搜索绑定的字段名（与 `searchType` 配合）；显式设为 `false` 表示该列不可搜索。
- `searchType`：搜索控件类型：
  - `match` / `multiple`：精确 / 多选（IN），常配 `searchList`。
  - `number`：数字。
  - `daterange`：日期范围（配合时间字段）。
  - `like`：模糊；`range` / `between`：区间。
- `searchList`：下拉数据源，可为接口路径字符串（如 `/gameAccount/select`）或数组 `[{label, value}]`。
- `render`：前端渲染方式，如 `image`、`status`、`boolean`。
- `replace`：`true` 时用 `searchList` 的映射替换显示值。

时间列固定写法：
```php
['v' => 'created_at', 'label' => '日期', 'search' => 'created_at', 'searchType' => 'daterange', 'sort' => 'created_at'],
```

## 自检清单

- [ ] `index()` 用 `tableList()->selectData()`
- [ ] 实现了 `columns()`，时间列用 `daterange`
- [ ] **`columns()` 里出现的每个虚拟字段（如 `xxx_name`），`index()` 里都必须有对应的 `->each()` 计算逻辑，且用 `?? ''` 防空值** —— 这是最容易漏的一步：`columns()` 声明了列，但忘了在 `index()` 里赋值，表现为前端该列显示为空。新增/复制列表 Controller 时优先检查这一点。
- [ ] 增删改状态方法都带 `#[Permission]`
- [ ] 枚举搜索用了已有 Model 的 `getXxxList()`，未重复定义
