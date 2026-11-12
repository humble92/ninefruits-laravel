# Tính toàn vẹn kiểm toán và bảo trì định kỳ

## Chuỗi băm kiểm toán

Các bảng kiểm toán (`audit.record_changes` cho thay đổi dữ liệu, `audit.activity_log` cho hoạt động nghiệp vụ, `audit.security_events` cho sự kiện bảo mật) chỉ cho phép ghi thêm (được bảo đảm bằng trigger). Bên cạnh đó còn có một cơ chế giúp **làm lộ ra** mọi hành vi giả mạo.

- Mỗi dòng nhận một giá trị SHA-256 của nội dung (`row_hash`) khi được chèn vào.
- `audit:seal` (chạy theo lịch hằng ngày lúc 03:30) liên kết các dòng mới vào chuỗi theo thứ tự ID: `chain_hash = SHA-256(previous chain_hash || row_hash)`.
- `audit:verify-chain` tính lại giá trị băm nội dung và chuỗi cho từng dòng. Dòng có nội dung bị thay đổi được báo là "row mismatch", dòng bị mất hoặc bị đảo thứ tự được báo là "chain mismatch", và lệnh trả về mã thoát 2.

Những điểm cần lưu ý

- Dòng bị thay đổi trước khi được niêm phong (trong cùng ngày) thì chuỗi không phát hiện được. Vì vậy việc niêm phong chạy hằng ngày, còn việc xác minh chạy trước khi sao lưu và trong các đợt rà soát bảo mật.
- Khi việc xóa bớt (`audit:prune`) loại bỏ các dòng cũ nhất, liên kết của dòng đầu tiên còn lại sẽ không được kiểm tra (phạm vi đã xóa được lưu trong bản ghi kiểm toán của lệnh xóa bớt).
- Chỉ quản trị viên cơ sở dữ liệu (chủ sở hữu schema) mới có thể đặt `app.audit_maintenance = on`. Vai trò của ứng dụng không có quyền UPDATE hay DELETE.

## Thời hạn lưu giữ và xóa bớt

1. Quyết định thời hạn lưu giữ (ví dụ thay đổi dữ liệu 3 năm, hoạt động nghiệp vụ 5 năm, sự kiện bảo mật 1 năm).
2. Sao lưu trước khi xóa bớt ([Sao lưu và khôi phục](backup-restore.md)) và xác nhận chuỗi còn nguyên vẹn bằng `audit:verify-chain`.
3. Kiểm tra số lượng bằng `audit:prune --before=YYYY-MM-DD --table=record_changes --dry-run`, sau đó chạy lại không có `--dry-run`. Các dòng chưa niêm phong không bao giờ bị xóa, vì vậy hãy chạy `audit:seal` trước.
4. Chạy lại `audit:verify-chain` sau khi xóa bớt.

Bản thân việc xóa bớt cũng được ghi lại: mỗi lần chạy ghi vào nhật ký hoạt động (log `command`) ngày mốc, các tùy chọn, người thực hiện và người dùng hệ điều hành, cùng với **số dòng đã xóa, phạm vi ID đã xóa và chain_hash tại ranh giới** cho từng bảng; một lần xóa thực sự còn được ghi thành sự kiện bảo mật `audit.pruned` (**Kiểm toán > Sự kiện bảo mật**). Chính dòng bản ghi này cũng được liên kết vào chuỗi ở lần niêm phong kế tiếp. Ranh giới này chính là lý do `audit:verify-chain` không kiểm tra liên kết của dòng đầu tiên còn lại; so sánh giá trị băm ranh giới với bản ghi xóa bớt cho phép xác nhận rằng phạm vi đã xóa khớp với một lần xóa bớt đã được ghi lại.

## Dọn dẹp tệp

`media:prune-orphans --dry-run` liệt kê các dòng media mà đối tượng sở hữu không còn tồn tại và các thư mục không có dòng tương ứng; khi không có `--dry-run`, lệnh sẽ xóa chúng. Phạm vi bao gồm ảnh cá nhân, tài liệu hồ sơ xét tuyển, tệp PDF giấy chứng nhận và minh chứng kỳ thi. Chạy hằng tháng hoặc sau một đợt xóa hàng loạt.

## Kiểm tra tình trạng hệ thống

`system:health-check` kiểm tra cấu hình và khóa, vai trò cơ sở dữ liệu và RLS, migration, danh mục quyền, Redis và hàng đợi (Horizon), Gotenberg, bộ lưu trữ và nhịp tín hiệu của bộ lập lịch (được ghi vào cache mỗi phút), và trả về mã thoát 2 khi có vấn đề. Dùng `--json` để kết nối với hệ thống giám sát.
