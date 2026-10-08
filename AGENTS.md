# 项目指令

@/Users/qiaoyuan/.codex/RTK.md

## 按模块读取 skill

项目 skill 的唯一维护源为 `.kiro/skills/`。开始相关开发时读取下表对应的 `SKILL.md`；全栈开发或跨模块改动先读取 `game-platform-dev`。不要仅因为文件放在 `.kiro/skills/` 就假设 Codex 已自动加载。

| 改动范围 | 读取/维护的 skill |
| --- | --- |
| `app/common/model/**`、`sql/**` | `game-model-convention`；通知/队列表另读 `game-worker-convention` |
| `app/admin/controller/**`、`app/admin/BaseController.php`、`app/common/BaseController.php` | `game-controller-convention`；权限、表头或响应契约另读 `game-routing-convention` |
| `app/common/validate/**` | `game-validate-convention` |
| `route/**`、`config/route.php`、`app/common/annotation/**`、Permission 注解/命令、`admin/src/views/**`、`admin/src/router/**`、`admin/src/store/**`、`admin/src/components/w/**`、`admin/src/api/**`、`admin/src/utils/**` | `game-routing-convention`；Controller 接口变化另读 `game-controller-convention` |
| `app/common/service/**`、`config/g2g.php`、`config/eldorado.php` | `game-external-api-convention`；PriceStrategy/Crawl/租约另读 `game-worker-convention` |
| `app/common/command/**`、`scripts/**`、`config/console.php`、`config/queue.php`、`config/redis.php`、`config/cache.php`、`*.conf` | 涉及爬取通知/改价消费/锁/部署时读 `game-worker-convention`；Permission 命令读 `game-routing-convention` |
| `test/service/**`、`test/frontend/**` | 按被测模块选择上述 skill |

## 模块改动后同步维护（Codex）

完成相关模块改动、回复用户之前，检查本次 diff 和当前源码，直接同步对应 skill 中受影响的接口、字段、关联、权限、事件、平台调用、队列状态、重试和锁约定。跨模块架构变化同时更新 `game-platform-dev`；路由细节变化同步 `game-routing-convention/references/routing.md`。

- 修改已有相关段落，删除或替换失效规则，不追加每次改动的流水账。
- 仅维护本次确认的事实；源码与旧规则矛盾时先确认实际行为，不把计划、猜测或未验证的外部程序写成既成事实。
- 纯样式、格式、注释以及未改变稳定契约的修复只需核对，skill 内容已经准确时不强制改写。
- 若用户明确限制修改文档或要求只读，遵守用户范围；在结果中说明规范待同步。
- skill 更新本身不授权调用真实平台、执行数据库迁移或同步权限数据。

## Kiro 事件钩子

`.kiro/hooks/sync-module-skills.json` 使用 Kiro IDE 1.x / CLI 3.x 的独立 JSON 格式，在代理创建、保存、删除相关模块文件后触发 skill 维护提示。钩子不监听 `.kiro/**` 或 `AGENTS.md`，防止更新规范循环触发。

这是 Kiro 的事件配置，不是 Codex 的原生事件钩子，也不是后台文件监视器；Codex 通过本文件的“回复前同步维护”规则执行同样的维护要求。人工编辑或外部脚本写入不能假定触发 Kiro 代理文件事件；后续任务检查相关 diff 时补齐规范。钩子格式依据 https://kiro.dev/docs/hooks/ 。
