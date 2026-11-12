# Backup at restore

- Para kanino: mga IT operator

## Ano ang iba-back up

| Target | Paraan | Dalas (inirerekomenda) |
|---|---|---|
| PostgreSQL | `pg_dump -Fc` o PITR (WAL archiving) | Buong backup araw-araw + tuloy-tuloy na WAL |
| File storage (`storage/app/private` o S3) | Snapshot o bucket na may versioning | Kapareho ng DB |
| Configuration ng environment at mga key | Secret store | Sa bawat pagbabago |

Dapat maibalik ang DB at ang file storage sa iisang punto ng panahon (nasa DB ang metadata ng mga larawan at PDF ng sertipiko).

## Halimbawa ng backup ng DB

```bash
pg_dump -U app_migrator -d sis -Fc -f sis-$(date +%F).dump
```

Pag-aari ng `app_migrator` ang bawat schema (`public`, `audit`, `reporting`, `util`), kaya mag-back up gamit ang account na ito. Ang mga role (`app_*`) ay mga object ng cluster at hindi kasama sa dump. Sa server na pagre-restore-an, gawin muna ang mga role ayon sa seksiyon 2 ng [Pag-install](installation.md).

## Halimbawa ng restore

```bash
createdb -U postgres sis_restore
psql -U postgres -d sis_restore -f database/sql/roles/prepare_database.sql
pg_restore -U app_migrator -d sis_restore --no-owner --role=app_migrator sis-2026-10-22.dump
```

## Mga pagsasanay sa restore

Bawat quarter, mag-restore sa isang hiwalay na server at beripikahin ang sumusunod: pag-sign in, pag-decrypt ng personal na datos (tugma ang mga key), pagtingin sa mga rekord ng audit, pagbukas ng mga file (mga larawan, PDF).
