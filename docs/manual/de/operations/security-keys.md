# Schlüsselverwaltung

- Zielgruppe: IT-Betrieb

| Schlüssel | Zweck | Bei Verlust | Wechsel |
|---|---|---|---|
| `APP_KEY` | Sitzungen, Cookies, Zwei-Faktor-Geheimnisse, Signatur der Cookies gemerkter Geräte | Alle Benutzer werden abgemeldet; eingerichtete Zwei-Faktor-Authentifizierung (Authenticator-Apps) funktioniert nicht mehr | Den bisherigen Schlüssel in `APP_PREVIOUS_KEYS` eintragen, dann wechseln |
| `PII_ENCRYPTION_KEY` | Verschlüsselung personenbezogener Daten wie Ausweisnummern | **Verschlüsselte personenbezogene Daten lassen sich nicht wiederherstellen** | Verfahren siehe unten |
| `PII_HASH_KEY` | Hashes für die Suche nach exakter Übereinstimmung in personenbezogenen Daten | Die Suche funktioniert nicht mehr (die Daten bleiben erhalten) | Wird grundsätzlich nicht gewechselt |

Grundsätze der Aufbewahrung

- Bewahren Sie mindestens zwei Kopien der drei Schlüssel außerhalb der Umgebungskonfiguration des Servers auf, in einem Secret Store (Vault, ein Secret Manager eines Cloud-Anbieters usw.) oder versiegelt offline.
- Bewahren Sie sie nie zusammen mit den Sicherungsdateien auf. Selbst wenn eine DB-Sicherung abfließt, müssen die personenbezogenen Daten ohne die Schlüssel unlesbar bleiben.

## Verfahren zum Wechsel des Schlüssels für personenbezogene Daten

1. Erzeugen Sie mit `php artisan pii:generate-key` einen neuen Schlüssel (verwenden Sie aus der Ausgabe nur `PII_ENCRYPTION_KEY`).
2. Tragen Sie den aktuellen Schlüssel in `PII_PREVIOUS_KEYS` ein (bei mehreren durch Kommas getrennt) und den neuen Schlüssel in `PII_ENCRYPTION_KEY`.
3. Laden Sie die Konfiguration neu: `php artisan config:cache`, `php artisan horizon:terminate`.
4. Prüfen Sie für jedes Modell mit personenbezogenen Daten die Anzahl der betroffenen Datensätze mit `php artisan pii:reencrypt {model} --dry-run` und führen Sie den Befehl dann ohne `--force` aus.
5. Sobald Horizon den Batch als beendet anzeigt und `--dry-run` 0 meldet, entfernen Sie den bisherigen Schlüssel aus `PII_PREVIOUS_KEYS`.
6. Bewahren Sie den bisherigen Schlüssel für die Dauer der Aufbewahrungsfrist der Sicherungen versiegelt auf (er wird zur Wiederherstellung älterer Sicherungen benötigt).
