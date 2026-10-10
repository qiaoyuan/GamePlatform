---
name: game-platform-dev
description: 游戏数据平台后台（ThinkPHP admin）的开发总览。当开始一个新的全栈业务模块开发（建表+Model+Validate+Controller+前端视图），或不确定该看哪个具体规范时使用。具体规范请查看对应专项 skill。
---

# 游戏数据平台后台开发总览

本 skill 覆盖游戏数据后台：ThinkPHP 8、PHP >= 8.2、Vue 2.7 + Element UI，以及爬取通知和改价服务。仓库另有 `app/index/`、`miniapp/`，不要把后台规范直接套到这些应用。按任务和文件路径读取下列专项 skill；路径本身不保证自动加载，项目入口规则见根目录 `AGENTS.md`。

| 专项 skill | 何时生效 | 覆盖内容 |
|-----------|---------|---------|
| `game-model-convention` | 编辑 `app/common/model/*.php` | 时间字段、软删除、`$field`/`$type`/`@property`、枚举复用 |
| `game-controller-convention` | 编辑 `app/admin/controller/*.php` | CRUD 标准方法、`columns()` 表头规范、关联字段/虚拟字段处理 |
| `game-validate-convention` | 编辑 `app/common/validate/*.php` | `add`/`edit` 校验场景规范 |
| `game-routing-convention` | 涉及权限菜单、路由、前端 views 目录 | `#[Permission]` 注解、`php think permission` 同步、url→视图映射、前端页面骨架 |
| `game-external-api-convention` | 编辑平台客户端、产品改价/库存/发布/同步服务及 `config/g2g.php`、`config/eldorado.php` | G2G / Eldorado 鉴权、日志脱敏、平台路由和改价锁 |
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

## 当前业务链路与维护

- 数据归属为 `admin → game_account → game_product → crawl_target`，继续关联竞品快照、策略和日志。管理员 ID=1 是当前实现的超级管理员；普通管理员的列表、下拉、统计和写操作都要检查归属，见 Controller skill。
- 平台枚举统一复用 `GameAccount`：G2G=1、Eldorado=2；`PLATFORM_FACEBOOK` 仅为旧兼容别名。
- 产品平台写操作由 `GameProductPriceService`、`GameProductStockService`、`GameProductPushService`、`GameProductOfferSyncService` 等共享服务承载；控制器和 Worker 不各写一套逻辑。
- `sql/` 保存建表和增量迁移；`scripts/crawl_g2g.mjs` 是仓库内爬虫脚本。生产 Python 爬虫位于独立 g2g 项目，修改生产者契约时核对该项目的 `tools/crawl_from_db.py`、`g2g/db.py`、`g2g/crawl_filter.py`，分别报告两个项目的验证范围，不能仅凭后台改动声称生产者已同步。
- 爬虫任务支持 crawl_type=0/1（0 默认、1 绑定店铺加强），G2G 游戏币默认前10条全部入库；enhance_stores 存多个店铺名，仅类型1对名单内竞品读取详情单价，不在生产者执行改价策略过滤。字段与通知契约、迁移顺序见 [Worker skill](../game-worker-convention/SKILL.md#列表候选与绑定店铺加强)。
- 修改模块后按 `AGENTS.md` 的路径映射同步相关 skill。只写经过源码和 diff 核实的稳定约定，替换过时规则，不追加流水账；跨模块规则变化时同步更新此总览。
- 验证按改动范围选择 PHP 语法检查、已有 `test/service/` 测试、`test/frontend/` Node 测试或前端构建；会改数据库的权限同步、迁移和会调用真实平台的操作应明确目标环境后执行。
