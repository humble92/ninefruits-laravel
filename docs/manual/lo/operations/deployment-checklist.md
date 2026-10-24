# ລາຍການກວດສອບການນຳໃຊ້ລະບົບ ແລະ ການຕັ້ງຄ່າຄວາມປອດໄພ PHP

## ເວີຊັນ PHP

- ໃຊ້ **PHP ≥ 8.5.12** (ຫຼື 8.4.27, 8.3.36). ການປ່ອຍເວີຊັນຄວາມປອດໄພວັນທີ 2026-10-21 ໄດ້ແກ້ໄຂຊ່ອງໂຫວ່ການແລ່ນໂຄດຈາກໄລຍະໄກໂດຍບໍ່ຕ້ອງຢືນຢັນຕົວຕົນ 2 ລາຍການ (CVE-2026-109618 ສະຖານະເສຍຫາຍຈາກ OOM, CVE-2026-109619 ສະຖານະຄ້າງລະຫວ່າງຄຳຮ້ອງຂໍ) ແລະ php.net ລະບຸວ່າ "ບໍ່ມີມາດຕະການບັນເທົາທີ່ສົມບູນ ມີພຽງການອັບເດດເທົ່ານັ້ນທີ່ປອດໄພ". `"php": "^8.5.12"` ໃນ `composer.json` ຈະຂັດຂວາງການຕິດຕັ້ງໃນ PHP ທີ່ຕ່ຳກວ່ານີ້.
- ສະພາບແວດລ້ອມພັດທະນາ: ແລ່ນ `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d` ແລ້ວກວດດ້ວຍ `./vendor/bin/sail php -v`.
- ເມື່ອສ້າງ image ຂອງ container ຫຼື ເຊີບເວີ ຢ່າຕຶງແພັກເກັດ PHP ໄວ້ ແຕ່ໃຫ້ຮັບການປ່ອຍ patch ລ່າສຸດ. `system:health-check` ສະແດງເວີຊັນ PHP ດ້ວຍ.

## php.ini ສຳລັບການນຳໃຊ້ຈິງ (pool ຂອງ php-fpm ຫຼື ການຕັ້ງຄ່າເຊີບເວີ ບໍ່ແມ່ນ `.user.ini` ຫຼື `ini_set()`)

| ການຕັ້ງຄ່າ | ຄ່າ | ເຫດຜົນ |
|---|---|---|
| `session.upload_progress.enabled` | `0` | ປິດເສັ້ນທາງຂອງ CVE-2026-109619 ແລະ CVE-2026-115560 (ຄຳແນະນຳຂອງ php.net 2026-10-21 ແລະ 23). ລະບົບນີ້ບໍ່ໃຊ້ການສະແດງຄວາມຄືບໜ້າການອັບໂຫຼດ |
| `fatal_error_backtraces` | `0` (PHP 8.5) | ປິດເສັ້ນທາງ backtrace ຂອງຂໍ້ຜິດພາດຮ້າຍແຮງຕາມຄຳແນະນຳດຽວກັນ |
| `memory_limit` | `256M` ຂຶ້ນໄປ ຕຶງໄວ້ດ້ວຍ `php_admin_value` | ເພື່ອບໍ່ໃຫ້ໂຄດຂອງແອັບປ່ຽນໄດ້ |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | ເພດານການອັບໂຫຼດເອກະສານສະໝັກເຂົ້າຮຽນ, ຫຼັກຖານການສອບເສັງ ແລະ ຮູບຖ່າຍ (ມີຊ່ອງຫວ່າງຫຼາຍກວ່າການກວດຂອງແອັບທີ່ 5MB ແລະ 2MB) |
| `expose_php` | `Off` | ປ້ອງກັນການເປີດເຜີຍເວີຊັນ |
| `display_errors` | `Off`, `log_errors=On` | ການນຳໃຊ້ຈິງ |
| `session.cookie_secure`, `session.cookie_httponly`, `session.cookie_samesite` | `1`, `1`, `Lax` | ຄວບຄູ່ກັບການຕັ້ງຄ່າເຊສຊັນຂອງ Laravel (`SESSION_SECURE_COOKIE=true`) |
| `opcache.enable`, `opcache.validate_timestamps` | `1`, `0` (ເລີ່ມໃໝ່ເມື່ອນຳໃຊ້) | ປະສິດທິພາບ |

