# 28. Tích hợp LMS

Menu: **Tích hợp LMS > Nền tảng LMS**, **Liên kết khóa học**, **Nhật ký đồng bộ** (quyền `Quản lý nền tảng LMS, liên kết và đồng bộ`; mặc định: Cán bộ đào tạo, ngoài Quản trị hệ thống và Cán bộ quản lý sinh viên). Giảng viên dùng các liên kết LMS trong **Giảng dạy của tôi** và trong chi tiết lớp học phần, và **Tải điểm từ LMS** trên màn hình nhập điểm. Việc tải điểm tuân theo cùng điều kiện như nhập điểm (giảng viên trong khung thời gian nhập điểm, cán bộ trong phạm vi quản lý `Điểm` của mình).

Thông tin chi tiết dành cho nhà phát triển có trong [hướng dẫn tích hợp LMS](../../../lms-integration-guide.md).

## Dữ liệu nào đi đâu

| SIS → LMS | Thời điểm |
|---|---|
| Lớp học phần (tạo khóa học) + giảng viên | Khi lớp học phần được **công bố**, và bằng `lms:sync` |
| Lượt đăng ký đã xác nhận → ghi danh | Ngay khi đăng ký, chuyển lên từ danh sách chờ hoặc cán bộ đăng ký |
| Hủy đăng ký hoặc rút học phần → hủy ghi danh | Ngay lập tức |
| Đối soát toàn bộ | Hằng ngày lúc 02:30 (`lms:reconcile`) |

| LMS → SIS | Thời điểm |
|---|---|
| Điểm cuối kỳ (bản nháp) | Khi giảng viên nhấn **Tải điểm từ LMS** trên màn hình nhập điểm. Việc lưu và nộp do giảng viên thực hiện |

Điểm chính thức không được gửi sang LMS. Tài liệu học tập, bài tập và bài kiểm tra nằm trong LMS.

## Nền tảng

**Nền tảng LMS**: mã, tên, trình kết nối, URL gốc, thông tin xác thực (khóa/giá trị, được lưu dưới dạng mã hóa, được giữ nguyên khi để trống lúc chỉnh sửa), đang hoạt động, đồng bộ danh sách lớp, lấy điểm cuối kỳ.

- Trình kết nối **Không có (chỉ ghi lại)**: không gọi gì cả và ghi lại những gì sẽ được gửi. Dùng để kiểm tra luồng xử lý hoặc để trình diễn trước khi tích hợp. Nền tảng `DEMO` trong dữ liệu demo dùng trình kết nối này.
- `Moodle`, `Open edX`: chưa có trình kết nối (được đánh dấu chưa được triển khai). Khi chọn, các lần đồng bộ được ghi nhận là thất bại.
- Bật **Đang hoạt động** sẽ khiến việc công bố lớp học phần và thay đổi đăng ký được chuyển đi ngay từ thời điểm đó và đưa nền tảng vào lần đối soát hằng đêm.

## Liên kết khóa học và nhật ký đồng bộ

- **Liên kết khóa học**: lớp học phần nào được liên kết với khóa học nào trên nền tảng (id, URL), trạng thái (Chờ xử lý/Đã liên kết/Thất bại), lần đồng bộ gần nhất và lỗi. **Đồng bộ lại** gửi lại lớp học phần, giảng viên và danh sách lớp.
- **Nhật ký đồng bộ**: kết quả và thông điệp theo từng bước (khóa học, thêm và xóa thành viên, lấy điểm, đối soát). Xem tại đây để tìm nguyên nhân thất bại.
- Lệnh: `lms:sync {platform} --term={term}` (gửi toàn bộ, `--dry-run`), `lms:reconcile` (đối soát).

## Cổng thông tin

- Liên kết khóa học trên nền tảng được hiển thị trong chi tiết học phần cho sinh viên và giảng viên, và trong danh sách **Giảng dạy của tôi** của giảng viên (chỉ các lớp học phần đã liên kết).
- Nhập điểm: khi nền tảng bật **Lấy điểm cuối kỳ** và lớp học phần đã được liên kết, nút **Tải điểm từ LMS** sẽ xuất hiện. Nút này chỉ điền vào cột điểm số và không lưu.

## Câu hỏi thường gặp

- **Một sinh viên bị thiếu trong LMS.** Sinh viên cần có tài khoản (người dùng) để được đồng bộ. Nếu chưa có, hãy mời sinh viên ([chương 14](14-merge-and-invitations.md)); lần đối soát tiếp theo sẽ thêm sinh viên.
- **Một lần đồng bộ vẫn ở trạng thái thất bại.** Đọc thông điệp trong nhật ký đồng bộ. Thông tin xác thực hết hạn là nguyên nhân thường gặp. Thay đổi giá trị trên màn hình chỉnh sửa nền tảng và nhấn **Đồng bộ lại**.
- **Chúng tôi đã chuyển sang nền tảng khác.** Tạo nền tảng mới và tắt Đang hoạt động của nền tảng cũ. Liên kết được tạo riêng cho từng nền tảng.
