# Установка

- Для кого: ИТ-специалисты по эксплуатации
- См. также: [Справочник команд](commands.md), [Управление ключами](security-keys.md)

## 1. Требования

| Компонент | Версия | Примечания |
|---|---|---|
| PHP | 8.5 | Расширения: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | Расширения `btree_gist` и `pg_trgm` (устанавливаются миграциями), правила сортировки ICU |
| Redis | 8 | Кеш, сессии, очереди |
| Gotenberg | 8 | Формирование PDF. Доступен только во внутренней сети |
| Почтовый сервер | SMTP | Сброс паролей, двухфакторные коды по эл. почте |
| Обработчик очереди | Horizon | Держите `php artisan horizon` запущенным под менеджером процессов (systemd, supervisor) |
| Планировщик | cron | `* * * * * php artisan schedule:run` |

## 2. Подготовка базы данных

Выполняется один раз от имени суперпользователя БД. Приложение никогда не использует суперпользователя.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| Роль | Переменные окружения | Назначение |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | Работа приложения |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | Только миграции |
| `app_reporting` | (настраивается в BI-инструменте) | Чтение схемы `reporting` |

## 3. Конфигурация окружения

Скопируйте `.env.example` и заполните значения. Значения, которые необходимо изменить

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`. **При их потере зашифрованные персональные данные восстановить невозможно.** Храните их отдельно, как описано в документе [Управление ключами](security-keys.md).
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: часовой пояс учебного заведения по умолчанию при первой установке

## 4. Установка

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` работает в режиме вопросов и ответов. В сценариях развёртывания запускайте её с `--force` и параметрами ([Справочник команд](commands.md#setupinstall)).

Профиль установки (`--profile=`): `korean-university` — первый/второй/летний и зимний семестры, шкала оценок 4.5, категории дисциплин, 50-минутные пары начиная с 09:00, назначения аудиторий. Без профиля введите эти данные в панели администрирования. При повторном применении профиля значения, изменённые администраторами, сохраняются.

После установки ответственный администратор вводит справочные данные согласно [главе 5 руководства администратора — порядок начальной настройки](../admin/05-institution-setup.md).

## 5. Проверка установки

1. Войдите на `/login` под учётной записью первого администратора.
2. Задайте политику двухфакторной аутентификации в разделе **Настройки > Безопасность** панели администрирования.
3. Подключите двухфакторную аутентификацию для самого администратора (раздел портала **Безопасность**).
4. Проверьте службу PDF командой `php artisan pdf:sample`.
5. Убедитесь в разделе **Аудит > Изменения данных** панели администрирования, что установка записана как `system:command:setup:install`.

## 6. Обновление

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```

## 7. Веб-сервер (Nginx)

Поставьте Nginx перед php-fpm. Ниже приведён пример: измените адрес, пути, сертификаты и сокет php-fpm под свой сервер.

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

- **Страница ошибки:** `public/errors/unavailable.html` — статическая страница, которую Nginx показывает, когда PHP не отвечает (502, 503, 504). Ей не нужны файлы сборки и база данных, а текст выбирается по языку браузера (для неподдерживаемых языков — английский). Страницы ошибок, которые отправляет само приложение (500, 503 во время `php artisan down` и т. п.), проходят без изменений, потому что `fastcgi_intercept_errors` остаётся в значении по умолчанию (off).
- **Номер для справки:** `ssi on` вставляет в страницу `$request_id` Nginx. То же значение передаётся приложению как `X-Request-Id`, поэтому журнал доступа Nginx (`rid=`), логи приложения и записи аудита ищутся по одному номеру ([устранение неполадок](troubleshooting.md)). `X-Request-Id`, присланный клиентом, заменяется этим значением.
- **Если Nginx проксирует на отдельный сервер приложения:** используйте `proxy_set_header X-Request-Id $request_id;` и оставьте `proxy_intercept_errors` в значении по умолчанию (off). `error_page` и её `location` оставьте как есть.
- **При изменении текста:** если вы правите `unavailable` и `reference` в `lang/*/errors.php`, поправьте и JSON в этом файле. Тест (`ErrorPagesTest`) падает, если они расходятся.
