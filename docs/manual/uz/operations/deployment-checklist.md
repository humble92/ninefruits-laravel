# Joriy etish nazorat roʻyxati va PHP xavfsizlik sozlamalari

## PHP versiyasi

- **PHP ≥ 8.5.12** (yoki 8.4.27, 8.3.36) dan foydalaning. 2026-10-21 dagi xavfsizlik relizi autentifikatsiyasiz masofadan kod bajarishga imkon beruvchi ikkita zaiflikni tuzatdi (CVE-2026-109618 OOM holatining buzilishi, CVE-2026-109619 soʻrovlar orasida holatning sizib chiqishi), php.net esa "toʻliq yumshatish chorasi yoʻq; yangilash yagona xavfsiz yoʻl" deb maʼlum qildi. `composer.json` dagi `"php": "^8.5.12"` cheklovi undan past PHP ga oʻrnatishning oldini oladi.
- Ishlab chiqish muhiti: `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d` ni bajaring, soʻngra `./vendor/bin/sail php -v` bilan tasdiqlang.
- Konteyner yoki server obrazlarini yigʻishda PHP paketini qatʼiy versiyaga bogʻlamang; eng soʻnggi patch relizini oling. `system:health-check` ham PHP versiyasini koʻrsatadi.

## Production php.ini (`.user.ini` yoki `ini_set()` emas, php-fpm pool yoki server sozlamalari)

| Sozlama | Qiymat | Sabab |
|---|---|---|
| `session.upload_progress.enabled` | `0` | CVE-2026-109619 va CVE-2026-115560 yoʻllarini toʻsadi (php.net ning 2026-10-21 va 23 dagi ogohlantirishlari). Bu tizim yuklash jarayoni koʻrsatkichidan foydalanmaydi |
| `fatal_error_backtraces` | `0` (PHP 8.5) | Oʻsha ogohlantirishdagi fatal xato backtrace yoʻlini toʻsadi |
| `memory_limit` | `256M` yoki undan koʻp, `php_admin_value` bilan qatʼiy belgilangan | Ilova kodi uni oʻzgartira olmasligi uchun |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | Qabul hujjatlari, imtihon dalillari va fotosuratlarni yuklash uchun yuqori chegara (ilovaning 5MB va 2MB tekshiruvidan zaxira bilan) |
| `expose_php` | `Off` | Versiyani oshkor qilmaslik |
| `display_errors` | `Off`, `log_errors=On` | Production |
| `session.cookie_secure`, `session.cookie_httponly`, `session.cookie_samesite` | `1`, `1`, `Lax` | Laravel sessiya sozlamalari (`SESSION_SECURE_COOKIE=true`) bilan birga |
| `opcache.enable`, `opcache.validate_timestamps` | `1`, `0` (joriy etishda qayta ishga tushirish) | Unumdorlik |

## Ilova muhiti (.env)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` haqiqiy manzilga oʻrnatilgan.
- `APP_KEY`, `PII_ENCRYPTION_KEY` va `PII_HASH_KEY` ni maxfiy maʼlumotlar omboridan kiriting va ularning zaxira nusxasini saqlang ([Kalitlarni boshqarish](security-keys.md)). Kalitlar yoʻqolsa, shaxsiy maʼlumotlarni shifrdan chiqarib boʻlmaydi.
- MB: `DB_USERNAME=app_runtime`, `DB_MIGRATOR_USERNAME=app_migrator` (faqat migratsiyalar uchun ishlatiladigan parol), `app_reporting` faqat BI vositalari uchun.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`. Horizon (`php artisan horizon`) va rejalashtiruvchini (`* * * * * php artisan schedule:run`) xizmat sifatida roʻyxatdan oʻtkazing.
- `GOTENBERG_URL`: ichki tarmoq manzili. Gotenberg hech qachon tashqariga ochilmaydi.
- Pochta (`MAIL_*`): taklifnomalar, ikki bosqichli autentifikatsiya xatlari, fanlarga yozilish va baholar haqidagi bildirishnomalar uchun ishlatiladi.

## Joriy etish tartibi

1. Zaxira nusxa oling ([Zaxiralash va tiklash](backup-restore.md)).
2. Kodni joylashtiring, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` (avtomatik ravishda migrator ulanishiga oʻtadi), `php artisan security:sync-permissions`, `php artisan setup:install --force` (asosiy maʼlumotlarni yangilaydi, administrator tahrirlarini saqlaydi).
4. `php artisan optimize`, Horizon ni qayta ishga tushirish (`horizon:terminate`), php-fpm ni qayta ishga tushirish (yangi php.ini ni qoʻllaydi).
5. `php artisan system:health-check` bilan tekshiring. Audit zanjirini `audit:verify-chain` bilan tekshiring.

## Muntazam tekshiruvlar

| Davriylik | Vazifa |
|---|---|
| Har kuni (avtomatik) | `audit:seal`, `lms:reconcile`, fanlarga yozilishdagi band qilingan oʻrinlarni boʻshatish, kutish roʻyxatidan oʻtkazish, ariza qoralamalari muddatining tugashi |
| Har hafta | `system:health-check`, PHP va OT xavfsizlik yangilanishlarini tekshirish, Horizon dagi muvaffaqiyatsiz vazifalarni tekshirish |
| Har oy | `media:prune-orphans --dry-run`, zaxiradan tiklash mashqi, `audit:verify-chain` |
| Har semestr | Rol tayinlashlarni koʻrib chiqish (ishdan ketgan yoki boshqa joyga oʻtgan shaxslar), saqlash muddati oʻtgan audit yozuvlarini tozalash (`audit:prune`) |
