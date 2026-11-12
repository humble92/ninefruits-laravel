# Tác vụ định kỳ và hàng đợi

- Đối tượng: cán bộ vận hành CNTT

## Tiến trình chạy liên tục

| Tiến trình | Lệnh | Kiểm tra |
|---|---|---|
| Tiến trình xử lý hàng đợi | `php artisan horizon` | `/horizon` (quyền `queue_monitor.view`) |
| Bộ lập lịch | cron `* * * * * php artisan schedule:run` | `php artisan schedule:list` |

## Tác vụ định kỳ

Mỗi giai đoạn bổ sung vào bảng này. Việc đăng ký nằm trong `routes/console.php`. Thời gian tính theo múi giờ của máy chủ (UTC).

| Tác vụ | Tần suất | Giai đoạn | Phát hiện lỗi |
|---|---|---|---|
| `admission:expire-drafts` | Hằng ngày lúc 03:10 | 3 | Được ghi vào nhật ký hoạt động (log `command`) sau mỗi lần chạy. Nếu quá một ngày không có bản ghi, hãy kiểm tra bộ lập lịch |
| `registration:release-reserved-seats` | Mỗi 5 phút | 5 | Không có bản ghi kiểm toán. Nếu các phân bổ chỗ đã qua thời điểm giải phóng mà `release_processed_at` vẫn trống, hãy kiểm tra bộ lập lịch |
| `waitlist:promote` | Hằng giờ | 5 | Được ghi vào nhật ký hoạt động (log `command`) sau mỗi lần chạy |

## Tác vụ hàng đợi

| Tác vụ | Được kích hoạt bởi | Ghi chú |
|---|---|---|
| `ReencryptPiiRecords` | `pii:reencrypt` | Theo lô. Chạy lại các tác vụ thất bại từ Horizon |
| `PromoteWaitlistJob` | Hủy đăng ký hoặc rút học phần, giải phóng phân bổ chỗ | Mỗi lớp học phần chỉ chạy một tác vụ (ngăn trùng lặp). Khi chuyển từ danh sách chờ, mọi quy tắc được kiểm tra lại |
| `CloseTermRecordsJob`, `FinishTermCloseJob` | `term:close`, khóa điểm học kỳ trong trang quản trị | Lô 100 sinh viên, sau đó là tác vụ hoàn tất (xếp hạng khen thưởng, đổi trạng thái học kỳ). Theo dõi tiến độ tại lô Horizon `term-close:{term code}`. Chạy lại các tác vụ thất bại từ Horizon (phép tính cho từng sinh viên cho cùng kết quả khi chạy lại) |
| `ImportCsv` (Filament) | Nhập CSV trong trang quản trị, `reference:import` | Theo lô. Việc hoàn tất được báo bằng thông báo trong trang quản trị (bảng `notifications`). Các dòng lỗi được đưa vào `failed_import_rows` |
| `audit:seal` | Hằng ngày lúc 03:30 | Niêm phong chuỗi băm kiểm toán ([Tính toàn vẹn kiểm toán](audit-and-maintenance.md)) |
| Nhịp tín hiệu của bộ lập lịch | Mỗi phút | Ghi thời điểm chạy gần nhất vào cache. `system:health-check` coi việc quá 5 phút không có tín hiệu là lỗi |

Trong khi một tác vụ hàng đợi đang chạy, người thực hiện trong cơ sở dữ liệu được ghi là `system:job:{class}`.
