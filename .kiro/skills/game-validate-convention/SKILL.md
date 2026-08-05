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
  - `edit` 场景在 `add` 基础上**必须包含 `id`**。

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
