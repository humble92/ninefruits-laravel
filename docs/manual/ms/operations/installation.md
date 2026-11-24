# Pemasangan

- Pembaca: pengendali IT
- Berkaitan: [Rujukan arahan](commands.md), [Pengurusan kunci](security-keys.md)

## 1. Keperluan

| Komponen | Versi | Catatan |
|---|---|---|
| PHP | 8.5 | Sambungan: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | Sambungan `btree_gist` dan `pg_trgm` (dipasang oleh migrasi), kolasi ICU |
| Redis | 8 | Cache, sesi, baris gilir |
| Gotenberg | 8 | Penjanaan PDF. Didedahkan hanya pada rangkaian dalaman |
| Pelayan e-mel | SMTP | Penetapan semula kata laluan, kod e-mel pengesahan dua faktor |
| Pekerja baris gilir | Horizon | Pastikan `php artisan horizon` sentiasa berjalan di bawah pengurus proses (systemd, supervisor) |
| Penjadual | cron | `* * * * * php artisan schedule:run` |

## 2. Penyediaan pangkalan data

Jalankan sekali sebagai superuser pangkalan data. Aplikasi tidak sekali-kali menggunakan superuser.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| Peranan | Pemboleh ubah persekitaran | Tujuan |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | Menjalankan aplikasi |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | Migrasi sahaja |
| `app_reporting` | (dikonfigurasi dalam alat BI) | Membaca skema `reporting` |

## 3. Konfigurasi persekitaran

Salin `.env.example` dan isikan nilainya. Nilai yang mesti diubah

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`. **Jika kunci ini hilang, data peribadi yang disulitkan tidak dapat dipulihkan.** Simpan secara berasingan seperti yang diterangkan dalam [Pengurusan kunci](security-keys.md).
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: zon waktu lalai institusi pada pemasangan pertama

## 4. Pemasangan

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` berjalan dengan mengemukakan soalan. Dalam skrip pelaksanaan, jalankannya dengan `--force` dan pilihan ([Rujukan arahan](commands.md#setupinstall)).

Profil pemasangan (`--profile=`): `korean-university` — semester pertama/kedua/semester pendek (musim panas/musim sejuk), skala gred 4.5, kategori kursus, slot masa 50 minit bermula pada 09:00, kegunaan bilik. Tanpa profil, masukkan semua ini dalam panel pentadbiran. Menggunakan profil sekali lagi kemudian akan mengekalkan nilai yang telah diubah oleh pentadbir.

Selepas pemasangan, pentadbir urusan memasukkan data rujukan mengikut [Manual pentadbir bab 5, susunan persediaan awal](../admin/05-institution-setup.md).

## 5. Mengesahkan pemasangan

1. Log masuk di `/login` sebagai pentadbir awal.
2. Tetapkan dasar pengesahan dua faktor di bawah **Tetapan > Keselamatan** dalam panel pentadbiran.
3. Daftarkan pengesahan dua faktor pentadbir sendiri (portal **Keselamatan**).
4. Semak perkhidmatan PDF dengan `php artisan pdf:sample`.
5. Pastikan di bawah **Audit > Perubahan data** dalam panel pentadbiran bahawa pemasangan direkodkan sebagai `system:command:setup:install`.

## 6. Mengemas kini

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```

## 7. Pelayan web (Nginx)

Letakkan Nginx di hadapan php-fpm. Berikut ialah contoh: tukar alamat, laluan, sijil dan soket php-fpm mengikut pelayan anda.

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

- **Halaman ralat:** `public/errors/unavailable.html` ialah halaman statik yang dipaparkan oleh Nginx apabila PHP tidak menjawab (502, 503, 504). Ia tidak memerlukan fail build atau pangkalan data, dan teksnya mengikut bahasa pelayar (bahasa Inggeris bagi bahasa yang tidak disokong). Halaman ralat yang dihantar sendiri oleh aplikasi (500, 503 semasa `php artisan down` dan lain-lain) diteruskan tanpa perubahan kerana `fastcgi_intercept_errors` kekal pada nilai lalai (off).
- **Nombor rujukan:** `ssi on` memasukkan `$request_id` Nginx ke dalam halaman. Nilai yang sama dihantar kepada aplikasi sebagai `X-Request-Id`, jadi log akses Nginx (`rid=`), log aplikasi dan rekod audit boleh dicari dengan satu nombor ([penyelesaian masalah](troubleshooting.md)). `X-Request-Id` yang dihantar oleh klien digantikan dengan nilai ini.
- **Apabila Nginx menjadi proksi kepada pelayan aplikasi yang berasingan:** gunakan `proxy_set_header X-Request-Id $request_id;` dan biarkan `proxy_intercept_errors` pada nilai lalai (off). Kekalkan `error_page` dan `location`nya.
- **Apabila mengubah teks:** jika anda menyunting `unavailable` dan `reference` dalam `lang/*/errors.php`, sunting juga JSON dalam fail ini. Ujian (`ErrorPagesTest`) gagal apabila kedua-duanya tidak sepadan.
