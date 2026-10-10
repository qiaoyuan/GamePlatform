---
name: game-validate-convention
description: 游戏数据平台 Validate 开发规范。当新增/修改 app/common/validate 下的验证器，或需要定义 add/edit 校验场景时使用。
---

# Validate 开发规范（app/common/validate/）

参考实现见 `app/common/validate/GameAccount.php`、`app/common/validate/GameProduct.php`。

## 基本约定

- 继承 `app\common\validate\Base`。
- `$rule`：键为 `'字段名|中文名'`，方便报错信息自动带中文字段名。
- `$message`：自定义报错信息，一般留空数组，靠 `$rule` 的中文名自动生成。
- `$scene`：至少定义 `add`、`edit` 两个场景：
  - `add` 场景列出新增时需要校验的字段。
  - `edit` 场景校验实际允许编辑的字段，并包含主键（一般为 `id`）；不能机械复制 `add` 的所有必填字段。

## 与 Controller 的联动

`BaseController::mAdd()` / `mEdit()` 会按控制器同名的验证器，自动调用对应的 `add`/`edit` 场景做校验，不需要在 Controller 里手动 `validate()`。

## 示例

```php
class GameAccount extends Base
{
    protected $rule = [
        'user_id|用户ID' => ['require'],
        'platform|平台' => ['require'],
        'status|状态' => ['require'],
    ];

    protected $message = [];

    protected $scene = [
        'add' => ['user_id', 'platform', 'status'],
        'edit' => ['user_id', 'platform', 'status', 'id'],
    ];
}
```

## 自检清单

- [ ] 继承 `app\common\validate\Base`
- [ ] 定义了 `add`、`edit` 场景
- [ ] `edit` 场景包含 `id`
- [ ] 必填字段用 `require`，数值类字段加 `float`/`integer` 等类型校验

## 场景边界与当前业务规则

- `mEdit()` 先执行 `append/except` 再校验 `edit` 场景。只能经专用接口修改的字段（例如平台价格）应从通用 edit 场景移除，避免剔除后仍被 require 拦截。
- 将 `id` 放入场景不等于已定义 ID 校验规则；`mEdit()` 会另行补取主键并检查缺失。自定义写接口须显式检查 ID/允许值及归属，不能依赖场景列表完成这些检查。
- `CrawlTarget` 的分类允许值为 `物品,游戏币,ELD物品,ELD游戏币`，`crawl_server` 为 1/2，`crawl_type` 为 tinyint 数字枚举 `0/1`（0 默认、1 店铺加强），`status` 为 0/1；`version` 为非负整数且不开放在通用 edit 场景。`crawl_type` 在 add/edit 场景校验；旧客户端可省略，新增由数据库默认 0，编辑省略时保留原值。
- `PriceStrategy.config` 结构灵活，当前由 Controller/Service 处理默认值和维度语义；改变配置结构要同步服务和前端，不只修改 `$scene`。
- 新增和编辑允许字段、专用接口参数变化时，同步对应 Controller skill 和 Model 字段说明。

- CrawlTarget.enhance_stores 在 add/edit 场景校验，非必填，仅接受字符串，最长2048字符；空字符串表示没有加强名单，省略时保留既有值或使用数据库默认空字符串。
