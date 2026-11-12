# Quản lý khóa

- Đối tượng: cán bộ vận hành CNTT

| Khóa | Mục đích | Nếu bị mất | Xoay vòng |
|---|---|---|---|
| `APP_KEY` | Phiên làm việc, cookie, bí mật xác thực hai yếu tố, ký cookie thiết bị được ghi nhớ | Mọi người dùng bị đăng xuất; xác thực hai yếu tố đã đăng ký (ứng dụng xác thực) ngừng hoạt động | Đưa khóa cũ vào `APP_PREVIOUS_KEYS`, sau đó xoay vòng |
| `PII_ENCRYPTION_KEY` | Mã hóa dữ liệu cá nhân như số định danh cá nhân | **Dữ liệu cá nhân đã mã hóa không thể khôi phục** | Quy trình bên dưới |
| `PII_HASH_KEY` | Giá trị băm để tìm kiếm khớp chính xác dữ liệu cá nhân | Tìm kiếm ngừng hoạt động (dữ liệu vẫn còn) | Theo nguyên tắc không xoay vòng |

Nguyên tắc lưu trữ

- Giữ ít nhất hai bản sao của ba khóa bên ngoài cấu hình môi trường của máy chủ, trong kho lưu trữ bí mật (Vault, dịch vụ quản lý bí mật trên đám mây, v.v.) hoặc nơi lưu trữ ngoại tuyến được niêm phong.
- Không bao giờ lưu chúng cùng với các tệp sao lưu. Ngay cả khi bản sao lưu cơ sở dữ liệu bị rò rỉ, dữ liệu cá nhân vẫn phải không đọc được nếu không có khóa.

## Quy trình xoay vòng khóa dữ liệu cá nhân

1. Tạo khóa mới bằng `php artisan pii:generate-key` (chỉ dùng `PII_ENCRYPTION_KEY` trong kết quả).
2. Đưa khóa hiện tại vào `PII_PREVIOUS_KEYS` (phân cách bằng dấu phẩy nếu có nhiều khóa) và khóa mới vào `PII_ENCRYPTION_KEY`.
3. Nạp lại cấu hình: `php artisan config:cache`, `php artisan horizon:terminate`.
4. Với từng model dữ liệu cá nhân, kiểm tra số bản ghi bị ảnh hưởng bằng `php artisan pii:reencrypt {model} --dry-run`, sau đó chạy lại không có `--force`.
5. Khi Horizon cho thấy lô đã hoàn tất và `--dry-run` báo 0, hãy xóa khóa cũ khỏi `PII_PREVIOUS_KEYS`.
6. Giữ khóa cũ ở trạng thái niêm phong trong suốt thời hạn lưu giữ bản sao lưu (cần thiết để khôi phục các bản sao lưu cũ hơn).
