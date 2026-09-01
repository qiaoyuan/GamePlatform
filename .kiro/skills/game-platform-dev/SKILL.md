---
name: game-platform-dev
description: 游戏数据平台后台（ThinkPHP admin）的开发总览。当开始一个新的全栈业务模块开发（建表+Model+Validate+Controller+前端视图），或不确定该看哪个具体规范时使用。具体规范请查看对应专项 skill。
---

# 游戏数据平台后台开发总览

本项目为"游戏数据平台"，只有后台（ThinkPHP + Vue admin）。这是一个路由入口 skill，按开发环节拆分为以下专项 skill，Kiro 会根据你正在编辑的文件类型自动匹配加载对应规范：

| 专项 skill | 何时生效 | 覆盖内容 |
|-----------|---------|---------|
| `game-model-convention` | 编辑 `app/common/model/*.php` | 时间字段、软删除、`$field`/`$type`/`@property`、枚举复用 |
| `game-controller-convention` | 编辑 `app/admin/controller/*.php` | CRUD 标准方法、`columns()` 表头规范、关联字段/虚拟字段处理 |
| `game-validate-convention` | 编辑 `app/common/validate/*.php` | `add`/`edit` 校验场景规范 |
| `game-routing-convention` | 涉及权限菜单、路由、前端 views 目录 | `#[Permission]` 注解、`php think permission` 同步、url→视图映射、前端页面骨架 |
| `game-worker-convention` | 编辑通知队列、常驻命令、改价消费或部署 Supervisor | 通知版本、原子领取、幂等、心跳租约、重试、部署与清理 |

参考实现见 `app/admin/controller/GameAccount.php` + `app/common/model/GameAccount.php`（基础 CRUD）、`app/admin/controller/GameProduct.php`（带关联查询与枚举翻译字段）。

## 开发新业务的推荐顺序（全栈）

1. 建表（含 `created_at`、`updated_at`、`deleted_at`）—— 参见 `game-model-convention`。
2. 建 Model —— 参见 `game-model-convention`。
3. 建 Validate —— 参见 `game-validate-convention`。
4. 建 Controller（含 `columns()`）—— 参见 `game-controller-convention`。
5. 同步权限菜单 + 建前端视图 —— 参见 `game-routing-convention`。
6. 自检：跑一遍增删改查接口，确认软删除、关联字段显示、权限挂靠位置都正确。

## 命名与目录约定（跨环节通用）

- 控制器/模型/验证器统一用同名的大驼峰命名（如 `GameAccount`），控制器里模型别名统一为 `Model`。
- 前端目录/路由用小驼峰（如 `gameAccount`），`views/{module}/index.vue` + `views/{module}/dialog/`。
- 新模块优先复用已有的枚举/常量定义（如平台类型直接引用 `GameAccount::$PLATFORM_MAP`），避免同一语义在多个 Model 里重复定义。
