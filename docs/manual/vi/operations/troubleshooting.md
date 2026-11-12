# Xử lý sự cố

- Đối tượng: cán bộ vận hành CNTT

| Triệu chứng | Những gì cần kiểm tra |
|---|---|
| Bị chuyển đến trang bảo mật ngay sau khi đăng nhập | Chính sách xác thực hai yếu tố là "bắt buộc" và thời gian ân hạn đã hết. Sự cố được giải quyết khi người dùng đăng ký. Chính sách nằm tại **Cài đặt > Bảo mật** |
| Lỗi 403 trong trang quản trị | Người dùng không có lượt gán vai trò còn hiệu lực với quyền `admin_panel.access`. Kiểm tra **Người dùng và phân quyền > Người dùng > Gán vai trò** |
| Thiếu một mục menu | Vai trò thiếu quyền cho màn hình đó (ví dụ `roles.view`) |
| Không nhận được mã qua email | Thiết lập thư (`MAIL_*`), các mục `Đã gửi mã qua email` trong sự kiện bảo mật, giới hạn gửi (một lần mỗi phút, năm lần mỗi giờ) |
| Màn hình hiển thị 0 dòng dữ liệu của người khác | Có thể là bình thường (bảo mật cấp dòng). Xác nhận rằng người thực hiện của phiên cơ sở dữ liệu đã được thiết lập bằng cách xem cột người dùng trong **Thay đổi dữ liệu** |
| `permission denied for schema public` | Ứng dụng đã cố thực thi DDL với `app_runtime`. Chỉ chạy migration bằng `php artisan migrate` (lệnh này tự động dùng `app_migrator`) |
| `Audit records are append-only` | Có một thao tác cố cập nhật hoặc xóa bảng kiểm toán. Việc chặn này là có chủ đích |
| Tạo PDF thất bại | `php artisan pdf:sample`, trạng thái của Gotenberg, `GOTENBERG_URL` |
| Tác vụ hàng đợi bị treo | Kiểm tra tác vụ thất bại trong `/horizon`, `php artisan horizon:status` |

Truy vết theo từng yêu cầu: với giá trị của header phản hồi `X-Request-Id`, bạn có thể tìm đồng thời trong log, nhật ký hoạt động, thay đổi dữ liệu và sự kiện bảo mật.
