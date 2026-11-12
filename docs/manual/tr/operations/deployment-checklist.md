# Dağıtım kontrol listesi ve PHP güvenlik ayarları

## PHP sürümü

- **PHP ≥ 8.5.12** (veya 8.4.27, 8.3.36) kullanın. 2026-10-21 tarihli güvenlik sürümü, kimlik doğrulama gerektirmeyen iki uzaktan kod çalıştırma açığını giderdi (CVE-2026-109618 OOM durum bozulması, CVE-2026-109619 istekler arasında durum sızması) ve php.net "tam bir azaltma yöntemi yoktur; güncellemek tek güvenli seçenektir" açıklamasını yaptı. `composer.json` içindeki `"php": "^8.5.12"` kısıtı, daha düşük bir PHP sürümüne kurulumu engeller.
- Geliştirme ortamı: `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d` çalıştırın, ardından `./vendor/bin/sail php -v` ile doğrulayın.
- Konteyner veya sunucu imajları oluştururken PHP paketini sabitlemeyin; en son yama sürümünü alın. `system:health-check` da PHP sürümünü gösterir.

## Üretim php.ini (`.user.ini` veya `ini_set()` değil, php-fpm havuzu ya da sunucu yapılandırması)

| Ayar | Değer | Neden |
|---|---|---|
| `session.upload_progress.enabled` | `0` | CVE-2026-109619 ve CVE-2026-115560 açıklarının yollarını kapatır (php.net'in 2026-10-21 ve 23 tarihli duyuruları). Bu sistem yükleme ilerlemesini kullanmaz |
| `fatal_error_backtraces` | `0` (PHP 8.5) | Aynı duyurudaki ölümcül hata geri izleme yolunu kapatır |
| `memory_limit` | `256M` veya daha fazla, `php_admin_value` ile sabitlenir | Uygulama kodunun değiştirememesi için |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | Başvuru evrakları, sınav kanıtları ve fotoğraf yüklemeleri için üst sınır (uygulamanın 5MB ve 2MB doğrulamasının üzerinde pay bırakarak) |
| `expose_php` | `Off` | Sürümü açığa vurmamak için |
| `display_errors` | `Off`, `log_errors=On` | Üretim |
| `session.cookie_secure`, `session.cookie_httponly`, `session.cookie_samesite` | `1`, `1`, `Lax` | Laravel oturum ayarlarıyla birlikte (`SESSION_SECURE_COOKIE=true`) |
| `opcache.enable`, `opcache.validate_timestamps` | `1`, `0` (dağıtımda yeniden başlatın) | Performans |

## Uygulama ortamı (.env)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` gerçek adrese ayarlanır.
- `APP_KEY`, `PII_ENCRYPTION_KEY` ve `PII_HASH_KEY` değerlerini gizli bilgi deposundan aktarın ve yedekleyin ([Anahtar yönetimi](security-keys.md)). Anahtarlar kaybolursa kişisel verilerin şifresi çözülemez.
- DB: `DB_USERNAME=app_runtime`, `DB_MIGRATOR_USERNAME=app_migrator` (yalnızca migration'lar için kullanılan bir parola), `app_reporting` yalnızca BI araçları için.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`. Horizon'u (`php artisan horizon`) ve zamanlayıcıyı (`* * * * * php artisan schedule:run`) servis olarak kaydedin.
- `GOTENBERG_URL`: iç ağ adresi. Gotenberg hiçbir zaman dışarıya açılmaz.
- E-posta (`MAIL_*`): davetler, iki adımlı doğrulama e-postaları ve ders kaydı ile not bildirimleri için kullanılır.

## Dağıtım prosedürü

1. Yedek alın ([Yedekleme ve geri yükleme](backup-restore.md)).
2. Kodu dağıtın, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` (migrator bağlantısına otomatik geçer), `php artisan security:sync-permissions`, `php artisan setup:install --force` (temel verileri yeniler, yönetici düzenlemelerini korur).
4. `php artisan optimize`, Horizon'u yeniden başlatın (`horizon:terminate`), php-fpm'i yeniden başlatın (yeni php.ini'yi uygular).
5. `php artisan system:health-check` ile doğrulayın. Denetim zincirini `audit:verify-chain` ile doğrulayın.

## Rutin kontroller

| Sıklık | Görev |
|---|---|
| Günlük (otomatik) | `audit:seal`, `lms:reconcile`, ders kaydındaki ayrılmış kontenjanların serbest bırakılması, bekleme listesinden kayıt, başvuru taslaklarının süresinin dolması |
| Haftalık | `system:health-check`, PHP ve işletim sistemi güvenlik güncellemelerinin kontrolü, Horizon'daki başarısız işlerin kontrolü |
| Aylık | `media:prune-orphans --dry-run`, yedekten geri yükleme tatbikatı, `audit:verify-chain` |
| Her dönem | Rol atamalarının gözden geçirilmesi (ayrılan veya görev yeri değişen kişiler), saklama süresi geçmiş denetim kayıtlarının temizlenmesi (`audit:prune`) |
