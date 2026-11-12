# 25. Lớp học của giảng viên và điểm danh

Menu: giảng viên dùng **Giảng dạy của tôi > Danh sách lớp và điểm danh** trên cổng thông tin, sinh viên dùng **Điểm danh** trên cổng thông tin. Cán bộ dùng **Buổi học** ở cuối màn hình **Chương trình và lớp học phần > Lớp học phần > Chỉnh sửa**.

| Đối tượng | Có thể làm gì |
|---|---|
| Giảng viên được phân công | Danh sách lớp (kèm ảnh) và CSV, bảng điểm danh PDF, tạo, thêm và hủy buổi học, điểm danh |
| Cán bộ có quyền `Lập kế hoạch lớp học phần` (trong phạm vi) | Như giảng viên + buổi học trong trang quản trị |
| Cán bộ có quyền `Xem lớp học phần` (trong phạm vi) | Danh sách lớp, bảng điểm danh, xem điểm danh |
| Sinh viên | Kết quả điểm danh của chính mình |

Các chức năng lớp học như thông báo, bài tập và tài liệu được giao cho LMS (kế hoạch chuyển đổi mục 12).

## Khác biệt so với hệ thống cũ

- **Hệ thống cũ (YUSTIC) không ghi nhận điểm danh.** "Bảng điểm danh" của hệ thống cũ là một tệp PDF in các ô trống (từ 27 đến 34 ô, khác nhau tùy bản) bên cạnh danh sách lớp, không có ngày và không có tổng hợp. Hệ thống mới tạo **buổi học** từ lịch học, ghi nhận có mặt, đi muộn, vắng hoặc vắng có phép cho từng buổi, và in kết quả cùng tổng hợp lên bảng điểm danh PDF.
- **Danh sách lớp.** Màn hình giảng viên của hệ thống cũ chỉ hiển thị mã số sinh viên và họ tên (và để lộ số đăng ký cư trú và địa chỉ trong một cửa sổ bật lên) và không có chức năng tải Excel. Hệ thống mới chỉ hiển thị mã số sinh viên, họ tên, ảnh, chương trình đào tạo, năm thứ và email liên hệ, và tải xuống dưới dạng CSV. Việc tải xuống được ghi vào nhật ký kiểm toán.
- **Quyền truy cập của giảng viên đối với sinh viên của mình.** Một chính sách RLS được bổ sung để giảng viên có thể xem hồ sơ sinh viên và thông tin cá nhân của sinh viên trong các lớp học phần của mình (trước đây họ chỉ xem được sinh viên được cố vấn, khiến họ tên bị trống ngay cả trên màn hình nhập điểm).

## Buổi học

- **Tạo buổi học**: mỗi lần diễn ra của lịch học hằng tuần (thứ, giờ, tiết) tạo một buổi học. Giảng viên dùng nút **Tạo từ lịch học**; cán bộ dùng **Buổi học > Tạo buổi học** trong trang quản trị hoặc lệnh `attendance:generate-sessions {term}` (cả học kỳ). Các buổi học và kết quả điểm danh hiện có được giữ nguyên, nên có thể chạy lại sau khi lịch học thay đổi.
- **Thêm buổi học**: buổi học ngoài lịch học, chẳng hạn buổi học bù. Chỉ các ngày trong đợt học.
- **Hủy**: hủy buổi học kèm lý do. Buổi học vẫn còn nhưng không được tính vào tổng hợp. **Khôi phục** đưa buổi học trở lại.
- Không có lịch ngày nghỉ. Hãy hủy các buổi học rơi vào ngày nghỉ lễ (xem mục 13.2; việc này sẽ được tự động hóa khi có lịch học kỳ).

## Điểm danh

Trong **Điểm danh** của buổi học, chọn có mặt, đi muộn, vắng hoặc vắng có phép cho từng sinh viên và thêm ghi chú (200 ký tự). Cách nhanh nhất là nhấn **Tất cả có mặt** rồi sửa các trường hợp ngoại lệ.

- Có thể điểm danh từ ngày diễn ra buổi học (không điểm danh cho các buổi học trong tương lai).
- Lưu lại chỉ ghi nhận các sinh viên có thay đổi, và sự kiện kiểm toán (`attendance.recorded`) lưu giá trị cũ và mới.
- Xóa trạng thái sẽ đưa sinh viên về "chưa điểm danh".

## Tổng hợp

| Mục | Định nghĩa |
|---|---|
| Số buổi đã diễn ra | Các buổi học không bị hủy và có ngày từ hôm nay trở về trước |
| Tỷ lệ chuyên cần | (có mặt + đi muộn + vắng có phép) ÷ số buổi đã diễn ra × 100 |
| Chưa điểm danh | Các buổi đã diễn ra nhưng chưa có kết quả điểm danh |

Không có quy tắc quy đổi số lần đi muộn thành một buổi vắng hay cho điểm F khi vắng quá một phần ba. Điều này khác nhau giữa các trường, nên giảng viên tự phản ánh điểm danh vào điểm (mục xem xét 13.5).

## Bảng điểm danh PDF và danh sách lớp CSV

Cả hai lần tải xuống đều được ghi vào nhật ký kiểm toán (`attendance.sheet_exported`; danh sách lớp CSV như trước). Các giá trị CSV bắt đầu bằng `=`, `+`, `-` hoặc `@` được xuất kèm dấu nháy đơn ở đầu để không bị thực thi như công thức.

- **Bảng điểm danh (PDF)**: khổ A4 ngang. Sinh viên theo hàng dọc, buổi học theo hàng ngang (bỏ qua buổi đã hủy), ký hiệu O/△/X/P và tổng hợp theo từng sinh viên. Các buổi học sắp tới để trống, nên cũng có thể dùng để điểm danh bằng tay.
- **Danh sách lớp (CSV)**: mã số sinh viên, họ tên, chương trình đào tạo, năm thứ, email liên hệ, trạng thái đăng ký. Mã hóa UTF-8 có BOM nên mở trực tiếp được trong Excel. Mỗi lần tải xuống được ghi vào sự kiện kiểm toán `attendance.roster_exported`.

## Cổng thông tin sinh viên

**Điểm danh**: theo học kỳ và học phần, số buổi có mặt, đi muộn, vắng và vắng có phép, tỷ lệ chuyên cần, và kết quả theo từng buổi học (chỉ những gì giảng viên đã nhập).

## Quy trình vận hành

1. Trước khi học kỳ bắt đầu, hoàn tất lịch học của các lớp học phần ([chương 18](18-sections-and-timetables.md)).
2. Tạo buổi học bằng `php artisan attendance:generate-sessions {term} --actor=...` (giảng viên cũng có thể tạo trên màn hình).
3. Chạy lại khi lịch học thay đổi. Buổi học của các lịch đã bị xóa không tự động bị xóa; hãy hủy chúng.
