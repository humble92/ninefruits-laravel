# 4. Protokoll

Die drei Bildschirme im Menü **Protokoll** sind schreibgeschützt; niemand kann ihre Einträge bearbeiten oder löschen (erfordert die Berechtigungen `Protokolleinträge anzeigen` und `Sicherheitsereignisse anzeigen`).

| Bildschirm | Inhalt | Beispiele |
|---|---|---|
| Aktivitätsprotokoll | Wer was getan hat und **warum**. Grund und Details | Rollenzuweisung (Grund: Vertretung im Semester), Berechtigungsänderung (Liste der hinzugefügten/entfernten), Einstellungsänderung (vorher/nachher), Befehlsausführung |
| Datenänderungen | Werte von Tabellenzeilen vor und nach der Änderung. Auch direkte Änderungen unter Umgehung der Anwendung werden erfasst | `users` UPDATE, geänderte Spalte `locale` |
| Sicherheitsereignisse | Anmeldungen, fehlgeschlagene Anmeldungen, Zwei-Faktor-Authentifizierung, berechtigungsbezogene Ereignisse, Passwort- und E-Mail-Änderungen, Bereinigung des Protokolls (`audit:prune`) | Anmeldung fehlgeschlagen (Anmeldename, IP) |

## Lesehilfe

- Spalte **Benutzer**: Aktionen auf dem Bildschirm zeigen den Benutzernamen oder `user:number`, automatische Jobs zeigen `system:job:...`, Befehle zeigen `system:command:...`.
- Passwörter, Zwei-Faktor-Geheimnisse und ähnliche Werte werden als `[redacted]` maskiert.
- Einträge mit derselben **Anfrage-ID** stammen aus einer einzigen Bildschirmanfrage. Wer über alle Bildschirme nach derselben Anfrage-ID sucht, sieht die vollständige Spur einer Aktion.

## Häufige Abfragen

| Gesucht | Vorgehen |
|---|---|
| Wann sich die Berechtigungen eines Benutzers geändert haben | Im Aktivitätsprotokoll Protokoll `security`, Ereignisse `role.assigned`, `role.assignment_revoked` |
| Ein Konto mit vielen fehlgeschlagenen Anmeldungen | In den Sicherheitsereignissen nach dem Ereignis `Anmeldung fehlgeschlagen` filtern, Spalte Anmeldename ansehen |
| Wer die Sicherheitsrichtlinie geändert hat | Aktivitätsprotokoll, Ereignis `settings.updated` |
| Wie sich eine bestimmte Zeile geändert hat | In den Datenänderungen nach Tabelle filtern und nach der Datensatz-ID suchen |