## ສະພາບແວດລ້ອມຂອງແອັບພລິເຄຊັນ (.env)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` ເປັນທີ່ຢູ່ຕົວຈິງ.
- `APP_KEY`, `PII_ENCRYPTION_KEY`, `PII_HASH_KEY` ຕ້ອງຖືກສີດເຂົ້າຈາກບ່ອນເກັບຄວາມລັບ ແລະ ສຳຮອງໄວ້ ([ກະແຈຄວາມປອດໄພ](security-keys.md)). ຖ້າສູນເສຍກະແຈ ຈະຖອດລະຫັດຂໍ້ມູນສ່ວນບຸກຄົນບໍ່ໄດ້.
- DB: `DB_USERNAME=app_runtime`, `DB_MIGRATOR_USERNAME=app_migrator` (ລະຫັດຜ່ານທີ່ໃຊ້ສະເພາະເວລາ migration), `app_reporting` ສຳລັບເຄື່ອງມື BI ເທົ່ານັ້ນ.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`. ລົງທະບຽນ Horizon (`php artisan horizon`) ແລະ ຕົວກຳນົດເວລາ (`* * * * * php artisan schedule:run`) ເປັນ service.
- `GOTENBERG_URL`: ທີ່ຢູ່ໃນເຄືອຂ່າຍພາຍໃນ. ບໍ່ເປີດເຜີຍ Gotenberg ສູ່ພາຍນອກ.
- ເມວ (`MAIL_*`): ໃຊ້ສຳລັບການເຊີນ, ເມວການຢືນຢັນສອງຂັ້ນຕອນ ແລະ ການແຈ້ງເຕືອນການລົງທະບຽນຮຽນ ແລະ ເກຣດ.

## ຂັ້ນຕອນການນຳໃຊ້ລະບົບ

1. ສຳຮອງຂໍ້ມູນ ([ການສຳຮອງ ແລະ ກູ້ຄືນຂໍ້ມູນ](backup-restore.md)).
2. ນຳໃຊ້ໂຄດ, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` (ໃຊ້ການເຊື່ອມຕໍ່ migrator ອັດຕະໂນມັດ), `php artisan security:sync-permissions`, `php artisan setup:install --force` (ອັບເດດຂໍ້ມູນຈຳເປັນ ໂດຍຮັກສາການແກ້ໄຂຂອງຜູ້ບໍລິຫານ).
4. `php artisan optimize`, ເລີ່ມ Horizon ໃໝ່ (`horizon:terminate`), ເລີ່ມ php-fpm ໃໝ່ (ນຳໃຊ້ php.ini ໃໝ່).
5. ກວດດ້ວຍ `php artisan system:health-check`. ກວດຕ່ອງໂສ້ບັນທຶກການກວດສອບດ້ວຍ `audit:verify-chain`.

## ການກວດກາຕາມກຳນົດ

| ຄວາມຖີ່ | ສິ່ງທີ່ຕ້ອງເຮັດ |
|---|---|
| ທຸກວັນ (ອັດຕະໂນມັດ) | `audit:seal`, `lms:reconcile`, ປ່ອຍບ່ອນນັ່ງທີ່ຈອງໄວ້, ເລື່ອນຂຶ້ນຈາກລາຍຊື່ລໍຖ້າ, ໝົດອາຍຸໃບສະໝັກທີ່ຍັງຮ່າງຢູ່ |
| ທຸກອາທິດ | `system:health-check`, ກວດການອັບເດດຄວາມປອດໄພຂອງ PHP ແລະ OS, ກວດວຽກທີ່ລົ້ມເຫຼວໃນ Horizon |
| ທຸກເດືອນ | `media:prune-orphans --dry-run`, ຝຶກຊ້ອມກູ້ຄືນຈາກການສຳຮອງຂໍ້ມູນ, `audit:verify-chain` |
| ທຸກພາກຮຽນ | ກວດການມອບບົດບາດ (ຜູ້ທີ່ລາອອກ ຫຼື ຍ້າຍ), ລ້າງບັນທຶກການກວດສອບທີ່ເກີນໄລຍະເກັບຮັກສາ (`audit:prune`) |
