# Sandaran dan pemulihan

- Pembaca: pengendali IT

## Perkara yang perlu disandarkan

| Sasaran | Kaedah | Kekerapan (disyorkan) |
|---|---|---|
| PostgreSQL | `pg_dump -Fc` atau PITR (pengarkiban WAL) | Penuh setiap hari + WAL berterusan |
| Storan fail (`storage/app/private` atau S3) | Snapshot atau bucket berversi | Sama seperti pangkalan data |
| Konfigurasi persekitaran dan kunci | Stor rahsia | Pada setiap perubahan |

Pangkalan data dan storan fail mesti boleh dipulihkan ke titik masa yang sama (metadata bagi foto dan PDF sijil disimpan dalam pangkalan data).

## Contoh sandaran pangkalan data

```bash
pg_dump -U app_migrator -d sis -Fc -f sis-$(date +%F).dump
```

`app_migrator` memiliki setiap skema (`public`, `audit`, `reporting`, `util`), jadi buat sandaran dengan akaun ini. Peranan (`app_*`) ialah objek kluster dan tidak termasuk dalam dump. Pada pelayan pemulihan, cipta peranan terlebih dahulu seperti yang diterangkan dalam [Pemasangan](installation.md) bahagian 2.

## Contoh pemulihan

```bash
createdb -U postgres sis_restore
psql -U postgres -d sis_restore -f database/sql/roles/prepare_database.sql
pg_restore -U app_migrator -d sis_restore --no-owner --role=app_migrator sis-2026-10-22.dump
```

## Latihan pemulihan

Setiap suku tahun, pulihkan ke pelayan berasingan dan sahkan perkara berikut: log masuk, penyahsulitan data peribadi (kunci sepadan), paparan rekod audit, pembukaan fail (foto, PDF).
