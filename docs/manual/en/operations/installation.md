# Installation

- Audience: IT operators
- Related: [Command reference](commands.md), [Key management](security-keys.md)

## 1. Requirements

| Component | Version | Notes |
|---|---|---|
| PHP | 8.5 | Extensions: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | `btree_gist` and `pg_trgm` extensions (installed by the migrations), ICU collation |
| Redis | 8 | Cache, sessions, queues |
| Gotenberg | 8 | PDF generation. Exposed only on the internal network |
| Mail server | SMTP | Password resets, two-factor email codes |
| Queue worker | Horizon | Keep `php artisan horizon` running under a process manager (systemd, supervisor) |
| Scheduler | cron | `* * * * * php artisan schedule:run` |

## 2. Database preparation

Run once as the DB superuser. The application never uses the superuser.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| Role | Environment variables | Purpose |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | Running the application |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | Migrations only |
| `app_reporting` | (configured in the BI tool) | Reading the `reporting` schema |

## 3. Environment configuration

Copy `.env.example` and fill in the values. Values that must be changed

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`. **If they are lost, encrypted personal data cannot be recovered.** Store them separately as described in [Key management](security-keys.md).
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: the default institution time zone at first installation

## 4. Installation

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` proceeds by asking questions. In deployment scripts, run it with `--force` and options ([Command reference](commands.md#setupinstall)).

Installation profile (`--profile=`): `korean-university` — first/second/summer-winter terms, 4.5 grade scale, course categories, 50-minute time slots starting at 09:00, room purposes. Without a profile, enter these in the administration panel. Applying a profile again later keeps the values administrators have changed.

After installation, the business administrator enters the reference data following [Administrator manual chapter 5, initial setup order](../admin/05-institution-setup.md).

## 5. Verifying the installation

1. Sign in at `/login` as the initial administrator.
2. Set the two-factor authentication policy under **Settings > Security** in the administration panel.
3. Enroll the administrator's own two-factor authentication (portal **Security**).
4. Check the PDF service with `php artisan pdf:sample`.
5. Confirm under **Audit > Data changes** in the administration panel that the installation was recorded as `system:command:setup:install`.

## 6. Updating

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```
