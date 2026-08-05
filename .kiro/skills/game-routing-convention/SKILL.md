---
name: game-routing-convention
description: 游戏数据平台路由与菜单开发规范。当新增控制器/方法后需要同步权限菜单，或新增前端页面路由、views 目录结构，或涉及 #[Permission] 注解、php think permission 同步命令时使用。
---

# 路由与菜单开发规范（前后端联动）

本项目的路由/菜单是**动态生成**的：后端用 `#[Permission]` 注解声明，经命令同步进权限表，前端拉取后按 `url` 动态注册路由并映射到视图组件。新增页面时必须走完整链路。

## 整体链路

```
后端 Controller 的 #[Permission] 注解
        │  php think permission <controller>
        ▼
admin_permission 表（url / parent_id / level / is_menu ...）
        │  前端 POST /index/menus
        ▼
前端 store/module/permission.js -> GenerateRoutes
        │  url -> component 映射
        ▼
@/views/{url}.vue  动态注册为路由
```

详细规则见 `references/routing.md`（url 生成规则、url→视图映射、前端视图骨架示例）。

## 关键规则速记

- **同步命令**：`php think permission <controller>`（单个）或 `php think permission all`（全部）。新增控制器/方法后必须运行，否则菜单/权限不会生效。
- **index 方法**：url 自动为 `{camelController}/index`，**必须**带 `parentUrl` 指向父级菜单的 url，否则同步时会跳过并报错。
- **其它方法**（add/edit/delete/status...）：url 自动为 `{camelController}/{action}`，`parentUrl` 默认取 `{camelController}/index`，一般无需手写。
- **新建顶级菜单** vs **挂靠已有菜单**：优先挂靠已有顶级菜单（如 `user`、`article`），除非业务上确实是全新的一级功能域。挂靠已有菜单时 `parentUrl` 直接填已有顶级菜单的 url。
- **url → 视图映射**：`url` 含 `/`（如 `account/index`）才会加载真实视图 `@/views/account/index.vue`；路由 `name` = url 按 `/`、`_` 拆分后首字母大写拼接（`account/index` → `AccountIndex`），前端视图组件 `name` 必须一致。
- **列表页统一** `<w-tabs-table :module="module">`，`module` 为控制器 camel 名，表头由后端 `columns()` 生成，前端不手写列。

## 新增页面必做清单

1. Controller 加 `#[Permission]`（index 带 `parentUrl`，操作方法按需加权限）。
2. 运行 `php think permission <controller>` 同步权限表。
3. 建前端视图 `views/{module}/index.vue`（`name` 与 url 对应，`module` 绑定控制器名）。
4. 需要增改时建 `views/{module}/dialog/` 弹窗组件（关联字段用 `formType: 'select', options: '/xxx/select'`）。
5. Controller 实现 `columns()` —— 前端表头/搜索项来源（详见 `game-controller-convention` skill）。

## 自检清单

- [ ] `index` 的 `#[Permission]` 带 `parentUrl`，且挂靠位置符合信息架构
- [ ] 已运行 `php think permission <controller>` 同步
- [ ] 前端视图 `name` 与路由 name 一致
- [ ] `module` 绑定的字符串与控制器 camel 名一致
