# 9. Nhập CSV

Dùng nút **Nhập CSV** ở đầu các màn hình đơn vị tổ chức, chương trình đào tạo, tòa nhà, phòng học và danh mục mã (quyền `Nhập dữ liệu danh mục từ CSV`). Danh sách hồ sơ sinh viên và danh sách giảng viên cũng có nút này (lần lượt cần quyền `Tạo và sửa hồ sơ sinh viên` và `Quản lý giảng viên`). Với các tệp rất lớn, người vận hành CNTT có thể nhập theo cùng cách bằng lệnh `reference:import`.

## Quy tắc chung

- Dòng đầu tiên chứa tên cột (xem các bảng bên dưới). Mã hóa ký tự là UTF-8; trong Excel, hãy lưu dưới dạng "CSV UTF-8".
- Dòng có mã đã tồn tại sẽ **cập nhật** bản ghi đó. Nhập lại cùng một tệp không tạo bản trùng lặp.
- Các cột theo ngôn ngữ có dạng `cột_ngôn ngữ`: `name_en`, `name_ko`, `name_zh_CN`. Dòng mới bắt buộc có tên tiếng Anh (`name_en`). Ô trống không xóa các bản dịch hiện có.
- Ngày được viết dạng `2026-03-01`; có/không viết là `1`/`0` hoặc `true`/`false`.
- Việc nhập chạy ở chế độ nền. Khi hoàn tất, kết quả được gửi dưới dạng thông báo ở góc trên bên phải của trang quản trị; nếu có dòng bị lỗi, **Tải xuống thông tin về những hàng bị lỗi** cung cấp một tệp CSV ghi lý do của từng dòng. Hãy sửa tệp đó và chỉ nhập lại tệp đó.
- Nếu tên cột trên màn hình khác, bạn có thể ghép cột thủ công trong hộp thoại nhập.

## Định dạng cột

| Loại | Cột bắt buộc | Cột không bắt buộc |
|---|---|---|
| Đơn vị tổ chức | `code`, `type` (mã loại đơn vị), `name_en`, `effective_start_date` | `parent` (mã đơn vị cấp trên), `name_ko` v.v., `short_name_*`, `effective_end_date`, `sort_order` |
| Chương trình đào tạo | `code`, `org_unit` (mã đơn vị tổ chức), `degree_level`, `name_en`, `effective_start_date` | `degree_title_*`, `standard_duration_years`, `effective_end_date`, `allows_second_major`, `allows_minor` |
| Tòa nhà | `code`, `name_en` | `name_*`, `address`, `is_active` |
| Phòng học | `building` (mã tòa nhà), `code` (số phòng) | `name_*`, `room_type` (mã loại phòng), `org_unit`, `capacity`, `is_schedulable`, `is_active` |
| Giá trị mã | `type` (mã danh mục), `code`, `name_en` | `name_*`, `sort_order`, `is_active` |

Giá trị của `degree_level`: `associate`, `bachelor`, `master`, `doctorate`, `certificate`, `non_degree`

Với đơn vị tổ chức, đơn vị cấp trên phải đứng trước (sắp xếp theo thứ tự trường đại học → trường thành viên → khoa).

## Ví dụ: đơn vị tổ chức

```csv
code,type,parent,name_en,name_ko,effective_start_date
UNI,university,,Sample University,샘플대학교,1990-03-01
ENG,college,UNI,College of Engineering,공과대학,1990-03-01
CSE,department,ENG,Computer Science,컴퓨터공학과,1995-03-01
```

Chức năng nhập cho sinh viên và giảng viên (Giai đoạn 2) và học phần (Giai đoạn 4) được bổ sung cùng với các chức năng đó.

## Sinh viên và giảng viên

Hồ sơ sinh viên được đối chiếu và cập nhật theo `student_number`, giảng viên theo `employee_number`. Cả hai loại dùng chung các cột thông tin cá nhân.

| Loại | Cột bắt buộc với dòng mới | Cột không bắt buộc |
|---|---|---|
| Hồ sơ sinh viên | `student_number`, `program` (mã chương trình đào tạo), `admission_date`, `family_name` | `catalog_year` (để trống thì lấy năm nhập học), `year_level`, `status`, `graduation_date`, `institutional_email` |
| Giảng viên | `employee_number`, `org_unit` (mã đơn vị tổ chức), `employment_type`, `family_name` | `academic_title_*`, `hire_date`, `end_date` |
| Cột thông tin cá nhân (dùng chung) | — | `given_name`, tên theo ngôn ngữ như `name_ko`, `birth_date`, `gender`, `email`, `phone`, `national_id` |

