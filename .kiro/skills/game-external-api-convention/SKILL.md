---
name: game-external-api-convention
description: 游戏数据平台第三方外部 API 集成规范。当需要调用 G2G 等外部平台接口（改价、令牌刷新、下单等）、处理令牌缓存、记录调用日志时使用。
---

# 第三方外部 API 集成规范

参考实现见 `app/common/service/G2gClient.php`（G2G 平台令牌刷新 + 改价）、`app/common/model/GameAccountApiLog.php`（调用日志）、`config/g2g.php`（配置）。

## 整体结构

```
app/common/service/{Platform}Client.php   -- 平台客户端类，封装认证+业务接口
app/common/model/GameAccountApiLog.php     -- 通用调用日志表（跨平台复用）
config/{platform}.php                      -- 平台域名/超时/token缓存时长等配置
```

## 1. Service 客户端类约定

- 放在 `app/common/service/`，命名 `{Platform}Client`（如 `G2gClient`）。
- 构造时传入对应的 `GameAccount`（该账号持有调用所需的令牌）。
- 用 `GuzzleHttp\Client`，`base_uri`/`timeout` 等从 `config('{platform}.xxx')` 读取，不要硬编码。
- 每个业务方法（如 `updatePrice`）内部若依赖鉴权令牌，统一调用 `getAccessToken()` 获取（带缓存）；不要在业务方法里重复写刷新逻辑。

## 2. 令牌缓存（避免频繁刷新）

- 用 ThinkPHP 的 `cache($key, $value, $ttl)`，缓存 key 按账号维度隔离：`{platform}_access_token_{account_id}`。
- 缓存时长设置为**略短于**令牌真实有效期（如真实15分钟、缓存10分钟），预留安全余量，避免临界过期。
- `getAccessToken()`：先读缓存，没有则调用 `refreshAccessToken()` 换新并写入缓存。
- `refreshAccessToken()`：强制刷新，不读缓存，用于 `getAccessToken()` 内部调用或手动强制刷新场景。

```php
public function getAccessToken(): string
{
    $cacheKey = 'g2g_access_token_' . $this->account->id;
    $token = cache($cacheKey);
    if ($token) {
        return $token;
    }
    $token = $this->refreshAccessToken();
    cache($cacheKey, $token, config('g2g.token_cache_ttl', 600));
    return $token;
}
```

## 3. 调用日志（每次外部请求必须落库）

- 统一用 `GameAccountApiLog::record([...])`，字段含：`game_account_id`、`game_product_id`（无关联业务对象则为0）、`type`（用 Model 里的 `TYPE_*` 常量）、`request_url`、`request_data`、`response_data`、`status`、`error_msg`、`duration_ms`。
- **日志表本身是纯日志表**：只有 `created_at`，没有 `updated_at`/`deleted_at`（参照 `AdminLog`/`SmsReport` 的既有模式），这是 `game-model-convention` 三时间字段规范的例外场景。
- 成功/失败都要记录，方便排查问题；耗时用 `microtime(true)` 起止计算，转成毫秒存 `duration_ms`。

## 4. 敏感字段脱敏（硬性要求）

调用日志中的令牌类字段（`refresh_token`、`active_device_token`、`long_lived_token`、`access_token`、`authorization` 等）**禁止明文入库**，必须脱敏后再落库：

```php
private function maskToken(string $token): string
{
    $len = strlen($token);
    if ($len <= 8) {
        return str_repeat('*', $len);
    }
    return substr($token, 0, 4) . '***' . substr($token, -4);
}
```

对请求/响应数据要做递归脱敏（`response_data` 里嵌套的 `payload.access_token` 等同样要处理），不要只处理顶层字段。

## 5. 错误处理

- Service 方法在失败时抛 `\RuntimeException`（不要吞掉异常静默失败），带上平台返回的错误信息。
- Controller 里用 `try/catch` 捕获，转成 `$this->error($e->getMessage())` 返回给前端，不要把原始异常堆栈暴露给用户。
- 网络异常（`GuzzleException`）和业务异常（平台返回 code 非成功）都要分别捕获并记录日志。

## 6. 安全与风险提示

- 改价/下单等**会影响真实线上数据**的调用，Controller 层要做参数校验（价格 > 0 等），避免误操作。
- 涉及资金/库存的接口，考虑是否需要操作二次确认（前端弹窗确认）和操作日志关联管理员（`admin_id`）。
- 不要在前端代码、日志、错误提示里回显完整的 access_token / refresh_token 等凭证。

## 自检清单

- [ ] Service 类构造依赖账号模型，业务方法通过 `getAccessToken()` 复用缓存
- [ ] 令牌缓存时长短于真实有效期，key 按账号维度隔离
- [ ] 每次外部调用（成功/失败）都记录 `GameAccountApiLog`
- [ ] 日志中的令牌字段已脱敏（含响应体嵌套字段）
- [ ] Controller 捕获异常并转换为用户可读的错误提示，未暴露堆栈
- [ ] 有实际业务影响的写操作（改价等）已做参数合法性校验
