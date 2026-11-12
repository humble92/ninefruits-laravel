# 17. Học phần và điều kiện tốt nghiệp

Menu: **Chương trình và lớp học phần > Học phần**, **Điều kiện tốt nghiệp** (quyền `Quản lý học phần, phiên bản và điều kiện học phần`, `Quản lý điều kiện tốt nghiệp`)

## Học phần và phiên bản

Một học phần gồm một **mã** không thay đổi và các **phiên bản** có thời gian hiệu lực. Tên học phần, số tín chỉ, số giờ, loại học phần, hình thức đánh giá và điều kiện học phần thuộc về phiên bản. Hệ thống cũ (YUSTIC) phân biệt học phần theo mã học phần và ngày bắt đầu, nhưng điểm chỉ gắn với mã học phần, nên không rõ sinh viên đã học phiên bản nào. Trong hệ thống mới, lớp học phần và điểm gắn với một phiên bản.

- Thời gian hiệu lực của các phiên bản thuộc cùng một học phần không được chồng lấn nhau (ràng buộc cơ sở dữ liệu). Vào một ngày bất kỳ, có đúng một phiên bản đang có hiệu lực.
- Lớp học phần được mở với phiên bản có hiệu lực vào ngày bắt đầu của đợt học.

| Trường | Mô tả |
|---|---|
| Mã học phần | Ví dụ `CSE2010`. Mã không mang ý nghĩa nghiệp vụ (các quy tắc như kiểm tra ngân hàng tín chỉ `009*` của hệ thống cũ được thay bằng các trường bên dưới) |
| Đơn vị quản lý | Đơn vị tổ chức quản lý học phần. Là đơn vị mở lớp mặc định của các lớp học phần |
| Tên học phần, Mô tả | Theo từng ngôn ngữ |
| Số tín chỉ, số giờ lý thuyết và thực hành | Theo bước 0,5 |
| Loại học phần | Dữ liệu danh mục > Loại học phần |
| Hình thức đánh giá | Điểm chữ hoặc Đạt/không đạt |
| Tính vào giới hạn tín chỉ | Khi tắt, học phần không được tính vào giới hạn tín chỉ khi đăng ký (học phần ngân hàng tín chỉ và các học phần tương tự) |
| Đánh giá học phần | Khi tắt, học phần không được đánh giá (thực tập, kiến tập và các học phần tương tự; [chương 22](22-course-evaluation.md)) |
| Được học lại để tích lũy thêm tín chỉ | Học lại sẽ được tích lũy tín chỉ lần nữa thay vì tính là học lại (các seminar có chủ đề thay đổi và tương tự) |

## Sửa đổi học phần

Khi số tín chỉ hoặc tên học phần thay đổi, đừng chỉnh sửa phiên bản mà hãy **sửa đổi** nó. Trên màn hình **Chỉnh sửa** học phần, trong thẻ **Phiên bản**, nhấn **Sửa đổi từ một ngày** trên phiên bản hiện tại và nhập ngày bắt đầu của phiên bản mới, các giá trị thay đổi và lý do.

- Phiên bản hiện tại kết thúc vào ngày trước ngày bắt đầu; phiên bản mới bắt đầu với bản sao của phiên bản hiện tại và các điều kiện học phần của nó.
- Các lớp học phần đã mở và điểm trong quá khứ vẫn gắn với phiên bản cũ.
- Chỉ chỉnh sửa trực tiếp một phiên bản khi sửa lỗi không làm thay đổi ý nghĩa, chẳng hạn lỗi chính tả (các thay đổi này xuất hiện trong bản ghi thay đổi dữ liệu).

## Học phần tiên quyết và học phần song hành

Được nhập trong **Điều kiện học phần** ở cuối màn hình chỉnh sửa phiên bản.

| Trường | Mô tả |
|---|---|
| Loại | Học phần tiên quyết (phải hoàn thành trước) hoặc Học phần song hành (học cùng trong một học kỳ; được coi là đáp ứng nếu đã hoàn thành) |
| Nhóm | Chỉ cần hoàn thành một học phần trong cùng nhóm (hoặc). Phải đáp ứng mọi nhóm (và) |
| Điểm tối thiểu | Một mã điểm của thang điểm mặc định (ví dụ `C0`). Để trống: điểm đạt |
| Được học cùng học kỳ | Khi học phần tiên quyết có thể học trong cùng học kỳ |

Ví dụ: nhóm 1 gồm `CSE1001`, nhóm 2 gồm `MAT1001` và `MAT1002` → `CSE1001` và (`MAT1001` hoặc `MAT1002`). Quy tắc học phần tiên quyết kiểm tra điều này khi đăng ký học phần ([chương 20](20-registration.md)).

## Học phần tương đương

Khi đổi mới chương trình đào tạo thay thế một học phần, hãy nhập học phần thay thế cùng thời gian hiệu lực trong thẻ **Học phần tương đương** của màn hình **Chỉnh sửa** học phần. Khi bật **Hai chiều**, mỗi học phần được tính cho học phần kia; khi tắt, chỉ học phần tương đương được tính cho học phần này. Được dùng trong kiểm tra học lại ([chương 20](20-registration.md)) và xét tốt nghiệp (giai đoạn 8).

## Điều kiện tốt nghiệp

Mỗi bộ điều kiện được tạo cho một chương trình đào tạo, một **Vai trò chương trình** (ngành chính, ngành thứ hai, ngành phụ) và một khoảng **khóa CTĐT** (áp dụng từ/đến khóa). Sinh viên áp dụng bộ điều kiện khớp với khóa CTĐT trên hồ sơ sinh viên của mình.

- Các khoảng khóa CTĐT của cùng chương trình đào tạo và vai trò chương trình không được chồng lấn nhau. Để trống khóa kết thúc để bộ điều kiện có hiệu lực cho đến khi bộ tiếp theo bắt đầu.
- Các loại điều kiện

| Loại | Thông tin nhập | Ví dụ |
|---|---|---|
| Số tín chỉ theo loại học phần | Loại học phần, số tín chỉ tối thiểu | 9 tín chỉ giáo dục đại cương (bắt buộc) |
| Học phần bắt buộc | Danh sách học phần | Cả 4 học phần chuyên ngành bắt buộc |
| Lựa chọn từ nhóm học phần | Danh sách học phần, số tín chỉ tối thiểu hoặc số học phần tối thiểu | 12 tín chỉ từ các học phần chuyên ngành tự chọn |
| Đạt kỳ thi | Loại kỳ thi | Đạt kỳ thi tổng hợp tiếng Anh ([chương 24](24-assessments.md)) |

Ngành thứ hai và ngành phụ có bộ điều kiện riêng, nhỏ hơn so với ngành chính (ví dụ 36 tín chỉ cho ngành thứ hai, 21 tín chỉ cho ngành phụ). Các điều kiện được kiểm tra khi xét tốt nghiệp (giai đoạn 8).
