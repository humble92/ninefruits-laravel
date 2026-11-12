# Danh sách kiểm tra triển khai và thiết lập bảo mật PHP

## Phiên bản PHP

- Dùng **PHP ≥ 8.5.12** (hoặc 8.4.27, 8.3.36). Bản phát hành bảo mật ngày 2026-10-21 đã vá hai lỗ hổng thực thi mã từ xa không cần xác thực (CVE-2026-109618 hỏng trạng thái khi hết bộ nhớ, CVE-2026-109619 rò rỉ trạng thái giữa các yêu cầu), và php.net cho biết "không có biện pháp giảm thiểu hoàn chỉnh; cập nhật là lựa chọn an toàn duy nhất". Ràng buộc `"php": "^8.5.12"` trong `composer.json` ngăn việc cài đặt trên bất kỳ phiên bản PHP thấp hơn nào.
- Môi trường phát triển: chạy `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d`, sau đó xác nhận bằng `./vendor/bin/sail php -v`.
- Khi build image container hoặc máy chủ, đừng cố định gói PHP; hãy lấy bản vá mới nhất. `system:health-check` cũng hiển thị phiên bản PHP.

## php.ini cho môi trường production (cấu hình pool php-fpm hoặc máy chủ, không dùng `.user.ini` hay `ini_set()`)

| Thiết lập | Giá trị | Lý do |
|---|---|---|
| `session.upload_progress.enabled` | `0` | Chặn đường khai thác của CVE-2026-109619 và CVE-2026-115560 (khuyến cáo của php.net ngày 2026-10-21 và 23). Hệ thống này không dùng tính năng tiến trình tải lên |
| `fatal_error_backtraces` | `0` (PHP 8.5) | Chặn đường khai thác qua backtrace của lỗi nghiêm trọng nêu trong cùng khuyến cáo |
| `memory_limit` | `256M` trở lên, cố định bằng `php_admin_value` | Để mã ứng dụng không thể thay đổi |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | Giới hạn trên cho tải lên tài liệu tuyển sinh, minh chứng kỳ thi và ảnh (có dư địa so với mức kiểm tra 5MB và 2MB của ứng dụng) |
| `expose_php` | `Off` | Không để lộ phiên bản |
| `display_errors` | `Off`, `log_errors=On` | Môi trường production |
| `session.cookie_secure`, `session.cookie_httponly`, `session.cookie_samesite` | `1`, `1`, `Lax` | Kết hợp với thiết lập phiên của Laravel (`SESSION_SECURE_COOKIE=true`) |
| `opcache.enable`, `opcache.validate_timestamps` | `1`, `0` (khởi động lại khi triển khai) | Hiệu năng |

## Môi trường ứng dụng (.env)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` đặt thành địa chỉ thực.
- Nạp `APP_KEY`, `PII_ENCRYPTION_KEY` và `PII_HASH_KEY` từ kho lưu trữ bí mật và sao lưu chúng ([Quản lý khóa](security-keys.md)). Nếu mất khóa, dữ liệu cá nhân không thể giải mã.
- Cơ sở dữ liệu: `DB_USERNAME=app_runtime`, `DB_MIGRATOR_USERNAME=app_migrator` (mật khẩu chỉ dùng cho migration), `app_reporting` chỉ dành cho công cụ BI.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`. Đăng ký Horizon (`php artisan horizon`) và bộ lập lịch (`* * * * * php artisan schedule:run`) làm dịch vụ.
- `GOTENBERG_URL`: một địa chỉ mạng nội bộ. Gotenberg không bao giờ được công khai ra bên ngoài.
- Thư điện tử (`MAIL_*`): dùng cho thư mời, email xác thực hai yếu tố, và thông báo về đăng ký học phần và điểm.

## Quy trình triển khai

1. Sao lưu ([Sao lưu và khôi phục](backup-restore.md)).
2. Triển khai mã nguồn, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` (tự động chuyển sang kết nối migrator), `php artisan security:sync-permissions`, `php artisan setup:install --force` (làm mới dữ liệu thiết yếu, giữ nguyên các chỉnh sửa của quản trị viên).
4. `php artisan optimize`, khởi động lại Horizon (`horizon:terminate`), khởi động lại php-fpm (áp dụng php.ini mới).
5. Kiểm tra bằng `php artisan system:health-check`. Kiểm tra chuỗi kiểm toán bằng `audit:verify-chain`.

## Kiểm tra định kỳ

| Tần suất | Công việc |
|---|---|
| Hằng ngày (tự động) | `audit:seal`, `lms:reconcile`, giải phóng chỗ dành riêng trong đăng ký học phần, chuyển sinh viên từ danh sách chờ, hết hạn bản nháp hồ sơ xét tuyển |
| Hằng tuần | `system:health-check`, kiểm tra bản cập nhật bảo mật của PHP và hệ điều hành, kiểm tra tác vụ thất bại trong Horizon |
| Hằng tháng | `media:prune-orphans --dry-run`, diễn tập khôi phục bản sao lưu, `audit:verify-chain` |
| Mỗi học kỳ | Rà soát việc gán vai trò (người đã nghỉ việc hoặc chuyển công tác), xóa bớt bản ghi kiểm toán đã quá thời hạn lưu giữ (`audit:prune`) |
