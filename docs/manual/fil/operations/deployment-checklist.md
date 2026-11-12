# Checklist ng deployment at mga setting pangseguridad ng PHP

## Bersiyon ng PHP

- Gumamit ng **PHP ≥ 8.5.12** (o 8.4.27, 8.3.36). Inayos ng security release noong 2026-10-21 ang dalawang kahinaang nagpapahintulot ng remote code execution nang walang authentication (CVE-2026-109618 OOM state corruption, CVE-2026-109619 pagtagas ng state sa pagitan ng mga request), at sinabi ng php.net na "there is no complete mitigation; updating is the only safe option" (walang ganap na mitigasyon; ang pag-update lamang ang ligtas na opsiyon). Pinipigilan ng constraint na `"php": "^8.5.12"` sa `composer.json` ang pag-install sa anumang mas mababang PHP.
- Development environment: patakbuhin ang `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d`, saka kumpirmahin gamit ang `./vendor/bin/sail php -v`.
- Kapag bumubuo ng container o server image, huwag i-pin ang PHP package; kunin ang pinakabagong patch release. Ipinapakita rin ng `system:health-check` ang bersiyon ng PHP.

## php.ini sa production (sa php-fpm pool o configuration ng server, hindi sa `.user.ini` o `ini_set()`)

| Setting | Halaga | Dahilan |
|---|---|---|
| `session.upload_progress.enabled` | `0` | Hinaharangan ang mga daan ng CVE-2026-109619 at CVE-2026-115560 (mga advisory ng php.net noong 2026-10-21 at 23). Hindi gumagamit ng upload progress ang sistemang ito |
| `fatal_error_backtraces` | `0` (PHP 8.5) | Hinaharangan ang daan ng fatal-error backtrace mula sa parehong advisory |
| `memory_limit` | `256M` o higit pa, naka-pin gamit ang `php_admin_value` | Upang hindi ito mabago ng code ng application |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | Pinakamataas na limitasyon para sa mga dokumento ng admisyon, ebidensiya ng pagsusulit at pag-upload ng larawan (may palugit sa ibabaw ng 5MB at 2MB na validation ng application) |
| `expose_php` | `Off` | Huwag ilantad ang bersiyon |
| `display_errors` | `Off`, `log_errors=On` | Production |
| `session.cookie_secure`, `session.cookie_httponly`, `session.cookie_samesite` | `1`, `1`, `Lax` | Kasabay ng mga setting ng session ng Laravel (`SESSION_SECURE_COOKIE=true`) |
| `opcache.enable`, `opcache.validate_timestamps` | `1`, `0` (i-restart sa bawat deploy) | Performance |

## Environment ng application (.env)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` na nakatakda sa tunay na address.
- Ipasok ang `APP_KEY`, `PII_ENCRYPTION_KEY` at `PII_HASH_KEY` mula sa secret store at i-back up ang mga ito ([Pamamahala ng mga key](security-keys.md)). Kapag nawala ang mga key, hindi na made-decrypt ang personal na datos.
- DB: `DB_USERNAME=app_runtime`, `DB_MIGRATOR_USERNAME=app_migrator` (password na ginagamit lamang para sa mga migration), `app_reporting` para lamang sa mga BI tool.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`. Irehistro ang Horizon (`php artisan horizon`) at ang scheduler (`* * * * * php artisan schedule:run`) bilang mga service.
- `GOTENBERG_URL`: isang address sa internal na network. Hindi kailanman inilalantad sa labas ang Gotenberg.
- Mail (`MAIL_*`): ginagamit para sa mga imbitasyon, email ng two-factor authentication, at mga abiso tungkol sa pagpapatala at marka.

## Pamamaraan ng deployment

1. Mag-back up ([Backup at restore](backup-restore.md)).
2. I-deploy ang code, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` (awtomatikong lumilipat sa migrator connection), `php artisan security:sync-permissions`, `php artisan setup:install --force` (nire-refresh ang mahahalagang datos, pinapanatili ang mga binago ng administrator).
4. `php artisan optimize`, i-restart ang Horizon (`horizon:terminate`), i-restart ang php-fpm (inilalapat ang bagong php.ini).
5. Beripikahin gamit ang `php artisan system:health-check`. Beripikahin ang audit chain gamit ang `audit:verify-chain`.

## Mga regular na pagsusuri

| Dalas | Gawain |
|---|---|
| Araw-araw (awtomatiko) | `audit:seal`, `lms:reconcile`, pagpapalaya ng mga nakareserbang upuan sa pagpapatala, promosyon mula sa waitlist, pag-expire ng mga draft ng aplikasyon |
| Lingguhan | `system:health-check`, pagsuri sa mga security update ng PHP at OS, pagsuri sa mga bigong job sa Horizon |
| Buwanan | `media:prune-orphans --dry-run`, pagsasanay sa pag-restore ng backup, `audit:verify-chain` |
| Bawat termino | Repasuhin ang mga pagtatalaga ng tungkulin (mga taong umalis o lumipat), alisin ang mga rekord ng audit na lampas na sa panahon ng pagpapanatili (`audit:prune`) |
