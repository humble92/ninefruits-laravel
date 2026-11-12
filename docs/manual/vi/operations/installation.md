# Cài đặt

- Đối tượng: cán bộ vận hành CNTT
- Liên quan: [Tham chiếu lệnh](commands.md), [Quản lý khóa](security-keys.md)

## 1. Yêu cầu hệ thống

| Thành phần | Phiên bản | Ghi chú |
|---|---|---|
| PHP | 8.5 | Phần mở rộng: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | Phần mở rộng `btree_gist` và `pg_trgm` (được cài bởi migration), collation ICU |
| Redis | 8 | Cache, phiên làm việc, hàng đợi |
| Gotenberg | 8 | Tạo PDF. Chỉ mở trên mạng nội bộ |
| Máy chủ thư | SMTP | Đặt lại mật khẩu, mã xác thực hai yếu tố qua email |
| Tiến trình xử lý hàng đợi | Horizon | Duy trì `php artisan horizon` chạy liên tục dưới một trình quản lý tiến trình (systemd, supervisor) |
| Bộ lập lịch | cron | `* * * * * php artisan schedule:run` |

## 2. Chuẩn bị cơ sở dữ liệu

Chạy một lần với quyền superuser của cơ sở dữ liệu. Ứng dụng không bao giờ dùng superuser.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| Vai trò | Biến môi trường | Mục đích |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | Chạy ứng dụng |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | Chỉ dùng cho migration |
| `app_reporting` | (cấu hình trong công cụ BI) | Đọc schema `reporting` |

## 3. Cấu hình môi trường

Sao chép `.env.example` và điền các giá trị. Những giá trị bắt buộc phải thay đổi

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`. **Nếu bị mất, dữ liệu cá nhân đã mã hóa không thể khôi phục.** Lưu trữ riêng theo hướng dẫn trong [Quản lý khóa](security-keys.md).
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: múi giờ mặc định của cơ sở đào tạo khi cài đặt lần đầu

## 4. Cài đặt

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` tiến hành bằng cách đặt câu hỏi. Trong script triển khai, hãy chạy với `--force` và các tùy chọn ([Tham chiếu lệnh](commands.md#setupinstall)).

Hồ sơ cài đặt (`--profile=`): `korean-university` — học kỳ 1/học kỳ 2/học kỳ hè-đông, thang điểm 4.5, loại học phần, tiết học 50 phút bắt đầu từ 09:00, mục đích sử dụng phòng. Nếu không dùng hồ sơ, hãy nhập các nội dung này trong trang quản trị. Áp dụng lại hồ sơ về sau vẫn giữ nguyên các giá trị mà quản trị viên đã thay đổi.

Sau khi cài đặt, quản trị viên nghiệp vụ nhập dữ liệu danh mục theo [Hướng dẫn quản trị viên chương 5, thứ tự thiết lập ban đầu](../admin/05-institution-setup.md).

## 5. Kiểm tra sau cài đặt

1. Đăng nhập tại `/login` bằng tài khoản quản trị viên ban đầu.
2. Thiết lập chính sách xác thực hai yếu tố tại **Cài đặt > Bảo mật** trong trang quản trị.
3. Đăng ký xác thực hai yếu tố cho chính quản trị viên (mục **Bảo mật** trên cổng thông tin).
4. Kiểm tra dịch vụ PDF bằng `php artisan pdf:sample`.
5. Xác nhận tại **Kiểm toán > Thay đổi dữ liệu** trong trang quản trị rằng việc cài đặt đã được ghi lại dưới tên `system:command:setup:install`.

## 6. Cập nhật

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```
