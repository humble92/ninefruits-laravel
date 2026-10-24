# Backup and restore

- Audience: IT operators

## What to back up

| Target | Method | Frequency (recommended) |
|---|---|---|
| PostgreSQL | `pg_dump -Fc` or PITR (WAL archiving) | Daily full + continuous WAL |
| File storage (`storage/app/private` or S3) | Snapshot or versioned bucket | Same as the DB |
| Environment configuration and keys | Secret store | On every change |

The DB and the file storage must be restorable to the same point in time (metadata for photos and certificate PDFs lives in the DB).

## DB backup example

```bash
pg_dump -U app_migrator -d sis -Fc -f sis-$(date +%F).dump
```

`app_migrator` owns every schema (`public`, `audit`, `reporting`, `util`), so back up with this account. The roles (`app_*`) are cluster objects and are not part of the dump. On the restore server, create the roles first as described in [Installation](installation.md) section 2.

## Restore example

```bash
createdb -U postgres sis_restore
psql -U postgres -d sis_restore -f database/sql/roles/prepare_database.sql
pg_restore -U app_migrator -d sis_restore --no-owner --role=app_migrator sis-2026-10-22.dump
```

## Restore drills

Every quarter, restore to a separate server and verify the following: signing in, decryption of personal data (keys match), viewing audit records, opening files (photos, PDFs).
