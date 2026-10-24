# ການສຳຮອງ ແລະ ກູ້ຄືນຂໍ້ມູນ

- ກຸ່ມເປົ້າໝາຍ: ຜູ້ປະຕິບັດງານ IT

## ສິ່ງທີ່ຕ້ອງສຳຮອງ

| ເປົ້າໝາຍ | ວິທີ | ຄວາມຖີ່ (ແນະນຳ) |
|---|---|---|
| PostgreSQL | `pg_dump -Fc` ຫຼື PITR (ເກັບ WAL) | ເຕັມທຸກວັນ + WAL ຕໍ່ເນື່ອງ |
| ບ່ອນເກັບໄຟລ໌ (`storage/app/private` ຫຼື S3) | snapshot ຫຼື bucket ທີ່ມີການຄຸ້ມຄອງເວີຊັນ | ຄວາມຖີ່ດຽວກັນກັບ DB |
| ການຕັ້ງຄ່າສະພາບແວດລ້ອມ ແລະ ກະແຈ | ບ່ອນເກັບຄວາມລັບ (secret store) | ເມື່ອມີການປ່ຽນແປງ |

DB ແລະ ບ່ອນເກັບໄຟລ໌ຕ້ອງສາມາດກູ້ຄືນໄດ້ຢູ່ຈຸດເວລາດຽວກັນ (ຂໍ້ມູນ metadata ຂອງຮູບຖ່າຍ ແລະ PDF ໃບຢັ້ງຢືນຢູ່ໃນ DB).

## ຕົວຢ່າງການສຳຮອງ DB

```bash
pg_dump -U app_migrator -d sis -Fc -f sis-$(date +%F).dump
```

`app_migrator` ເປັນເຈົ້າຂອງທຸກ schema (`public`, `audit`, `reporting`, `util`) ດັ່ງນັ້ນຈຶ່ງສຳຮອງຂໍ້ມູນດ້ວຍບັນຊີນີ້. ບົດບາດ (`app_*`) ເປັນວັດຖຸລະດັບ cluster ຈຶ່ງບໍ່ຢູ່ໃນໄຟລ໌ dump. ໃນເຊີບເວີທີ່ຈະກູ້ຄືນ ໃຫ້ສ້າງບົດບາດຕາມພາກທີ 2 ຂອງ [ການຕິດຕັ້ງ](installation.md) ກ່ອນ.

## ຕົວຢ່າງການກູ້ຄືນ

```bash
createdb -U postgres sis_restore
psql -U postgres -d sis_restore -f database/sql/roles/prepare_database.sql
pg_restore -U app_migrator -d sis_restore --no-owner --role=app_migrator sis-2026-10-22.dump
```

## ການຝຶກຊ້ອມກູ້ຄືນ

ທຸກໄຕມາດ ໃຫ້ກູ້ຄືນໃສ່ເຊີບເວີແຍກຕ່າງຫາກ ແລະ ກວດສິ່ງຕໍ່ໄປນີ້: ການເຂົ້າສູ່ລະບົບ, ການຖອດລະຫັດຂໍ້ມູນສ່ວນບຸກຄົນ (ກະແຈກົງກັນ), ການເບິ່ງບັນທຶກການກວດສອບ, ການເປີດໄຟລ໌ (ຮູບຖ່າຍ, PDF).
