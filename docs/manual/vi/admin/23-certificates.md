# 23. Giấy chứng nhận

Menu: **Giấy chứng nhận > Giấy chứng nhận đã cấp**, **Loại giấy chứng nhận**, **Cài đặt > Cài đặt giấy chứng nhận**. Giấy chứng nhận được cấp bằng nút **Cấp giấy chứng nhận** trên màn hình hồ sơ sinh viên (**Sinh viên và cán bộ > Hồ sơ sinh viên > Xem**). Sinh viên tải tệp PDF giấy chứng nhận của mình trên cổng thông tin trong **Giấy chứng nhận**. Bất kỳ ai cũng có thể xác minh giấy chứng nhận trên trang `/verify/{code}`.

| Quyền | Cho phép |
|---|---|
| `Cấp, tạo lại và thu hồi giấy chứng nhận` | Cấp, tạo lại, thu hồi, xem các giấy chứng nhận đã cấp (trong phạm vi xem hồ sơ sinh viên) |
| `Quản lý loại giấy chứng nhận, mẫu và cài đặt` | Các loại và mẫu theo từng ngôn ngữ, mẫu số hiệu, người ký |

Mặc định theo vai trò: Cán bộ cấp giấy chứng nhận cấp giấy, Cán bộ quản lý sinh viên có cả hai quyền, Cán bộ đào tạo quản lý loại và mẫu.

## Khác biệt so với hệ thống cũ

- **Sổ đăng ký giấy chứng nhận đã cấp.** Hệ thống cũ (YUSTIC) in trực tiếp từ màn hình (`aca3/cti_*`), và sổ đăng ký (`SRCERT`) chỉ tồn tại để sinh số, nên không cho biết ai đã cấp giấy chứng nhận hay giấy đó có bị thu hồi hay không. Hệ thống mới ghi lại số hiệu, mã xác minh, cán bộ, mục đích, số bản, tệp PDF và checksum cho mỗi lần cấp, và giấy chứng nhận bị thu hồi vẫn giữ dòng dữ liệu của nó.
- **Xác minh.** Hệ thống cũ không có. Mỗi giấy chứng nhận mang một mã xác minh và mã QR, và một trang công khai cho biết giấy đã được cấp, loại, ngày cấp và người sở hữu đã được che bớt (chữ cái đầu và cuối của họ và tên, 4 chữ số đầu của mã số sinh viên).
- **Mẫu.** Hệ thống cũ có một script PDF riêng cho mỗi loại và ngôn ngữ. Hệ thống mới chỉnh sửa một mẫu theo từng ngôn ngữ (tiêu đề, nội dung, chân trang, người ký) cho mỗi loại trên màn hình.
- **Chặn.** Hệ thống cũ cấp giấy chứng nhận bất kể danh sách cấm đăng ký. Hệ thống mới không cấp cho sinh viên đang bị chặn cấp giấy chứng nhận ([chương 20](20-registration.md)).
- Lệ phí không được ghi lại (hệ thống cũ chỉ ghi số tiền).
- Không có chức năng sinh viên yêu cầu trực tuyến (kế hoạch chuyển đổi 17.5). Cán bộ cấp giấy chứng nhận.

## Loại và mẫu

**Loại giấy chứng nhận**: mã (dùng trong số hiệu), tên (3 ngôn ngữ), đối tượng (mọi hồ sơ sinh viên / sinh viên / người đã tốt nghiệp), kèm điểm, kèm ảnh, đang hoạt động, thứ tự. Hồ sơ trường đại học Hàn Quốc khởi tạo sẵn ba loại.

| Mã | Loại | Đối tượng | Ghi chú |
|---|---|---|---|
| `ENR` | Giấy xác nhận sinh viên | Sinh viên (chưa tốt nghiệp) | |
| `GRD` | Giấy chứng nhận tốt nghiệp | Người đã tốt nghiệp | Ngày tốt nghiệp, số hiệu văn bằng |
| `TRN` | Bảng điểm | Mọi hồ sơ sinh viên | Bảng điểm chính thức theo học kỳ, điểm TB tích lũy và tín chỉ tích lũy |

**Mẫu** (ở cuối màn hình chỉnh sửa loại): mỗi ngôn ngữ một mẫu. Tiêu đề, nội dung (các đoạn cách nhau bằng một dòng trống), chân trang, người ký. Nội dung dùng các biến thay thế trong dấu ngoặc nhọn.

| Biến thay thế | Giá trị |
|---|---|
| `{institution}` | Tên cơ sở đào tạo (bằng ngôn ngữ đó) |
| `{student_name}`, `{student_number}` | Họ tên (theo dạng của ngôn ngữ đó, nếu không có thì dùng cách viết Latinh), mã số sinh viên |
| `{program}`, `{org_unit}` | Chương trình đào tạo, đơn vị tổ chức |
| `{admission_date}`, `{graduation_date}`, `{degree_number}` | Ngày nhập học, ngày tốt nghiệp, số hiệu văn bằng |
| `{status}`, `{year_level}` | Tình trạng sinh viên, năm thứ |
| `{birth_date}`, `{gender}`, `{nationality}` | Ngày sinh, giới tính, quốc tịch |
| `{issue_date}`, `{serial_number}`, `{purpose}`, `{copies}` | Ngày cấp, số hiệu, mục đích, số bản |

Ngày được in theo định dạng của ngôn ngữ đó. Giá trị bị thiếu (ví dụ ngày tốt nghiệp) được in để trống. Một bảng thông tin cá nhân (họ tên, mã số sinh viên, ngày sinh, giới tính, quốc tịch, chương trình đào tạo, ngày nhập học, sau đó là ngày tốt nghiệp đối với người đã tốt nghiệp hoặc tình trạng và năm thứ đối với trường hợp khác) được tự động thêm phía trên nội dung.