- Giá trị của `status`: `enrolled`, `on_leave`, `withdrawn`, `dismissed`, `graduated` (với graduated bắt buộc có `graduation_date`). Tình trạng thay đổi qua nhập CSV được coi là **hiệu chỉnh dữ liệu** và không tạo lịch sử biến động sinh viên. Các biến động thực tế được ghi nhận trên màn hình ([chương 13](13-status-changes-and-advisors.md)).
- Giá trị của `employment_type`: `full_time`, `part_time`, `adjunct`, `visiting`, `emeritus`. Giá trị của `gender`: `female`, `male`, `other`, `undisclosed`.
- Nếu `national_id` của một hồ sơ sinh viên mới trùng với một cá nhân đã có, hồ sơ được gắn vào cá nhân đó (nhập học lại). Nếu không, một cá nhân mới được tạo. Khi cập nhật, ô trống không xóa các giá trị hiện có.
- Sinh viên và giảng viên được nhập vào chưa có tài khoản. Hãy gửi lời mời cho họ bằng [lời mời tạo tài khoản](14-merge-and-invitations.md#lời-mời-tạo-tài-khoản).

```csv
student_number,program,admission_date,year_level,status,family_name,given_name,name_ko,birth_date
202600001,B-CSE,2026-03-02,1,enrolled,Kim,Minsu,김민수,2007-05-01
```

## Học phần

**Nhập CSV** ở đầu danh sách học phần (quyền `Quản lý học phần, phiên bản và điều kiện học phần`) hoặc `reference:import courses`. Mỗi dòng là một phiên bản học phần.

| Cột bắt buộc | Cột không bắt buộc |
|---|---|
| `code`, `effective_start_date`, `title_en`, `credits` (phiên bản mới) | `org_unit` (bắt buộc với học phần mới; mã đơn vị tổ chức quản lý), `effective_end_date`, `title_*`, `lecture_hours`, `lab_hours`, `category` (mã loại học phần), `grading_basis` (`graded`, `pass_fail`), `counts_toward_credit_limit` |

- Học phần được đối chiếu theo `code`, phiên bản theo `code` + `effective_start_date`. Nhập lại cùng một tệp sẽ cập nhật chúng.
- Có thể nạp lịch sử điều chỉnh từ một tệp: liệt kê các dòng của cùng một mã theo từng giai đoạn (đặt `effective_end_date` cho phiên bản trước).
- Dòng có khoảng thời gian chồng lấn với phiên bản khác sẽ bị lỗi. Nếu dòng của một học phần mới bị lỗi, học phần đó cũng không được tạo.
- Điều kiện học phần, học phần tương đương và điều kiện tốt nghiệp được nhập trên màn hình ([chương 17](17-courses-and-curriculum.md)).

```csv
code,org_unit,effective_start_date,effective_end_date,title_en,title_ko,credits,category
CSE2010,CSE,2015-03-01,2026-02-28,Data Structures,자료구조,3,MAJ_REQ
CSE2010,,2026-03-01,,Data Structures,자료구조,4,MAJ_REQ
```

## Chặn đăng ký

**Nhập CSV** ở đầu danh sách **Đăng ký học phần > Chặn đăng ký** (quyền `Đặt và gỡ chặn, cấp ngoại lệ quy tắc`) hoặc `reference:import registration_holds`. Dùng để nạp dữ liệu `SRDENY` cũ hoặc danh sách sinh viên chưa nộp học phí từ phòng tài chính.

| Cột bắt buộc | Cột không bắt buộc |
|---|---|
| `student_number`, `hold_type` (`unregistered`, `financial`, `academic`, `administrative`, `other`), `reason` | `starts_at` (mặc định: thời điểm nhập), `ends_at`, `blocks_registration` (mặc định 1), `blocks_grades`, `blocks_certificates` |

- Mỗi dòng tạo một lượt chặn mới (nguồn `import`). Nhập cùng một tệp hai lần sẽ tạo lượt chặn hai lần, vì vậy hãy kiểm tra danh sách trước khi nhập lại.
- Dòng có mã số sinh viên không xác định sẽ bị lỗi. Việc gỡ chặn được thực hiện trên màn hình kèm lý do ([chương 20](20-registration.md)).

```csv
student_number,hold_type,reason,ends_at
202312345,financial,2027-1 등록금 미납,
202254321,academic,학사경고 상담 필요,2027-03-15
```
