# Байршуулалтын хяналтын жагсаалт ба PHP-ийн аюулгүй байдлын тохиргоо

## PHP хувилбар

- **PHP ≥ 8.5.12** (эсвэл 8.4.27, 8.3.36) ашиглана. 2026-10-21-ний аюулгүй байдлын хувилбар нэвтрэлтгүйгээр алсаас код ажиллуулах хоёр эмзэг байдлыг (CVE-2026-109618 OOM төлөвийн эвдрэл, CVE-2026-109619 хүсэлт хооронд төлөв алдагдах) зассан бөгөөд php.net "бүрэн хамгаалах арга байхгүй; шинэчлэх нь цорын ганц аюулгүй сонголт" гэж мэдэгдсэн. `composer.json` дахь `"php": "^8.5.12"` нөхцөл нь үүнээс доош PHP дээр суулгахаас сэргийлнэ.
- Хөгжүүлэлтийн орчин: `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d` ажиллуулаад `./vendor/bin/sail php -v`-ээр шалгана.
- Контейнер эсвэл серверийн image бүтээхдээ PHP багцыг тогтмол хувилбарт бэхлэхгүй, хамгийн сүүлийн patch хувилбарыг авна. `system:health-check` мөн PHP хувилбарыг харуулна.

## Production php.ini (`.user.ini` эсвэл `ini_set()` биш, php-fpm pool эсвэл серверийн тохиргоо)

| Тохиргоо | Утга | Шалтгаан |
|---|---|---|
| `session.upload_progress.enabled` | `0` | CVE-2026-109619 болон CVE-2026-115560-ын замыг хаана (php.net-ийн 2026-10-21 ба 23-ны зөвлөмж). Энэ систем upload progress ашигладаггүй |
| `fatal_error_backtraces` | `0` (PHP 8.5) | Мөн зөвлөмж дэх fatal error backtrace-ийн замыг хаана |
| `memory_limit` | `256M` буюу түүнээс дээш, `php_admin_value`-аар бэхэлнэ | Аппликейшны код өөрчлөх боломжгүй байхын тулд |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | Элсэлтийн баримт, шалгалтын нотлох баримт, зураг байршуулах дээд хязгаар (аппликейшны 5MB ба 2MB шалгалтаас илүү нөөцтэй) |
| `expose_php` | `Off` | Хувилбарыг ил гаргахгүй |
| `display_errors` | `Off`, `log_errors=On` | Production |
| `session.cookie_secure`, `session.cookie_httponly`, `session.cookie_samesite` | `1`, `1`, `Lax` | Laravel-ийн сессийн тохиргоотой (`SESSION_SECURE_COOKIE=true`) хамт |
| `opcache.enable`, `opcache.validate_timestamps` | `1`, `0` (байршуулах үед дахин ачаална) | Гүйцэтгэл |

## Аппликейшны орчин (.env)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`-д бодит хаягийг тохируулна.
- `APP_KEY`, `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`-г нууц хадгалалтаас оруулж, нөөцөлнө ([Түлхүүрийн удирдлага](security-keys.md)). Түлхүүрээ алдвал хувийн мэдээллийг тайлах боломжгүй.
- DB: `DB_USERNAME=app_runtime`, `DB_MIGRATOR_USERNAME=app_migrator` (зөвхөн миграцад ашиглах нууц үг), `app_reporting` зөвхөн BI хэрэгсэлд.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`. Horizon (`php artisan horizon`) болон төлөвлөгчийг (`* * * * * php artisan schedule:run`) үйлчилгээ болгон бүртгэнэ.
- `GOTENBERG_URL`: дотоод сүлжээний хаяг. Gotenberg-ийг гадагш хэзээ ч нээхгүй.
- Мэйл (`MAIL_*`): урилга, хоёр шатлалт баталгаажуулалтын и-мэйл, хичээл сонголт болон дүнгийн мэдэгдэлд ашиглана.

## Байршуулах дараалал

1. Нөөцлөнө ([Нөөцлөлт ба сэргээлт](backup-restore.md)).
2. Кодыг байршуулж, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` (migrator холболт руу автоматаар шилжинэ), `php artisan security:sync-permissions`, `php artisan setup:install --force` (үндсэн өгөгдлийг шинэчилж, админуудын өөрчлөлтийг хадгална).
4. `php artisan optimize`, Horizon-ийг дахин эхлүүлэх (`horizon:terminate`), php-fpm-ийг дахин эхлүүлэх (шинэ php.ini хэрэгжинэ).
5. `php artisan system:health-check`-ээр шалгана. Аудитын гинжийг `audit:verify-chain`-ээр шалгана.

## Тогтмол шалгалт

| Давтамж | Ажил |
|---|---|
| Өдөр бүр (автомат) | `audit:seal`, `lms:reconcile`, нөөцөлсөн суудлыг чөлөөлөх, хүлээлгийн жагсаалтаас дэвшүүлэх, өргөдлийн ноорогийн хугацаа дуусгах |
| Долоо хоног бүр | `system:health-check`, PHP болон үйлдлийн системийн аюулгүй байдлын шинэчлэлийг шалгах, Horizon дахь амжилтгүй ажлуудыг шалгах |
| Сар бүр | `media:prune-orphans --dry-run`, нөөцөөс сэргээх дадлага, `audit:verify-chain` |
| Улирал бүр | Үүргийн оноолтыг хянах (ажлаас гарсан, шилжсэн хүмүүс), хадгалах хугацаа дууссан аудитын бичлэгийг цэвэрлэх (`audit:prune`) |
