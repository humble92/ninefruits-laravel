# Pag-install

- Para kanino: mga IT operator
- Kaugnay: [Sanggunian ng mga command](commands.md), [Pamamahala ng mga key](security-keys.md)

## 1. Mga kinakailangan

| Bahagi | Bersiyon | Mga tala |
|---|---|---|
| PHP | 8.5 | Mga extension: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | Mga extension na `btree_gist` at `pg_trgm` (ini-install ng mga migration), ICU collation |
| Redis | 8 | Cache, mga session, mga queue |
| Gotenberg | 8 | Pagbuo ng PDF. Sa internal na network lamang inilalantad |
| Mail server | SMTP | Pag-reset ng password, mga email code ng two-factor authentication |
| Queue worker | Horizon | Panatilihing tumatakbo ang `php artisan horizon` sa ilalim ng isang process manager (systemd, supervisor) |
| Scheduler | cron | `* * * * * php artisan schedule:run` |

## 2. Paghahanda ng database

Patakbuhin nang isang beses bilang superuser ng DB. Hindi kailanman ginagamit ng application ang superuser.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| Role | Mga environment variable | Layunin |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | Pagpapatakbo ng application |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | Para sa mga migration lamang |
| `app_reporting` | (itinatakda sa BI tool) | Pagbasa ng schema na `reporting` |

## 3. Pagsasaayos ng environment

Kopyahin ang `.env.example` at punan ang mga halaga. Mga halagang dapat baguhin:

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`. **Kapag nawala ang mga ito, hindi na mababawi ang naka-encrypt na personal na datos.** Itago ang mga ito nang hiwalay ayon sa [Pamamahala ng mga key](security-keys.md).
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: ang default na time zone ng institusyon sa unang pag-install

## 4. Pag-install

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Nagtatanong ang `setup:install` habang tumatakbo. Sa mga deployment script, patakbuhin ito gamit ang `--force` at mga option ([Sanggunian ng mga command](commands.md#setupinstall)).

Profile ng pag-install (`--profile=`): `korean-university` — una/ikalawang semestre at summer-winter term, iskala ng marka na 4.5, mga kategorya ng asignatura, mga period ng klase na 50 minuto simula 09:00, mga gamit ng silid. Kung walang profile, ilagay ang mga ito sa panel ng administrasyon. Kapag muling inilapat ang profile sa ibang pagkakataon, pinapanatili ang mga halagang binago ng mga administrator.

Pagkatapos ng pag-install, ilalagay ng administrator ng operasyon ang sangguniang datos ayon sa [kabanata 5 ng manwal ng administrator, pagkakasunod-sunod ng unang pagsasaayos](../admin/05-institution-setup.md).

## 5. Pagberipika ng pag-install

1. Mag-sign in sa `/login` bilang unang administrator.
2. Itakda ang patakaran sa two-factor authentication sa **Mga setting > Seguridad** sa panel ng administrasyon.
3. I-enroll ang sariling two-factor authentication ng administrator (**Seguridad** sa portal).
4. Suriin ang PDF service gamit ang `php artisan pdf:sample`.
5. Kumpirmahin sa **Audit > Mga pagbabago ng datos** sa panel ng administrasyon na naitala ang pag-install bilang `system:command:setup:install`.

## 6. Pag-update

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```
