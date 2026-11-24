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

## 7. Web sunucusu (Nginx)

php-fpm'in önüne Nginx koyun. Aşağıdaki bir örnektir: adresi, yolları, sertifikaları ve php-fpm soketini sunucunuza göre değiştirin.

```nginx
log_format sis '$remote_addr - $remote_user [$time_local] "$request" $status $body_bytes_sent '
               '"$http_referer" "$http_user_agent" rt=$request_time rid=$request_id';

server {
    listen 443 ssl;
    http2 on;
    server_name sis.example.edu;
    root /var/www/sis/public;
    index index.php;

    ssl_certificate     /etc/ssl/sis/fullchain.pem;
    ssl_certificate_key /etc/ssl/sis/privkey.pem;

    access_log /var/log/nginx/sis.access.log sis;
    client_max_body_size 32m;     # same as post_max_size in php.ini
    server_tokens off;

    # Only when PHP does not answer. The application's own error pages pass through.
    error_page 502 503 504 /errors/unavailable.html;
    location = /errors/unavailable.html {
        internal;
        ssi on;                    # puts $request_id on the page
        add_header Cache-Control "no-store" always;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param HTTP_X_REQUEST_ID $request_id;   # replaces a client-sent X-Request-Id
        internal;
    }

    location ~ \.php$ { return 404; }
    location ~ /\.(?!well-known) { deny all; }
}
```

- **Hata sayfası:** `public/errors/unavailable.html`, PHP yanıt vermediğinde (502, 503, 504) Nginx'in gösterdiği statik sayfadır. Derleme dosyalarına ve veritabanına ihtiyaç duymaz; metni tarayıcı diline göre değişir (desteklenmeyen dillerde İngilizce). `fastcgi_intercept_errors` varsayılan değerinde (off) kaldığı için uygulamanın kendisinin gönderdiği hata sayfaları (500, `php artisan down` sırasındaki 503 vb.) olduğu gibi iletilir.
- **Referans numarası:** `ssi on`, Nginx'in `$request_id` değerini sayfaya yerleştirir. Aynı değer uygulamaya `X-Request-Id` olarak iletilir; böylece Nginx erişim günlüğü (`rid=`), uygulama günlükleri ve denetim kayıtları tek bir numarayla aranabilir ([sorun giderme](troubleshooting.md)). İstemcinin gönderdiği `X-Request-Id` bu değerle değiştirilir.
- **Nginx ayrı bir uygulama sunucusuna proxy yaptığında:** `proxy_set_header X-Request-Id $request_id;` kullanın ve `proxy_intercept_errors` değerini varsayılanda (off) bırakın. `error_page` ve ilgili `location` olduğu gibi kalır.
- **Metni değiştirirken:** `lang/*/errors.php` içindeki `unavailable` ve `reference` değerlerini düzenlerseniz bu dosyadaki JSON'u da düzenleyin. İkisi birbirinden ayrılırsa test (`ErrorPagesTest`) başarısız olur.
- **Nginx sürümü:** `http2 on;` için Nginx 1.25.1 veya daha yenisi gerekir. Daha eski sürümlerde (örneğin Ubuntu 22.04'teki 1.18) bu satırı silip yerine `listen 443 ssl http2;` yazın.
