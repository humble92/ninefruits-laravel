# Deployment-Checkliste und PHP-Sicherheitseinstellungen

## PHP-Version

- Verwenden Sie **PHP ≥ 8.5.12** (oder 8.4.27, 8.3.36). Die Sicherheitsversion vom 21.10.2026 hat zwei Schwachstellen behoben, die eine Remotecodeausführung ohne Authentifizierung ermöglichen (CVE-2026-109618 Zustandsbeschädigung bei Speichermangel (OOM), CVE-2026-109619 Zustandsübertragung zwischen Requests), und php.net erklärte: „Es gibt keine vollständige Abhilfe; ein Update ist die einzige sichere Option.“ Die Einschränkung `"php": "^8.5.12"` in `composer.json` verhindert die Installation auf einer älteren PHP-Version.
- Entwicklungsumgebung: Führen Sie `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d` aus und prüfen Sie anschließend mit `./vendor/bin/sail php -v`.
- Beim Bauen von Container- oder Server-Images das PHP-Paket nicht auf eine Version festlegen, sondern die neueste Patch-Version verwenden. `system:health-check` zeigt ebenfalls die PHP-Version an.

## php.ini für die Produktion (php-fpm-Pool oder Serverkonfiguration, nicht `.user.ini` oder `ini_set()`)

| Einstellung | Wert | Grund |
|---|---|---|
| `session.upload_progress.enabled` | `0` | Sperrt die Angriffswege von CVE-2026-109619 und CVE-2026-115560 (Sicherheitshinweise von php.net vom 21. und 23.10.2026). Dieses System verwendet keinen Upload-Fortschritt |
| `fatal_error_backtraces` | `0` (PHP 8.5) | Sperrt den Angriffsweg über Backtraces bei fatalen Fehlern aus demselben Sicherheitshinweis |
| `memory_limit` | `256M` oder mehr, mit `php_admin_value` festgelegt | Damit der Anwendungscode den Wert nicht ändern kann |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | Obergrenze für Bewerbungsunterlagen, Prüfungsnachweise und Foto-Uploads (mit Reserve über der Validierung der Anwendung von 5 MB bzw. 2 MB) |
| `expose_php` | `Off` | Version nicht preisgeben |
| `display_errors` | `Off`, `log_errors=On` | Produktion |
| `session.cookie_secure`, `session.cookie_httponly`, `session.cookie_samesite` | `1`, `1`, `Lax` | Zusammen mit den Sitzungseinstellungen von Laravel (`SESSION_SECURE_COOKIE=true`) |
| `opcache.enable`, `opcache.validate_timestamps` | `1`, `0` (Neustart beim Deployment) | Leistung |

## Anwendungsumgebung (.env)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` auf die tatsächliche Adresse gesetzt.
- `APP_KEY`, `PII_ENCRYPTION_KEY` und `PII_HASH_KEY` aus dem Secret Store einspielen und sichern ([Schlüsselverwaltung](security-keys.md)). Gehen die Schlüssel verloren, lassen sich personenbezogene Daten nicht mehr entschlüsseln.
- DB: `DB_USERNAME=app_runtime`, `DB_MIGRATOR_USERNAME=app_migrator` (ein ausschließlich für Migrationen verwendetes Passwort), `app_reporting` nur für BI-Werkzeuge.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`. Registrieren Sie Horizon (`php artisan horizon`) und den Scheduler (`* * * * * php artisan schedule:run`) als Dienste.
- `GOTENBERG_URL`: eine Adresse im internen Netz. Gotenberg wird nie nach außen freigegeben.
- Mail (`MAIL_*`): wird für Einladungen, Zwei-Faktor-E-Mails sowie Benachrichtigungen zu Belegung und Noten verwendet.

## Ablauf des Deployments

1. Sichern ([Sicherung und Wiederherstellung](backup-restore.md)).
2. Code ausrollen, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` (wechselt automatisch auf die Migrator-Verbindung), `php artisan security:sync-permissions`, `php artisan setup:install --force` (aktualisiert die Basisdaten, behält Änderungen der Administratoren bei).
4. `php artisan optimize`, Horizon neu starten (`horizon:terminate`), php-fpm neu starten (übernimmt die neue php.ini).
5. Mit `php artisan system:health-check` überprüfen. Die Protokollkette mit `audit:verify-chain` überprüfen.

## Regelmäßige Prüfungen

| Häufigkeit | Aufgabe |
|---|---|
| Täglich (automatisch) | `audit:seal`, `lms:reconcile`, Freigabe reservierter Belegungsplätze, Nachrücken von der Warteliste, Verfall von Bewerbungsentwürfen |
| Wöchentlich | `system:health-check`, PHP- und Betriebssystem-Sicherheitsupdates prüfen, fehlgeschlagene Jobs in Horizon prüfen |
| Monatlich | `media:prune-orphans --dry-run`, Wiederherstellungsübung aus der Sicherung, `audit:verify-chain` |
| Jedes Semester | Rollenzuweisungen überprüfen (ausgeschiedene oder versetzte Personen), Protokolleinträge nach Ablauf ihrer Aufbewahrungsfrist bereinigen (`audit:prune`) |
