# Zeitgesteuerte Jobs und Queues

- Zielgruppe: IT-Betrieb

## Dauerhaft laufende Prozesse

| Prozess | Befehl | Kontrolle |
|---|---|---|
| Queue-Worker | `php artisan horizon` | `/horizon` (Berechtigung `queue_monitor.view`) |
| Scheduler | cron `* * * * * php artisan schedule:run` | `php artisan schedule:list` |

## Zeitgesteuerte Jobs

Jede Phase ergänzt diese Tabelle. Die Registrierungen stehen in `routes/console.php`. Die Zeiten beziehen sich auf die Zeitzone des Servers (UTC).

| Job | Häufigkeit | Phase | Fehlererkennung |
|---|---|---|---|
| `admission:expire-drafts` | Täglich um 03:10 | 3 | Wird bei jedem Lauf im Aktivitätsprotokoll (Log `command`) aufgezeichnet. Fehlt ein Eintrag länger als einen Tag, prüfen Sie den Scheduler |
| `registration:release-reserved-seats` | Alle 5 Minuten | 5 | Kein Protokolleintrag. Haben Platzkontingente, deren Freigabezeitpunkt verstrichen ist, noch ein leeres `release_processed_at`, prüfen Sie den Scheduler |
| `waitlist:promote` | Stündlich | 5 | Wird bei jedem Lauf im Aktivitätsprotokoll (Log `command`) aufgezeichnet |

## Queue-Jobs

| Job | Ausgelöst durch | Hinweise |
|---|---|---|
| `ReencryptPiiRecords` | `pii:reencrypt` | Batch. Fehlgeschlagene Jobs in Horizon erneut ausführen |
| `PromoteWaitlistJob` | Abmeldung oder Rücktritt von einer Belegung, Freigabe eines Platzkontingents | Je Kurs läuft nur einer (Duplikate werden verhindert). Beim Nachrücken von der Warteliste werden alle Regeln erneut geprüft |
| `CloseTermRecordsJob`, `FinishTermCloseJob` | `term:close`, Semesterabschluss in der Verwaltungsoberfläche | Batches zu je 100 Studierenden, gefolgt vom Abschlussjob (Reihung der Auszeichnungen, Änderung des Semesterstatus). Fortschritt im Horizon-Batch `term-close:{Semestercode}` verfolgen. Fehlgeschlagene Jobs in Horizon erneut ausführen (die Berechnungen je Person liefern bei erneuter Ausführung dasselbe Ergebnis) |
| `ImportCsv` (Filament) | CSV-Import in der Verwaltungsoberfläche, `reference:import` | Batch. Der Abschluss wird als Benachrichtigung in der Verwaltungsoberfläche gemeldet (Tabelle `notifications`). Fehlgeschlagene Zeilen landen in `failed_import_rows` |
| `audit:seal` | Täglich um 03:30 | Versiegelt die Hash-Kette des Protokolls ([Integrität des Protokolls](audit-and-maintenance.md)) |
| Heartbeat des Schedulers | Jede Minute | Schreibt den Zeitpunkt des letzten Laufs in den Cache. `system:health-check` wertet mehr als 5 Minuten ohne Eintrag als Fehler |

Während ein Queue-Job läuft, wird als DB-Akteur `system:job:{class}` aufgezeichnet.
