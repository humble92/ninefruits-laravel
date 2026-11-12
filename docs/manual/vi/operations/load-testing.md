# Kiểm thử tải (cao điểm đăng ký học phần)

Dùng k6 để tái hiện tình huống nhiều sinh viên cùng lúc dồn vào các lớp học phần giống nhau, như vào buổi sáng đầu tiên của đợt đăng ký học phần. Script là `tests/load/registration.js`.

## Chuẩn bị

1. Tạo một bộ dữ liệu demo lớn và gắn tài khoản sinh viên (tệp CSV danh sách tài khoản được ghi vào `storage/app/private/load-accounts.csv`).

   ```bash
   ./vendor/bin/sail artisan setup:demo --size=large --seed=42 --student-accounts
   ```

2. Phải có một khung thời gian đăng ký học phần đang mở (dữ liệu demo mở khung thời gian đăng ký của đợt học trong học kỳ hiện tại).
3. Tiến trình xử lý hàng đợi (Horizon) và Redis phải đang chạy. Việc giữ chỗ và chuyển sinh viên từ danh sách chờ được xử lý qua hàng đợi.

## Chạy kiểm thử

```bash
docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
    --vus 200 --duration 2m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/private/load-accounts.csv tests/load/registration.js
```

- Tăng `--vus` từ 100 lên 200 rồi 500 và theo dõi p95 của `http_req_duration` cùng tỷ lệ thất bại. Mục tiêu: p95 của màn hình đăng ký < 800ms, p95 của yêu cầu đăng ký < 1.5s, tỷ lệ thất bại < 1%.
- Ở phía máy chủ, theo dõi thêm số phiên đang chờ (khóa) trong `pg_stat_activity`, độ trễ xử lý của Horizon và mức bão hòa của worker php-fpm.
- Khi chạy với Sail, hãy tăng số worker của máy chủ tích hợp (`PHP_CLI_SERVER_WORKERS` trong `.env`, mặc định 4). Số worker giới hạn thông lượng.

## Những gì thiết kế bảo đảm

- Sĩ số được bảo vệ bằng khóa dòng trong cơ sở dữ liệu (`SeatAllocator::lock`), vì vậy ngay cả khi có một loạt yêu cầu đồng thời, lớp học phần cũng không thể bị đăng ký vượt sĩ số (`ConcurrentRegistrationTest`).
- Việc giữ chỗ chính xác chỉ với cơ sở dữ liệu, không cần Redis hay hàng đợi, nhưng việc chuyển sinh viên từ danh sách chờ và gửi thông báo được xử lý qua hàng đợi.

## Quyết định về Octane

Nếu p95 vượt mục tiêu trên một php-fpm đơn lẻ dưới Sail, trước tiên hãy điều chỉnh số worker php-fpm và `max_connections` của PostgreSQL hoặc PgBouncer. Nếu vẫn chưa đủ, hãy cân nhắc Laravel Octane (FrankenPHP hoặc Swoole). Trước khi chuyển sang Octane, cần kiểm tra: trạng thái phải được đặt lại sau mỗi yêu cầu (các biến phiên của `DatabaseSessionContext` được hoàn nguyên bởi `reset()` khi kết thúc yêu cầu, nên vẫn an toàn khi dùng Octane), dữ liệu người dùng còn sót lại trong singleton, cache tĩnh. Quyết định được ghi vào báo cáo Giai đoạn 13 sau khi xem xét số liệu tải thực tế.

## Kết quả ngày 2026-10-23

Container Sail cho môi trường phát triển (máy chủ tích hợp, không có opcache), 100 VU trong một phút: 24.7 req/s với 4 worker, 34.5 req/s với 16 worker, 0 lỗi HTTP, 0 trường hợp đăng ký vượt sĩ số, 4 kết nối PostgreSQL. Thời gian xử lý trên máy chủ cho mỗi yêu cầu khoảng 120 ms đối với trang và 190 ms đối với một lượt đăng ký; độ trễ nhiều giây ở mức 100 VU là do xếp hàng chờ worker. Thông lượng tăng theo số worker PHP và cơ sở dữ liệu còn cách xa giới hạn, vì vậy **không áp dụng Octane**: số worker php-fpm và việc mở rộng ngang các máy chủ ứng dụng là đủ. Hãy chạy lại với 200 và 500 VU trên môi trường staging dùng php-fpm + opcache trước khi đưa vào vận hành chính thức. Số liệu đầy đủ: [Báo cáo Giai đoạn 13](../../../../porting/phase-13-report.md) mục 7.
