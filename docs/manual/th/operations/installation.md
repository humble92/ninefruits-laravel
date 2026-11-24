# การติดตั้ง

- ผู้อ่าน: ผู้ดูแลระบบไอที
- เกี่ยวข้อง: [ข้อมูลอ้างอิงคำสั่ง](commands.md), [การจัดการคีย์](security-keys.md)

## 1. ข้อกำหนดของระบบ

| องค์ประกอบ | เวอร์ชัน | หมายเหตุ |
|---|---|---|
| PHP | 8.5 | ส่วนขยาย: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | ส่วนขยาย `btree_gist` และ `pg_trgm` (ติดตั้งโดยไมเกรชัน), การเรียงลำดับแบบ ICU |
| Redis | 8 | แคช เซสชัน คิว |
| Gotenberg | 8 | การสร้าง PDF เปิดให้เข้าถึงเฉพาะในเครือข่ายภายใน |
| เซิร์ฟเวอร์อีเมล | SMTP | การรีเซ็ตรหัสผ่าน รหัสยืนยันตัวตนแบบสองขั้นตอนทางอีเมล |
| ตัวประมวลผลคิว | Horizon | ให้ `php artisan horizon` ทำงานต่อเนื่องภายใต้ตัวจัดการโพรเซส (systemd, supervisor) |
| ตัวจัดกำหนดเวลา | cron | `* * * * * php artisan schedule:run` |

## 2. การเตรียมฐานข้อมูล

ดำเนินการครั้งเดียวด้วยสิทธิ์ superuser ของฐานข้อมูล แอปพลิเคชันจะไม่ใช้ superuser เลย

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| บทบาท | ตัวแปรสภาพแวดล้อม | วัตถุประสงค์ |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | การทำงานของแอปพลิเคชัน |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | ไมเกรชันเท่านั้น |
| `app_reporting` | (ตั้งค่าในเครื่องมือ BI) | การอ่านสคีมา `reporting` |

## 3. การตั้งค่าสภาพแวดล้อม

คัดลอก `.env.example` แล้วกรอกค่าต่าง ๆ ค่าที่ต้องเปลี่ยน

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key` **หากคีย์สูญหาย จะไม่สามารถกู้คืนข้อมูลส่วนบุคคลที่เข้ารหัสไว้ได้** ให้จัดเก็บแยกไว้ตามที่อธิบายใน [การจัดการคีย์](security-keys.md)
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: เขตเวลาเริ่มต้นของสถาบันเมื่อติดตั้งครั้งแรก

## 4. การติดตั้ง

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` จะดำเนินการโดยถามคำถามทีละข้อ ในสคริปต์นำขึ้นใช้งาน ให้รันพร้อม `--force` และตัวเลือกต่าง ๆ ([ข้อมูลอ้างอิงคำสั่ง](commands.md#setupinstall))

โปรไฟล์การติดตั้ง (`--profile=`): `korean-university` — ภาคการศึกษาที่ 1/ภาคการศึกษาที่ 2/ภาคฤดูร้อน-ภาคฤดูหนาว เกณฑ์ระดับคะแนน 4.5 หมวดวิชา คาบละ 50 นาทีเริ่มเวลา 09:00 และประเภทการใช้งานห้อง หากไม่ใช้โปรไฟล์ ให้กรอกข้อมูลเหล่านี้ในแผงผู้ดูแลระบบ การใช้โปรไฟล์ซ้ำในภายหลังจะคงค่าที่ผู้ดูแลระบบได้แก้ไขไว้

หลังการติดตั้ง ผู้ดูแลงานจะกรอกข้อมูลอ้างอิงตาม [คู่มือผู้ดูแลระบบ บทที่ 5 ลำดับการตั้งค่าเริ่มต้น](../admin/05-institution-setup.md)

## 5. การตรวจสอบการติดตั้ง

1. เข้าสู่ระบบที่ `/login` ด้วยบัญชีผู้ดูแลระบบเริ่มต้น
2. กำหนดนโยบายการยืนยันตัวตนแบบสองขั้นตอนที่ **การตั้งค่า > ความปลอดภัย** ในแผงผู้ดูแลระบบ
3. ลงทะเบียนการยืนยันตัวตนแบบสองขั้นตอนของผู้ดูแลระบบเอง (พอร์ทัล **ความปลอดภัย**)
4. ตรวจสอบบริการ PDF ด้วย `php artisan pdf:sample`
5. ยืนยันที่ **การตรวจสอบ > การเปลี่ยนแปลงข้อมูล** ในแผงผู้ดูแลระบบว่าการติดตั้งถูกบันทึกเป็น `system:command:setup:install`

## 6. การอัปเดต

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```

## 7. เว็บเซิร์ฟเวอร์ (Nginx)

วาง Nginx ไว้หน้า php-fpm ด้านล่างเป็นตัวอย่าง ให้เปลี่ยนที่อยู่ เส้นทาง ใบรับรอง และ socket ของ php-fpm ให้ตรงกับเซิร์ฟเวอร์

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

- **หน้าข้อผิดพลาด:** `public/errors/unavailable.html` เป็นหน้าสแตติกที่ Nginx แสดงเมื่อ PHP ไม่ตอบสนอง (502, 503, 504) ไม่ต้องใช้ไฟล์ build หรือฐานข้อมูล และข้อความจะเปลี่ยนตามภาษาของเบราว์เซอร์ (ภาษาที่ไม่รองรับจะเป็นภาษาอังกฤษ) หน้าข้อผิดพลาดที่แอปส่งเอง (500, 503 ระหว่าง `php artisan down` ฯลฯ) จะผ่านไปตามเดิม เพราะ `fastcgi_intercept_errors` ยังเป็นค่าเริ่มต้น (off)
- **หมายเลขอ้างอิง:** `ssi on` จะใส่ `$request_id` ของ Nginx ลงในหน้า ค่าเดียวกันนี้ส่งให้แอปเป็น `X-Request-Id` จึงค้นหา access log ของ Nginx (`rid=`) ล็อกของแอป และบันทึกการตรวจสอบได้ด้วยหมายเลขเดียว ([การแก้ไขปัญหา](troubleshooting.md)) `X-Request-Id` ที่ไคลเอนต์ส่งมาจะถูกแทนที่ด้วยค่านี้
- **เมื่อ Nginx ทำ proxy ไปยังเซิร์ฟเวอร์แอปที่แยกกัน:** ใช้ `proxy_set_header X-Request-Id $request_id;` และปล่อย `proxy_intercept_errors` เป็นค่าเริ่มต้น (off) คง `error_page` และ `location` ของมันไว้ตามเดิม
- **เมื่อเปลี่ยนข้อความ:** หากแก้ `unavailable` และ `reference` ใน `lang/*/errors.php` ให้แก้ JSON ในไฟล์นี้ด้วย หากทั้งสองไม่ตรงกัน การทดสอบ (`ErrorPagesTest`) จะล้มเหลว
- **เวอร์ชัน Nginx:** `http2 on;` ต้องใช้ Nginx 1.25.1 ขึ้นไป ในเวอร์ชันที่เก่ากว่า (เช่น 1.18 ใน Ubuntu 22.04) ให้ลบบรรทัดนี้ออกแล้วเขียน `listen 443 ssl http2;` แทน