## Cài đặt

**Cài đặt > Cài đặt giấy chứng nhận**

- **Mẫu số hiệu**: mặc định `{TYPE}-{YYYY}-{SEQ:6}` → `ENR-2026-000001`. Bắt buộc có `{SEQ:n}`. Số thứ tự được đếm riêng cho mỗi tiền tố khác nhau. `{TYPE}-{SEQ:6}` đếm theo loại qua các năm, `{YYYY}{SEQ:5}` đếm theo năm cho mọi loại. Các lần cấp đồng thời không bao giờ bị trùng (bộ đếm được tăng nguyên tử trong cơ sở dữ liệu).
- **Thời hạn xác minh (ngày)**: 0 nghĩa là không giới hạn. Sau thời hạn này, trang xác minh trả lời "hết hạn". Bản thân lần cấp vẫn nằm trong sổ đăng ký.
- **Người ký theo ngôn ngữ**: tên được in phía trên con dấu ("Hiệu trưởng, Trường Đại học Ninefruits"). Người ký trong mẫu được ưu tiên hơn.

## Cấp giấy chứng nhận

**Cấp giấy chứng nhận** trên màn hình hồ sơ sinh viên: loại, ngôn ngữ, mục đích, số bản (1 đến 20). Khi lưu, tệp PDF mở trong thẻ mới.

Việc cấp bị từ chối khi:

- đối tượng của loại không khớp (giấy xác nhận sinh viên cho người đã tốt nghiệp, giấy chứng nhận tốt nghiệp cho sinh viên đang học)
- loại không còn hoạt động
- không có mẫu cho ngôn ngữ đó
- sinh viên đang bị **chặn cấp giấy chứng nhận** (một chặn đăng ký, [chương 20](20-registration.md)); hãy gỡ chặn trước
- hồ sơ sinh viên nằm ngoài phạm vi xem

Việc cấp được ghi vào nhật ký hoạt động (`certificate.issued`: mã số sinh viên, loại, ngôn ngữ, số hiệu, số bản, mục đích).

## Giấy chứng nhận đã cấp

**Giấy chứng nhận > Giấy chứng nhận đã cấp**: ngày cấp, số hiệu, loại và ngôn ngữ, mã số sinh viên, họ tên, mục đích, số bản, trạng thái, cán bộ. Lọc theo loại và trạng thái. Phạm vi xem giống như đối với hồ sơ sinh viên.

- **PDF**: mở tệp đã lưu. Tệp không được tạo lại, nên giữ đúng như khi cấp.
- **Trang xác minh**: mở trang công khai.
- **Tạo lại**: tạo tệp PDF mới từ dữ liệu hiện tại, ví dụ sau khi sửa mẫu, và thay thế tệp. Số hiệu và mã xác minh được giữ nguyên; checksum thay đổi. Sự kiện kiểm toán `certificate.rerendered`. Tương đương lệnh `certificate:render`.
- **Thu hồi**: bắt buộc nhập lý do. Dòng dữ liệu và tệp được giữ lại, trạng thái chuyển thành đã thu hồi, và trang xác minh trả lời "đã thu hồi". Sự kiện kiểm toán `certificate.revoked`. Giấy chứng nhận đã thu hồi không được khôi phục; thay vào đó cấp một giấy mới.

Không thể sửa hoặc xóa các dòng trong sổ đăng ký trên màn hình.

## Xác minh

- URL xác minh (`/verify/{code}`) và mã QR được in ở cuối giấy chứng nhận. Mã là một chuỗi ngẫu nhiên gồm 20 ký tự.
- Trang mở được mà không cần đăng nhập và bị giới hạn 30 yêu cầu mỗi phút. Trang cho biết giấy chứng nhận có được cấp hay không, loại, số hiệu, ngày cấp, cùng họ tên và mã số sinh viên đã được che bớt. Mã đã thu hồi, đã hết hạn và không xác định mỗi loại nhận một câu trả lời khác nhau.
- Để kiểm tra chính tệp PDF được nộp, hãy so sánh checksum (SHA-256) trong sổ đăng ký với giá trị băm của tệp. Lệnh `certificate:verify {code}` cũng làm việc này.

## Cổng thông tin sinh viên

**Giấy chứng nhận**: các giấy chứng nhận được cấp trên hồ sơ của chính sinh viên, kèm tệp PDF. Giấy đã thu hồi được liệt kê cùng trạng thái, nhưng sinh viên không thể tải tệp PDF của giấy đã thu hồi (cán bộ cấp có thể mở để kiểm tra hồ sơ). Không thể mở tệp của sinh viên khác (được bảo đảm trong ứng dụng và cơ sở dữ liệu).

## Câu hỏi thường gặp

- **Họ tên bị viết sai.** Sửa biến thể họ tên của cá nhân (theo ngôn ngữ đó) rồi nhấn **Tạo lại**. Nếu giấy chứng nhận đã được nộp đi, hãy thu hồi và cấp giấy mới.
- **Tôi đã thay đổi mẫu; các giấy chứng nhận đã cấp thì sao?** Chúng không thay đổi. Nhấn **Tạo lại** nếu cần.
- **Một số hiệu bị bỏ qua.** Khi xảy ra lỗi trong lúc cấp (ví dụ dịch vụ PDF ngừng hoạt động), số đó đã được sử dụng và có thể không để lại dòng nào trong sổ đăng ký. Số hiệu được bảo đảm là duy nhất, không bảo đảm liên tiếp.
