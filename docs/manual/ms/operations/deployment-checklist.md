# Senarai semak pelaksanaan dan tetapan keselamatan PHP

## Versi PHP

- Gunakan **PHP ≥ 8.5.12** (atau 8.4.27, 8.3.36). Keluaran keselamatan pada 2026-10-21 membaiki dua kerentanan pelaksanaan kod jauh tanpa pengesahan (CVE-2026-109618 kerosakan keadaan OOM, CVE-2026-109619 kebocoran keadaan antara permintaan), dan php.net menyatakan bahawa "tiada mitigasi yang lengkap; mengemas kini ialah satu-satunya pilihan yang selamat". Kekangan `"php": "^8.5.12"` dalam `composer.json` menghalang pemasangan pada mana-mana PHP yang lebih rendah.
- Persekitaran pembangunan: jalankan `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d`, kemudian sahkan dengan `./vendor/bin/sail php -v`.
- Semasa membina imej kontena atau pelayan, jangan kunci versi pakej PHP; ambil keluaran tampalan terkini. `system:health-check` juga memaparkan versi PHP.

## php.ini produksi (pool php-fpm atau konfigurasi pelayan, bukan `.user.ini` atau `ini_set()`)

| Tetapan | Nilai | Sebab |
|---|---|---|
| `session.upload_progress.enabled` | `0` | Menyekat laluan CVE-2026-109619 dan CVE-2026-115560 (nasihat php.net bertarikh 2026-10-21 dan 23). Sistem ini tidak menggunakan kemajuan muat naik |
| `fatal_error_backtraces` | `0` (PHP 8.5) | Menyekat laluan backtrace ralat fatal daripada nasihat yang sama |
| `memory_limit` | `256M` atau lebih, dikunci dengan `php_admin_value` | Supaya kod aplikasi tidak dapat mengubahnya |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | Had atas bagi muat naik dokumen kemasukan, bukti peperiksaan dan foto (dengan ruang lebihan di atas pengesahan aplikasi 5MB dan 2MB) |
| `expose_php` | `Off` | Jangan dedahkan versi |
| `display_errors` | `Off`, `log_errors=On` | Produksi |
| `session.cookie_secure`, `session.cookie_httponly`, `session.cookie_samesite` | `1`, `1`, `Lax` | Bersama tetapan sesi Laravel (`SESSION_SECURE_COOKIE=true`) |
| `opcache.enable`, `opcache.validate_timestamps` | `1`, `0` (mula semula semasa pelaksanaan) | Prestasi |

## Persekitaran aplikasi (.env)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` ditetapkan kepada alamat sebenar.
- Suntik `APP_KEY`, `PII_ENCRYPTION_KEY` dan `PII_HASH_KEY` daripada stor rahsia dan buat sandarannya ([Pengurusan kunci](security-keys.md)). Jika kunci hilang, data peribadi tidak dapat dinyahsulit.
- Pangkalan data: `DB_USERNAME=app_runtime`, `DB_MIGRATOR_USERNAME=app_migrator` (kata laluan yang digunakan hanya untuk migrasi), `app_reporting` hanya untuk alat BI.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`. Daftarkan Horizon (`php artisan horizon`) dan penjadual (`* * * * * php artisan schedule:run`) sebagai perkhidmatan.
- `GOTENBERG_URL`: alamat rangkaian dalaman. Gotenberg tidak sekali-kali didedahkan ke luar.
- E-mel (`MAIL_*`): digunakan untuk jemputan, e-mel pengesahan dua faktor, serta pemberitahuan pendaftaran kursus dan gred.

## Prosedur pelaksanaan

1. Buat sandaran ([Sandaran dan pemulihan](backup-restore.md)).
2. Laksanakan kod, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` (bertukar ke sambungan migrator secara automatik), `php artisan security:sync-permissions`, `php artisan setup:install --force` (menyegarkan data penting, mengekalkan suntingan pentadbir).
4. `php artisan optimize`, mula semula Horizon (`horizon:terminate`), mula semula php-fpm (menggunakan php.ini baharu).
5. Sahkan dengan `php artisan system:health-check`. Sahkan rantaian audit dengan `audit:verify-chain`.

## Pemeriksaan berkala

| Kekerapan | Tugas |
|---|---|
| Harian (automatik) | `audit:seal`, `lms:reconcile`, pelepasan tempat duduk pendaftaran kursus yang ditempah, kenaikan senarai menunggu, luput draf permohonan |
| Mingguan | `system:health-check`, semak kemas kini keselamatan PHP dan OS, semak tugas yang gagal dalam Horizon |
| Bulanan | `media:prune-orphans --dry-run`, latihan pemulihan sandaran, `audit:verify-chain` |
| Setiap semester | Semak semula penetapan peranan (individu yang telah meninggalkan institusi atau bertukar), pangkas rekod audit yang melepasi tempoh simpanan (`audit:prune`) |
