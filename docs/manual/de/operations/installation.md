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
