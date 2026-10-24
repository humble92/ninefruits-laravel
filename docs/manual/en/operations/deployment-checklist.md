# Deployment checklist and PHP security settings

## PHP version

- Use **PHP ≥ 8.5.12** (or 8.4.27, 8.3.36). The security release of 2026-10-21 fixed two unauthenticated remote code execution vulnerabilities (CVE-2026-109618 OOM state corruption, CVE-2026-109619 state leaking between requests), and php.net stated that "there is no complete mitigation; updating is the only safe option". The `"php": "^8.5.12"` constraint in `composer.json` prevents installation on any lower PHP.
- Development environment: run `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d`, then confirm with `./vendor/bin/sail php -v`.
- When building container or server images, do not pin the PHP package; take the latest patch release. `system:health-check` also shows the PHP version.

## Production php.ini (php-fpm pool or server configuration, not `.user.ini` or `ini_set()`)

| Setting | Value | Reason |
|---|---|---|
| `session.upload_progress.enabled` | `0` | Blocks the paths of CVE-2026-109619 and CVE-2026-115560 (php.net advisories of 2026-10-21 and 23). This system does not use upload progress |
| `fatal_error_backtraces` | `0` (PHP 8.5) | Blocks the fatal-error backtrace path from the same advisory |
| `memory_limit` | `256M` or more, pinned with `php_admin_value` | So that application code cannot change it |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | Upper limit for admission documents, examination evidence and photo uploads (with headroom above the application's 5MB and 2MB validation) |
| `expose_php` | `Off` | Do not expose the version |
| `display_errors` | `Off`, `log_errors=On` | Production |
| `session.cookie_secure`, `session.cookie_httponly`, `session.cookie_samesite` | `1`, `1`, `Lax` | Together with the Laravel session settings (`SESSION_SECURE_COOKIE=true`) |
| `opcache.enable`, `opcache.validate_timestamps` | `1`, `0` (restart on deploy) | Performance |

## Application environment (.env)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` set to the real address.
- Inject `APP_KEY`, `PII_ENCRYPTION_KEY` and `PII_HASH_KEY` from the secret store and back them up ([Key management](security-keys.md)). If the keys are lost, personal data cannot be decrypted.
- DB: `DB_USERNAME=app_runtime`, `DB_MIGRATOR_USERNAME=app_migrator` (a password used only for migrations), `app_reporting` only for BI tools.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`. Register Horizon (`php artisan horizon`) and the scheduler (`* * * * * php artisan schedule:run`) as services.
- `GOTENBERG_URL`: an internal network address. Gotenberg is never exposed externally.
- Mail (`MAIL_*`): used for invitations, two-factor emails, and registration and grade notifications.

## Deployment procedure

1. Back up ([Backup and restore](backup-restore.md)).
2. Deploy the code, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` (switches to the migrator connection automatically), `php artisan security:sync-permissions`, `php artisan setup:install --force` (refreshes essential data, keeps administrator edits).
4. `php artisan optimize`, restart Horizon (`horizon:terminate`), restart php-fpm (applies the new php.ini).
5. Verify with `php artisan system:health-check`. Verify the audit chain with `audit:verify-chain`.

## Routine checks

| Frequency | Task |
|---|---|
| Daily (automatic) | `audit:seal`, `lms:reconcile`, release of reserved registration seats, waitlist promotion, expiry of application drafts |
| Weekly | `system:health-check`, check for PHP and OS security updates, check failed jobs in Horizon |
| Monthly | `media:prune-orphans --dry-run`, backup restore drill, `audit:verify-chain` |
| Every term | Review role assignments (people who left or moved), prune audit records past their retention period (`audit:prune`) |
