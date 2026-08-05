# 路由与菜单约定（前后端联动）

本项目的路由/菜单是**动态生成**的：后端用 `#[Permission]` 注解声明，经命令同步进权限表，前端拉取后按 `url` 动态注册路由并映射到视图组件。改代码新增页面时，必须走完整条链路。

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

## 1. 后端：Permission 注解 → url 生成规则

同步命令（`app/common/command/Permission.php`）：

- 同步单个控制器：`php think permission Account`
- 同步全部控制器：`php think permission all`

url 生成规则（`Str::camel(控制器名)`）：

- **类注解**（顶级菜单，level 1）：必须显式传 `url`，例如
  ```php
  #[Permission(title: '游戏管理', isMenu: 1, url: 'game')]
  class Account extends BaseController
  ```
- **index 方法**：url 自动设为 `{camelController}/index`，必须提供 `parentUrl` 指向父级（父级的 url），否则同步跳过。
  ```php
  #[Permission(title: '游戏账号', isMenu: 1, parentUrl: 'game', isHideSub: 1)]
  public function index() {}
  // -> url = account/index
  ```
- **其它方法**（add/edit/delete/status...）：url 自动设为 `{camelController}/{action}`；`parentUrl` 默认取 `{camelController}/index`，无需手写。
  ```php
  #[Permission(title: '新增账号')] public function add() {}
  // -> url = account/add, parent = account/index
  ```

注解字段：`title`、`isMenu`、`isHide`、`parentUrl`、`isHideSub`、`sort`、`url`。

## 2. 前端：url → 视图组件映射

`store/module/permission.js` 的 `GenerateRoutes`：

- `level === 1`：`component = Layout`，`path = '/' + url`（顶级菜单容器）。
- `level > 1`：
  - `url` 含 `/`（如 `account/index`）→ `component = url`，实际加载 `@/views/{url}.vue`（即 `@/views/account/index.vue`）。
  - `url` 不含 `/` → `component = Empty`（仅作为分组占位）。
- 路由 `name` = 把 url 按 `/` 和 `_` 拆分后每段首字母大写拼接。例如 `account/index` → `AccountIndex`；`user_channel/index` → `UserChannelIndex`。

## 3. 前端视图约定

- 列表页固定放在 `admin/src/views/{module}/index.vue`，`{module}` 为控制器 camel 名（如 `account`）。
- 视图组件 `name` 必须与路由 name 一致（如 `AccountIndex`），否则 keep-alive 缓存异常。
- 列表统一用 `<w-tabs-table :module="module">`，`module` 绑定控制器名（如 `'account'`），表头/搜索项由后端 `columns()` 接口自动生成，**前端不手写列**。
- 增删改弹窗放 `views/{module}/dialog/` 下（如 `accountAddDialog.vue`），通过 `ref` 调 `open(row)`。

标准列表页骨架：
```vue
<template>
  <div class="app-container">
    <w-tabs-table ref="wTable" :operates="operates" :module="module" k
      @add="onAdd" @edit="onEdit" />
    <AccountAddDialog ref="accountAddDialog" @done="getList" />
  </div>
</template>
<script>
import AccountAddDialog from './dialog/accountAddDialog'
export default {
  name: 'AccountIndex',
  components: { AccountAddDialog },
  data() {
    return {
      module: 'account',
      operates: { del: true, add: true, edit: true, multiDel: true },
    }
  },
  methods: {
    getList() {
      this.$store.dispatch('cleanColumnOptions', this.module)
      this.$refs.wTable.getList()
    },
    onEdit(row) { this.$refs.accountAddDialog.open(row) },
    onAdd() { this.$refs.accountAddDialog.open({}) },
  }
}
</script>
```

## 4. 新增页面必做清单

1. Controller 加 `#[Permission]`（index 带 `parentUrl`，操作方法按需加权限）。
2. 运行 `php think permission <controller>` 同步权限表。
3. 建前端视图 `views/{module}/index.vue`（name 与 url 对应，`module` 绑定控制器名）。
4. 需要增改时建 `views/{module}/dialog/` 弹窗组件。
5. Controller 实现 `columns()` —— 前端表头/搜索项来源。
