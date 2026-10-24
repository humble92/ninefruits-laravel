# 部署检查清单与 PHP 安全设置

## PHP 版本

- 使用 **PHP ≥ 8.5.12**（或 8.4.27、8.3.36）。2026-10-21 的安全版本修复了两个无需认证的远程代码执行漏洞（CVE-2026-109618 OOM 状态损坏，CVE-2026-109619 请求间状态残留），php.net 表示"没有完整的缓解措施，只有更新才是安全的"。`composer.json` 中的 `"php": "^8.5.12"` 会阻止在更低版本的 PHP 上安装。
- 开发环境：执行 `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d` 后，用 `./vendor/bin/sail php -v` 确认。
- 构建容器或服务器镜像时不要固定 PHP 包版本，始终获取最新的补丁版本。`system:health-check` 也会显示 PHP 版本。

## 生产环境 php.ini（php-fpm 进程池或服务器配置，不是 `.user.ini` 或 `ini_set()`）

| 设置 | 值 | 原因 |
|---|---|---|
| `session.upload_progress.enabled` | `0` | 阻断 CVE-2026-109619 和 CVE-2026-115560 的利用路径（php.net 2026-10-21、23 公告）。本系统不使用上传进度显示 |
| `fatal_error_backtraces` | `0`（PHP 8.5） | 阻断同一公告中的致命错误回溯路径 |
| `memory_limit` | `256M` 以上，用 `php_admin_value` 固定 | 防止应用代码修改 |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | 招生材料、考试凭证、照片上传的上限（比应用内校验的 5MB、2MB 留有余量） |
| `expose_php` | `Off` | 防止暴露版本 |
| `display_errors` | `Off`，`log_errors=On` | 生产环境 |
| `session.cookie_secure`、`session.cookie_httponly`、`session.cookie_samesite` | `1`、`1`、`Lax` | 与 Laravel 会话设置（`SESSION_SECURE_COOKIE=true`）配合 |
| `opcache.enable`、`opcache.validate_timestamps` | `1`、`0`（部署时重启） | 性能 |

## 应用环境（.env）

- `APP_ENV=production`、`APP_DEBUG=false`，`APP_URL` 填写实际地址。
- `APP_KEY`、`PII_ENCRYPTION_KEY`、`PII_HASH_KEY` 从机密存储注入并做好备份（[安全密钥](security-keys.md)）。密钥丢失后个人信息将无法解密。
- 数据库：`DB_USERNAME=app_runtime`，`DB_MIGRATOR_USERNAME=app_migrator`（仅迁移时使用的密码），`app_reporting` 只给 BI 工具使用。
- `QUEUE_CONNECTION=redis`、`CACHE_STORE=redis`、`SESSION_DRIVER=redis`。把 Horizon（`php artisan horizon`）和调度器（`* * * * * php artisan schedule:run`）注册为服务。
- `GOTENBERG_URL`：内网地址。Gotenberg 不对外暴露。
- 邮件（`MAIL_*`）：用于邀请、双重身份验证邮件、选课和成绩通知。

## 部署步骤

1. 备份（[备份与恢复](backup-restore.md)）。
2. 部署代码，执行 `composer install --no-dev --optimize-autoloader`、`npm ci && npm run build`。
3. `php artisan migrate --force`（自动使用 migrator 连接）、`php artisan security:sync-permissions`、`php artisan setup:install --force`（更新必要数据，保留管理员的修改）。
4. `php artisan optimize`，重启 Horizon（`horizon:terminate`），重启 php-fpm（使新 php.ini 生效）。
5. 用 `php artisan system:health-check` 确认。用 `audit:verify-chain` 确认审计链。

## 定期检查

| 频率 | 事项 |
|---|---|
| 每天（自动） | `audit:seal`、`lms:reconcile`、释放选课预留名额、候补递补、申请草稿过期 |
| 每周 | `system:health-check`，检查 PHP 和操作系统安全更新，检查 Horizon 失败任务 |
| 每月 | `media:prune-orphans --dry-run`，备份恢复演练，`audit:verify-chain` |
| 每学期 | 检查角色分配（离职、调动人员），清理超过保留期限的审计记录（`audit:prune`） |
