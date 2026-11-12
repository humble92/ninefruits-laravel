# Fehlerbehebung

- Zielgruppe: IT-Betrieb

| Symptom | Was zu prüfen ist |
|---|---|
| Direkt nach der Anmeldung Weiterleitung auf die Sicherheitsseite | Die Zwei-Faktor-Richtlinie steht auf „erforderlich“, und die Übergangsfrist ist abgelaufen. Erledigt sich, sobald der Benutzer die Zwei-Faktor-Authentifizierung einrichtet. Die Richtlinie befindet sich unter **Einstellungen > Sicherheit** |
| 403 in der Verwaltungsoberfläche | Der Benutzer hat keine gültige Rollenzuweisung mit der Berechtigung `admin_panel.access`. Prüfen Sie **Benutzer und Zugriff > Benutzer > Rollenzuweisungen** |
| Ein Menüpunkt fehlt | Der Rolle fehlt die Berechtigung für diese Seite (zum Beispiel `roles.view`) |
| E-Mail-Codes kommen nicht an | Mail-Einstellungen (`MAIL_*`), die Einträge `E-Mail-Code gesendet` unter den Sicherheitsereignissen, die Versandgrenzen (einmal pro Minute, fünfmal pro Stunde) |
| Eine Seite zeigt 0 Zeilen mit Daten anderer Personen | Kann normal sein (Row Level Security). Vergewissern Sie sich anhand der Benutzerspalte in **Datenänderungen**, dass der Akteur der DB-Sitzung gesetzt ist |
| `permission denied for schema public` | Die Anwendung hat als `app_runtime` DDL versucht. Führen Sie Migrationen nur mit `php artisan migrate` aus (das automatisch `app_migrator` verwendet) |
| `Audit records are append-only` | Ein Versuch, eine Protokolltabelle zu ändern oder daraus zu löschen. Diese Sperre ist beabsichtigt |
| PDF-Erzeugung schlägt fehl | `php artisan pdf:sample`, der Zustand von Gotenberg, `GOTENBERG_URL` |
| Queue-Jobs bleiben hängen | Fehlgeschlagene Jobs unter `/horizon` prüfen, `php artisan horizon:status` |

Nachverfolgung einzelner Requests: Mit dem Wert des Response-Headers `X-Request-Id` können Sie die Logs, das Aktivitätsprotokoll, die Datenänderungen und die Sicherheitsereignisse gemeinsam durchsuchen.
