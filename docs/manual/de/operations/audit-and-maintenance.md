# Integrität des Protokolls und regelmäßige Wartung

## Hash-Kette des Protokolls

Die Protokolltabellen (`audit.record_changes` für Datenänderungen, `audit.activity_log` für fachliche Aktivitäten, `audit.security_events` für Sicherheitsereignisse) erlauben nur das Anfügen (durch Trigger erzwungen). Darüber hinaus gibt es einen Mechanismus, der Manipulationen **sichtbar macht**.

- Jede Zeile erhält beim Einfügen einen SHA-256-Hash ihres Inhalts (`row_hash`).
- `audit:seal` (zeitgesteuert täglich um 03:30) verknüpft neue Zeilen in ID-Reihenfolge mit der Kette: `chain_hash = SHA-256(previous chain_hash || row_hash)`.
- `audit:verify-chain` berechnet für jede Zeile den Inhalts-Hash und die Kette neu. Eine Zeile mit geändertem Inhalt wird als „row mismatch“ gemeldet, eine fehlende oder umsortierte Zeile als „chain mismatch“, und der Befehl liefert den Exit-Code 2.

Zu beachten

- Eine vor dem Versiegeln (innerhalb desselben Tages) geänderte Zeile kann die Kette nicht erkennen. Deshalb wird täglich versiegelt und vor Sicherungen sowie bei Sicherheitsüberprüfungen verifiziert.
- Entfernt die Bereinigung (`audit:prune`) die ältesten Zeilen, wird die Verknüpfung der ersten verbleibenden Zeile nicht geprüft (der bereinigte Bereich wird im Protokolleintrag des Bereinigungsbefehls festgehalten).
- Nur die DB-Administration (Schema-Eigentümer) kann `app.audit_maintenance = on` setzen. Die Anwendungsrolle hat keine UPDATE- oder DELETE-Rechte.

## Aufbewahrung und Bereinigung

1. Legen Sie die Aufbewahrungsfristen fest (zum Beispiel Datenänderungen 3 Jahre, fachliche Aktivitäten 5 Jahre, Sicherheitsereignisse 1 Jahr).
2. Erstellen Sie vor dem Bereinigen eine Sicherung ([Sicherung und Wiederherstellung](backup-restore.md)) und vergewissern Sie sich mit `audit:verify-chain`, dass die Kette intakt ist.
3. Prüfen Sie die Anzahl mit `audit:prune --before=YYYY-MM-DD --table=record_changes --dry-run` und führen Sie den Befehl dann ohne `--dry-run` aus. Nicht versiegelte Zeilen werden nie gelöscht; führen Sie daher zuerst `audit:seal` aus.
4. Führen Sie nach dem Bereinigen erneut `audit:verify-chain` aus.

Die Bereinigung selbst wird aufgezeichnet: Jeder Lauf schreibt Stichtag, Optionen, Akteur und Betriebssystembenutzer in das Aktivitätsprotokoll (Log `command`), dazu je Tabelle die **Anzahl der gelöschten Zeilen, den Bereich der gelöschten IDs und den chain_hash an der Grenze**; eine tatsächliche Löschung wird zusätzlich als Sicherheitsereignis `audit.pruned` aufgezeichnet (**Protokoll > Sicherheitsereignisse**). Diese Eintragszeile wird beim nächsten Versiegeln ihrerseits mit der Kette verknüpft. Genau wegen dieser Grenze prüft `audit:verify-chain` die Verknüpfung der ersten verbleibenden Zeile nicht; durch den Vergleich des Grenz-Hashes mit dem Bereinigungseintrag können Sie bestätigen, dass der gelöschte Bereich einer aufgezeichneten Bereinigung entspricht.

## Bereinigung von Dateien

`media:prune-orphans --dry-run` listet Medienzeilen, deren Eigentümer nicht mehr existiert, und Verzeichnisse ohne Zeile auf; ohne `--dry-run` werden sie gelöscht. Erfasst werden Personenfotos, Bewerbungsunterlagen, Bescheinigungs-PDFs und Prüfungsnachweise. Führen Sie den Befehl monatlich oder nach einer Massenlöschung aus.

## Systemprüfung

`system:health-check` prüft Konfiguration und Schlüssel, DB-Rollen und RLS, Migrationen, den Berechtigungskatalog, Redis und die Queue (Horizon), Gotenberg, den Speicher und den Heartbeat des Schedulers (wird jede Minute in den Cache geschrieben) und liefert den Exit-Code 2, wenn etwas nicht stimmt. Mit `--json` binden Sie ihn an das Monitoring an.
