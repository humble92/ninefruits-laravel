# Oʻrnatish

- Kim uchun: IT operatorlari
- Bogʻliq: [Buyruqlar maʼlumotnomasi](commands.md), [Kalitlarni boshqarish](security-keys.md)

## 1. Talablar

| Komponent | Versiya | Izohlar |
|---|---|---|
| PHP | 8.5 | Kengaytmalar: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | `btree_gist` va `pg_trgm` kengaytmalari (migratsiyalar tomonidan oʻrnatiladi), ICU collation |
| Redis | 8 | Kesh, sessiyalar, navbatlar |
| Gotenberg | 8 | PDF yaratish. Faqat ichki tarmoqda ochiq |
| Pochta serveri | SMTP | Parolni tiklash, ikki bosqichli autentifikatsiya uchun e-pochta kodlari |
| Navbat ishlovchisi | Horizon | `php artisan horizon` ni jarayon menejeri (systemd, supervisor) ostida doimiy ishlatib turing |
| Rejalashtiruvchi | cron | `* * * * * php artisan schedule:run` |

## 2. Maʼlumotlar bazasini tayyorlash

MB superfoydalanuvchisi sifatida bir marta bajaring. Ilova hech qachon superfoydalanuvchidan foydalanmaydi.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| Rol | Muhit oʻzgaruvchilari | Vazifasi |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | Ilovani ishlatish |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | Faqat migratsiyalar |
| `app_reporting` | (BI vositasida sozlanadi) | `reporting` sxemasini oʻqish |

## 3. Muhit sozlamalari

`.env.example` dan nusxa oling va qiymatlarni toʻldiring. Albatta oʻzgartirilishi kerak boʻlgan qiymatlar

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`. **Ular yoʻqolsa, shifrlangan shaxsiy maʼlumotlarni tiklab boʻlmaydi.** Ularni [Kalitlarni boshqarish](security-keys.md) da tavsiflanganidek alohida saqlang.
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: birinchi oʻrnatishdagi muassasaning standart vaqt mintaqasi

## 4. Oʻrnatish

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` savollar berib ishlaydi. Joriy etish skriptlarida uni `--force` va parametrlar bilan ishga tushiring ([Buyruqlar maʼlumotnomasi](commands.md#setupinstall)).

Oʻrnatish profili (`--profile=`): `korean-university` — birinchi/ikkinchi/yozgi-qishki semestrlar, 4.5 baholash shkalasi, fan toifalari, soat 09:00 dan boshlanadigan 50 daqiqalik paralar, auditoriyalar vazifalari. Profilsiz boʻlsa, bularni boshqaruv panelida kiriting. Keyinchalik profilni qayta qoʻllash administratorlar oʻzgartirgan qiymatlarni saqlab qoladi.

Oʻrnatishdan keyin ish jarayonlari administratori asosiy maʼlumotlarni [Administrator qoʻllanmasining 5-bobi, dastlabki sozlash tartibi](../admin/05-institution-setup.md) boʻyicha kiritadi.

## 5. Oʻrnatishni tekshirish

1. `/login` da dastlabki administrator sifatida tizimga kiring.
2. Boshqaruv panelida **Sozlamalar > Xavfsizlik** boʻlimida ikki bosqichli autentifikatsiya siyosatini belgilang.
3. Administratorning oʻz ikki bosqichli autentifikatsiyasini ulang (portaldagi **Xavfsizlik**).
4. PDF xizmatini `php artisan pdf:sample` bilan tekshiring.
5. Boshqaruv panelida **Audit > Maʼlumot oʻzgarishlari** boʻlimida oʻrnatish `system:command:setup:install` sifatida qayd etilganini tasdiqlang.

## 6. Yangilash

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```
