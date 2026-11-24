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

## 7. Web server (Nginx)

Ilagay ang Nginx sa harap ng php-fpm. Halimbawa lamang ang nasa ibaba: palitan ang address, mga path, mga sertipiko at ang php-fpm socket ayon sa iyong server.

```nginx
log_format sis '$remote_addr - $remote_user [$time_local] "$request" $status $body_bytes_sent '
               '"$http_referer" "$http_user_agent" rt=$request_time rid=$request_id';

server {
    listen 443 ssl;
    http2 on;
    server_name sis.example.edu;
    root /var/www/sis/public;
    index index.php;

    ssl_certificate     /etc/ssl/sis/fullchain.pem;
    ssl_certificate_key /etc/ssl/sis/privkey.pem;

    access_log /var/log/nginx/sis.access.log sis;
    client_max_body_size 32m;     # same as post_max_size in php.ini
    server_tokens off;

    # Only when PHP does not answer. The application's own error pages pass through.
    error_page 502 503 504 /errors/unavailable.html;
    location = /errors/unavailable.html {
        internal;
        ssi on;                    # puts $request_id on the page
        add_header Cache-Control "no-store" always;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param HTTP_X_REQUEST_ID $request_id;   # replaces a client-sent X-Request-Id
        internal;
    }

    location ~ \.php$ { return 404; }
    location ~ /\.(?!well-known) { deny all; }
}
```

- **Error page:** Ang `public/errors/unavailable.html` ay ang static na pahinang ipinapakita ng Nginx kapag hindi sumasagot ang PHP (502, 503, 504). Hindi ito nangangailangan ng build file o database, at sumusunod ang teksto nito sa wika ng browser (English para sa mga hindi suportadong wika). Ang mga error page na ipinapadala mismo ng application (500, 503 habang `php artisan down`, at iba pa) ay dumadaan nang walang pagbabago dahil nananatili sa default (off) ang `fastcgi_intercept_errors`.
- **Reference number:** Inilalagay ng `ssi on` ang `$request_id` ng Nginx sa pahina. Ang parehong halaga ay ipinapasa sa application bilang `X-Request-Id`, kaya mahahanap ang Nginx access log (`rid=`), ang mga log ng application at ang mga audit record gamit ang iisang numero ([pag-troubleshoot](troubleshooting.md)). Pinapalitan ng halagang ito ang `X-Request-Id` na ipinadala ng client.
- **Kapag nagpo-proxy ang Nginx sa hiwalay na application server:** gamitin ang `proxy_set_header X-Request-Id $request_id;` at iwan sa default (off) ang `proxy_intercept_errors`. Panatilihin ang `error_page` at ang `location` nito.
- **Kapag binago ang teksto:** kapag in-edit mo ang `unavailable` at `reference` sa `lang/*/errors.php`, i-edit din ang JSON sa file na ito. Babagsak ang test (`ErrorPagesTest`) kapag hindi na magkatugma ang dalawa.
- **Bersyon ng Nginx:** Kailangan ng `http2 on;` ang Nginx 1.25.1 o mas bago. Sa mas lumang bersyon (halimbawa 1.18 sa Ubuntu 22.04), alisin ang linyang iyon at isulat sa halip ang `listen 443 ssl http2;`.
