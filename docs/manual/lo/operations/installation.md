# ການຕິດຕັ້ງ

- ກຸ່ມເປົ້າໝາຍ: ຜູ້ປະຕິບັດງານ IT
- ກ່ຽວຂ້ອງ: [ເອກະສານອ້າງອີງຄຳສັ່ງ](commands.md), [ການຄຸ້ມຄອງກະແຈ](security-keys.md)

## 1. ຄວາມຕ້ອງການ

| ອົງປະກອບ | ເວີຊັນ | ໝາຍເຫດ |
|---|---|---|
| PHP | 8.5 | ສ່ວນຂະຫຍາຍ: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | ສ່ວນຂະຫຍາຍ `btree_gist`, `pg_trgm` (migration ຕິດຕັ້ງໃຫ້), ICU collation |
| Redis | 8 | cache, ເຊສຊັນ, ຄິວ |
| Gotenberg | 8 | ສ້າງ PDF. ເປີດເຜີຍສະເພາະໃນເຄືອຂ່າຍພາຍໃນ |
| ເຊີບເວີເມວ | SMTP | ການຕັ້ງລະຫັດຜ່ານໃໝ່, ລະຫັດອີເມວສຳລັບການຢືນຢັນສອງຂັ້ນຕອນ |
| ຕົວປະມວນຜົນຄິວ | Horizon | ແລ່ນ `php artisan horizon` ຕະຫຼອດເວລາດ້ວຍຕົວຈັດການໂປຣເຊສ (systemd, supervisor) |
| ຕົວກຳນົດເວລາ | cron | `* * * * * php artisan schedule:run` |

## 2. ການກຽມຖານຂໍ້ມູນ

ຜູ້ໃຊ້ລະດັບສູງສຸດ (superuser) ຂອງ DB ແລ່ນຄັ້ງດຽວ. ແອັບພລິເຄຊັນບໍ່ໃຊ້ superuser.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| ບົດບາດ | ຕົວແປສະພາບແວດລ້ອມ | ການນຳໃຊ້ |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | ການແລ່ນແອັບພລິເຄຊັນ |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | migration ເທົ່ານັ້ນ |
| `app_reporting` | (ຕັ້ງຄ່າໃນເຄື່ອງມື BI) | ອ່ານ schema `reporting` |

## 3. ການຕັ້ງຄ່າສະພາບແວດລ້ອມ

ສຳເນົາ `.env.example` ແລ້ວຕື່ມຄ່າ. ຄ່າທີ່ຕ້ອງປ່ຽນແນ່ນອນ

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`. **ຖ້າສູນເສຍ ຈະກູ້ຄືນຂໍ້ມູນສ່ວນບຸກຄົນທີ່ເຂົ້າລະຫັດໄວ້ບໍ່ໄດ້.** ເກັບຮັກສາແຍກຕ່າງຫາກຕາມ [ການຄຸ້ມຄອງກະແຈ](security-keys.md).
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: ຄ່າເລີ່ມຕົ້ນຂອງເຂດເວລາສະຖາບັນເມື່ອຕິດຕັ້ງຄັ້ງທຳອິດ

## 4. ການຕິດຕັ້ງ

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` ດຳເນີນໄປໂດຍຕອບຄຳຖາມ. ໃນສະຄຣິບນຳໃຊ້ລະບົບ ໃຫ້ແລ່ນດ້ວຍ `--force` ແລະ ຕົວເລືອກ ([ເອກະສານອ້າງອີງຄຳສັ່ງ](commands.md#setupinstall)).

ໂປຣໄຟລ໌ການຕິດຕັ້ງ (`--profile=`): `korean-university` — ພາກຮຽນ 1/ພາກຮຽນ 2/ພາກຮຽນລະດູການ, ມາດຕາເກຣດ 4.5, ປະເພດວິຊາ, ຄາບຮຽນ 50 ນາທີເລີ່ມແຕ່ 09:00, ປະເພດການນຳໃຊ້ຫ້ອງຮຽນ. ຖ້າບໍ່ນຳໃຊ້ ໃຫ້ປ້ອນເອງໃນໜ້າບໍລິຫານ. ເຖິງວ່າຈະນຳໃຊ້ຊ້ຳໃນພາຍຫຼັງ ຄ່າທີ່ຜູ້ບໍລິຫານປ່ຽນແລ້ວກໍຍັງຖືກຮັກສາໄວ້.

ຫຼັງຕິດຕັ້ງ ຜູ້ບໍລິຫານທາງທຸລະກິດປ້ອນຂໍ້ມູນອ້າງອີງຕາມ [ຄູ່ມືຜູ້ບໍລິຫານ ບົດທີ 5 ລຳດັບການຕັ້ງຄ່າເບື້ອງຕົ້ນ](../admin/05-institution-setup.md).

## 5. ການກວດຫຼັງຕິດຕັ້ງ

1. ເຂົ້າສູ່ລະບົບທີ່ `/login` ດ້ວຍຜູ້ບໍລິຫານຄົນທຳອິດ.
2. ກຳນົດນະໂຍບາຍການຢືນຢັນສອງຂັ້ນຕອນທີ່ **ການຕັ້ງຄ່າ > ຄວາມປອດໄພ** ໃນໜ້າບໍລິຫານ.
3. ລົງທະບຽນການຢືນຢັນສອງຂັ້ນຕອນຂອງຜູ້ບໍລິຫານເອງ (**ຄວາມປອດໄພ** ໃນພອດທໍ).
4. ກວດບໍລິການ PDF ດ້ວຍ `php artisan pdf:sample`.
5. ກວດທີ່ **ການກວດສອບ > ບັນທຶກການປ່ຽນແປງຂໍ້ມູນ** ໃນໜ້າບໍລິຫານວ່າການຕິດຕັ້ງຖືກບັນທຶກເປັນ `system:command:setup:install` ຫຼືບໍ່.

## 6. ການອັບເດດ

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migration, ຂໍ້ມູນຈຳເປັນ, ຊິງສິດ
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # worker ເລີ່ມໃໝ່ດ້ວຍໂຄດໃໝ່
php artisan up
```
