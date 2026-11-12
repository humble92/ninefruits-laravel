# Суулгалт

- Зориулалт: МТ-ийн ашиглалтын ажилтнууд
- Холбоотой: [Командын лавлах](commands.md), [Түлхүүрийн удирдлага](security-keys.md)

## 1. Шаардлага

| Бүрэлдэхүүн | Хувилбар | Тайлбар |
|---|---|---|
| PHP | 8.5 | Өргөтгөлүүд: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | `btree_gist` болон `pg_trgm` өргөтгөл (миграцаар суулгагдана), ICU collation |
| Redis | 8 | Кэш, сесс, дараалал |
| Gotenberg | 8 | PDF үүсгэх. Зөвхөн дотоод сүлжээнд нээлттэй |
| Мэйл сервер | SMTP | Нууц үг сэргээх, хоёр шатлалт баталгаажуулалтын и-мэйл код |
| Дарааллын worker | Horizon | `php artisan horizon`-ийг процесс менежерээр (systemd, supervisor) тасралтгүй ажиллуулна |
| Төлөвлөгч | cron | `* * * * * php artisan schedule:run` |

## 2. Өгөгдлийн санг бэлтгэх

Өгөгдлийн сангийн superuser-ээр нэг удаа ажиллуулна. Аппликейшн superuser-ийг хэзээ ч ашигладаггүй.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| Роль | Орчны хувьсагч | Зориулалт |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | Аппликейшныг ажиллуулах |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | Зөвхөн миграц |
| `app_reporting` | (BI хэрэгсэлд тохируулна) | `reporting` схемийг унших |

## 3. Орчны тохиргоо

`.env.example`-ийг хуулж утгуудыг бөглөнө. Заавал өөрчлөх утгууд

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`. **Эдгээрийг алдвал шифрлэгдсэн хувийн мэдээллийг сэргээх боломжгүй.** [Түлхүүрийн удирдлага](security-keys.md)-д заасны дагуу тусад нь хадгална.
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: анхны суулгалтын үеийн байгууллагын үндсэн цагийн бүс

## 4. Суулгах

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` асуулт асууж явцаа үргэлжлүүлнэ. Байршуулалтын скриптэд `--force` болон сонголтуудтай ажиллуулна ([Командын лавлах](commands.md#setupinstall)).

Суулгалтын профайл (`--profile=`): `korean-university` — нэгдүгээр/хоёрдугаар/зун-өвлийн улирал, 4.5 дүнгийн шкал, хичээлийн ангилал, 09:00-оос эхэлсэн 50 минутын пар, танхимын зориулалт. Профайлгүй бол эдгээрийг удирдлагын самбарт оруулна. Дараа нь профайлыг дахин хэрэглэхэд админуудын өөрчилсөн утгууд хадгалагдана.

Суулгалтын дараа бизнесийн админ [Админы гарын авлагын 5-р бүлэг, анхны тохиргооны дараалал](../admin/05-institution-setup.md)-ын дагуу лавлах мэдээллийг оруулна.

## 5. Суулгалтыг шалгах

1. `/login` хаягаар анхны админаар нэвтэрнэ.
2. Удирдлагын самбарын **Тохиргоо > Аюулгүй байдал** хэсэгт хоёр шатлалт баталгаажуулалтын бодлогыг тохируулна.
3. Админ өөрийн хоёр шатлалт баталгаажуулалтыг бүртгүүлнэ (портал **Аюулгүй байдал**).
4. `php artisan pdf:sample`-аар PDF үйлчилгээг шалгана.
5. Удирдлагын самбарын **Аудит > Өгөгдлийн өөрчлөлтүүд** хэсэгт суулгалт `system:command:setup:install` гэж бүртгэгдсэнийг шалгана.

## 6. Шинэчлэх

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```
