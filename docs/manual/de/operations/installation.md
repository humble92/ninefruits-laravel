# Installation

- Zielgruppe: IT-Betrieb
- Siehe auch: [Befehlsreferenz](commands.md), [Schlüsselverwaltung](security-keys.md)

## 1. Voraussetzungen

| Komponente | Version | Hinweise |
|---|---|---|
| PHP | 8.5 | Erweiterungen: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | Erweiterungen `btree_gist` und `pg_trgm` (werden von den Migrationen installiert), ICU-Kollation |
| Redis | 8 | Cache, Sitzungen, Queues |
| Gotenberg | 8 | PDF-Erzeugung. Nur im internen Netz erreichbar |
| Mailserver | SMTP | Zurücksetzen von Passwörtern, Zwei-Faktor-Codes per E-Mail |
| Queue-Worker | Horizon | `php artisan horizon` dauerhaft unter einem Prozessmanager (systemd, supervisor) laufen lassen |
| Scheduler | cron | `* * * * * php artisan schedule:run` |

## 2. Vorbereitung der Datenbank

Einmalig als DB-Superuser ausführen. Die Anwendung verwendet den Superuser nie.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| Rolle | Umgebungsvariablen | Zweck |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | Betrieb der Anwendung |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | Nur Migrationen |
| `app_reporting` | (im BI-Werkzeug konfiguriert) | Lesen des Schemas `reporting` |

## 3. Umgebungskonfiguration

Kopieren Sie `.env.example` und tragen Sie die Werte ein. Folgende Werte müssen geändert werden

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`. **Gehen sie verloren, lassen sich verschlüsselte personenbezogene Daten nicht wiederherstellen.** Bewahren Sie sie gesondert auf, wie in [Schlüsselverwaltung](security-keys.md) beschrieben.
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: die Standardzeitzone der Hochschule bei der Erstinstallation

## 4. Installation

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` läuft mit Rückfragen ab. In Deployment-Skripten führen Sie den Befehl mit `--force` und Optionen aus ([Befehlsreferenz](commands.md#setupinstall)).

Installationsprofil (`--profile=`): `korean-university` — erstes/zweites Semester sowie Sommer- und Winterkurse, Notenskala 4.5, Veranstaltungskategorien, 50-minütige Zeitblöcke ab 09:00 Uhr, Raumnutzungsarten. Ohne Profil geben Sie diese Werte in der Verwaltungsoberfläche ein. Wird ein Profil später erneut angewendet, bleiben die von Administratoren geänderten Werte erhalten.

Nach der Installation erfasst die fachliche Administration die Stammdaten gemäß [Administratorhandbuch Kapitel 5, Reihenfolge der Ersteinrichtung](../admin/05-institution-setup.md).

## 5. Überprüfung der Installation

1. Melden Sie sich unter `/login` als erster Administrator an.
2. Legen Sie die Richtlinie für die Zwei-Faktor-Authentifizierung unter **Einstellungen > Sicherheit** in der Verwaltungsoberfläche fest.
3. Richten Sie die eigene Zwei-Faktor-Authentifizierung des Administrators ein (Portal **Sicherheit**).
4. Prüfen Sie den PDF-Dienst mit `php artisan pdf:sample`.
5. Vergewissern Sie sich unter **Protokoll > Datenänderungen** in der Verwaltungsoberfläche, dass die Installation als `system:command:setup:install` aufgezeichnet wurde.

## 6. Aktualisierung

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```

## 7. Webserver (Nginx)

Stellen Sie Nginx vor php-fpm. Das Folgende ist ein Beispiel: Passen Sie Adresse, Pfade, Zertifikate und den php-fpm-Socket an Ihren Server an.

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

- **Fehlerseite:** `public/errors/unavailable.html` ist die statische Seite, die Nginx zeigt, wenn PHP nicht antwortet (502, 503, 504). Sie braucht keine Build-Dateien und keine Datenbank, und ihr Text folgt der Browsersprache (Englisch bei nicht unterstützten Sprachen). Fehlerseiten, die die Anwendung selbst sendet (500, 503 während `php artisan down` usw.), werden unverändert durchgereicht, weil `fastcgi_intercept_errors` auf dem Standardwert (off) bleibt.
- **Referenznummer:** `ssi on` setzt die `$request_id` von Nginx in die Seite ein. Derselbe Wert geht als `X-Request-Id` an die Anwendung, sodass Sie das Nginx-Zugriffslog (`rid=`), die Anwendungslogs und die Prüfprotokolle mit einer Nummer durchsuchen können ([Fehlerbehebung](troubleshooting.md)). Eine vom Client gesendete `X-Request-Id` wird durch diesen Wert ersetzt.
- **Wenn Nginx an einen separaten Anwendungsserver weiterleitet:** Verwenden Sie `proxy_set_header X-Request-Id $request_id;` und lassen Sie `proxy_intercept_errors` auf dem Standardwert (off). `error_page` und die zugehörige `location` bleiben unverändert.
- **Wenn Sie den Text ändern:** Ändern Sie `unavailable` und `reference` in `lang/*/errors.php`, passen Sie auch das JSON in dieser Datei an. Der Test (`ErrorPagesTest`) schlägt fehl, wenn beide auseinanderlaufen.
- **Nginx-Version:** `http2 on;` erfordert Nginx 1.25.1 oder neuer. Bei älteren Versionen (zum Beispiel 1.18 unter Ubuntu 22.04) entfernen Sie diese Zeile und schreiben stattdessen `listen 443 ssl http2;`.
