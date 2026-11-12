# Sicherung und Wiederherstellung

- Zielgruppe: IT-Betrieb

## Was gesichert wird

| Ziel | Methode | Häufigkeit (empfohlen) |
|---|---|---|
| PostgreSQL | `pg_dump -Fc` oder PITR (WAL-Archivierung) | Täglich vollständig + fortlaufend WAL |
| Dateispeicher (`storage/app/private` oder S3) | Snapshot oder versionierter Bucket | Wie die DB |
| Umgebungskonfiguration und Schlüssel | Secret Store | Bei jeder Änderung |

DB und Dateispeicher müssen auf denselben Zeitpunkt wiederherstellbar sein (die Metadaten zu Fotos und Bescheinigungs-PDFs liegen in der DB).

## Beispiel für eine DB-Sicherung

```bash
pg_dump -U app_migrator -d sis -Fc -f sis-$(date +%F).dump
```

`app_migrator` ist Eigentümer aller Schemas (`public`, `audit`, `reporting`, `util`); sichern Sie daher mit diesem Konto. Die Rollen (`app_*`) sind Cluster-Objekte und nicht Teil des Dumps. Legen Sie auf dem Wiederherstellungsserver zuerst die Rollen an, wie in [Installation](installation.md) Abschnitt 2 beschrieben.

## Beispiel für eine Wiederherstellung

```bash
createdb -U postgres sis_restore
psql -U postgres -d sis_restore -f database/sql/roles/prepare_database.sql
pg_restore -U app_migrator -d sis_restore --no-owner --role=app_migrator sis-2026-10-22.dump
```

## Wiederherstellungsübungen

Stellen Sie jedes Quartal auf einem separaten Server wieder her und überprüfen Sie Folgendes: Anmeldung, Entschlüsselung personenbezogener Daten (Schlüssel passen), Anzeige von Protokolleinträgen, Öffnen von Dateien (Fotos, PDFs).
