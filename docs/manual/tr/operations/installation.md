# Kurulum

- Hedef kitle: BT operatörleri
- İlgili: [Komut başvurusu](commands.md), [Anahtar yönetimi](security-keys.md)

## 1. Gereksinimler

| Bileşen | Sürüm | Notlar |
|---|---|---|
| PHP | 8.5 | Eklentiler: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | `btree_gist` ve `pg_trgm` eklentileri (migration'lar tarafından kurulur), ICU harmanlaması |
| Redis | 8 | Önbellek, oturumlar, kuyruklar |
| Gotenberg | 8 | PDF üretimi. Yalnızca iç ağda erişilebilir |
| E-posta sunucusu | SMTP | Parola sıfırlama, iki adımlı doğrulama e-posta kodları |
| Kuyruk çalışanı | Horizon | `php artisan horizon` komutunu bir süreç yöneticisi (systemd, supervisor) altında sürekli çalışır tutun |
| Zamanlayıcı | cron | `* * * * * php artisan schedule:run` |

## 2. Veritabanı hazırlığı

DB süper kullanıcısı olarak bir kez çalıştırın. Uygulama hiçbir zaman süper kullanıcıyı kullanmaz.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| Rol | Ortam değişkenleri | Amaç |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | Uygulamanın çalışması |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | Yalnızca migration'lar |
| `app_reporting` | (BI aracında yapılandırılır) | `reporting` şemasının okunması |

## 3. Ortam yapılandırması

`.env.example` dosyasını kopyalayın ve değerleri doldurun. Değiştirilmesi gereken değerler

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`. **Kaybolurlarsa şifrelenmiş kişisel veriler kurtarılamaz.** [Anahtar yönetimi](security-keys.md) belgesinde anlatıldığı gibi ayrı olarak saklayın.
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: ilk kurulumdaki varsayılan kurum saat dilimi

## 4. Kurulum

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` sorular sorarak ilerler. Dağıtım betiklerinde `--force` ve seçeneklerle çalıştırın ([Komut başvurusu](commands.md#setupinstall)).

Kurulum profili (`--profile=`): `korean-university` — birinci/ikinci/yaz-kış dönemleri, 4.5 not ölçeği, ders türleri, 09:00'da başlayan 50 dakikalık ders saatleri, derslik kullanım amaçları. Profil olmadan bunları yönetim panelinde girin. Bir profil daha sonra yeniden uygulandığında yöneticilerin değiştirdiği değerler korunur.

Kurulumdan sonra iş yöneticisi, temel verileri [Yönetici kılavuzu 5. bölüm, ilk ayar sırası](../admin/05-institution-setup.md) belgesini izleyerek girer.

## 5. Kurulumun doğrulanması

1. `/login` adresinde ilk yönetici olarak oturum açın.
2. Yönetim panelinde **Ayarlar > Güvenlik** altında iki adımlı doğrulama politikasını belirleyin.
3. Yöneticinin kendi iki adımlı doğrulamasını kaydedin (portal **Güvenlik**).
4. PDF servisini `php artisan pdf:sample` ile kontrol edin.
5. Yönetim panelinde **Denetim > Veri değişiklikleri** altında kurulumun `system:command:setup:install` olarak kaydedildiğini doğrulayın.

## 6. Güncelleme

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```
