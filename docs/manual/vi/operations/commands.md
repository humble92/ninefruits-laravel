# Tham chiếu lệnh Artisan

- Đối tượng: cán bộ vận hành CNTT
- Phạm vi: mọi lệnh ứng dụng được đăng ký trong repository này. Khi thêm một lệnh, lệnh đó cũng phải được bổ sung vào đây, nếu không CI sẽ thất bại (`tests/Feature/Tooling/CommandDocumentationTest.php`).

## Quy ước chung

Mọi lệnh đều chạy dưới dạng `php artisan {command}`. Trong môi trường phát triển là `./vendor/bin/sail artisan {command}`.

| Mục | Quy tắc |
|---|---|
| Bản ghi kiểm toán | Các lệnh làm thay đổi dữ liệu ("Kiểm toán: có ghi" bên dưới) ghi lệnh, tham số, tùy chọn, người dùng hệ điều hành, mã thoát và kết quả vào nhật ký hoạt động (log `command`) sau mỗi lần chạy. Xem tại **Kiểm toán > Nhật ký hoạt động** trong trang quản trị |
| Mã lần chạy | Mỗi lần chạy nhận một mã bắt đầu bằng `cmd-` thay cho mã yêu cầu. Nhật ký hoạt động, các thay đổi dữ liệu do lần chạy đó tạo ra và các tác vụ hàng đợi mà nó gửi đi đều mang cùng một mã, vì vậy câu hỏi "lệnh nào đã tạo ra thay đổi này" được trả lời bằng mã đó. `--actor` là giá trị do người vận hành cung cấp, nên không được đưa vào cột người dùng của thay đổi dữ liệu (người thực hiện thực tế là người dùng hệ điều hành) |
| `--actor=<email>` | Dùng chung cho mọi lệnh có kiểm toán. Ghi lại tài khoản quản trị viên chịu trách nhiệm. Email không xác định sẽ làm dừng lần chạy |
| `--dry-run` | In kết quả mà không thay đổi gì. Có ở các thao tác khó hoàn tác |
| `--force` | Chạy không cần xác nhận (cho lịch chạy và script triển khai) |
| `--reason=` | Có ở các lệnh yêu cầu lý do |
| Mã thoát | 0 thành công, 1 thất bại hoặc bị hủy, 2 lệnh kiểm tra phát hiện vấn đề |
| Ngữ cảnh cơ sở dữ liệu | Trong khi lệnh chạy, người thực hiện của phiên cơ sở dữ liệu được đặt thành `system:command:{command}` và xuất hiện trong thay đổi dữ liệu (trigger) |

## Cài đặt và demo

### `setup:install`

- Mục đích: cài đặt mới và cập nhật. Lần lượt chạy migration, dữ liệu thiết yếu, giá trị mặc định của cơ sở đào tạo, hồ sơ cài đặt và quản trị viên ban đầu. Có thể chạy lại an toàn (dữ liệu hiện có và các giá trị do quản trị viên thay đổi được giữ nguyên).
- Khi nào: khi cài đặt lần đầu và mỗi lần triển khai.
- Tùy chọn: `--institution-name=`, `--locale=` (ngôn ngữ mặc định), `--timezone=` (ví dụ `Asia/Seoul`), `--profile=*` (khóa hồ sơ cài đặt), `--grade-scale=` (thang điểm `4.5` hoặc `4.3`. Không thể thay đổi sau khi đã đặt. Nếu để trống, dùng thang điểm của hồ sơ đầu tiên), `--admin-email=`, `--admin-name=`, `--force` (chạy không đặt câu hỏi. Mật khẩu quản trị viên được đọc từ đầu vào chuẩn).
- Kiểm toán: có ghi
- Ví dụ

  ```bash
  php artisan setup:install
  echo "$ADMIN_PASSWORD" | php artisan setup:install --force --locale=ko --timezone=Asia/Seoul \
      --institution-name="Demo University" --admin-email=admin@example.ac.kr --admin-name="Administrator"
  ```

- Đọc kết quả: `DONE`/`FAIL` cho từng bước. `Administrator exists` nghĩa là đã có lượt gán vai trò quản trị hệ thống, nên bước tạo được bỏ qua.
- Khi thất bại: nếu lỗi báo thiếu khóa, hãy đưa các giá trị do `key:generate` và `pii:generate-key` tạo ra vào cấu hình môi trường. Lỗi migration thường có nghĩa là các vai trò cơ sở dữ liệu chưa được chuẩn bị (xem hướng dẫn cài đặt).

### `setup:demo`

- Mục đích: tạo dữ liệu cho một trường đại học giả định bằng factory. In ra các tài khoản demo cố định theo từng vai trò (`registrar@demo.test`, v.v.).
- Khi nào: môi trường phát triển, đào tạo và trình diễn. **Lệnh sẽ không chạy trên môi trường production (`APP_ENV=production`), kể cả khi có `--force`.**
- Tùy chọn: `--size=tiny|small|medium|large` (khoảng 20/100/2.000/20.000 sinh viên. `tiny` dùng cho kiểm thử và kiểm tra nhanh), `--seed=` (cùng một giá trị cho ra cùng một bộ dữ liệu), `--force`.
- `--student-accounts`: tạo tài khoản cho mọi sinh viên đang học (`student{student number}@demo.test`, mật khẩu demo) và ghi chúng vào `storage/app/private/load-accounts.csv`. Dùng cho kiểm thử tải ([Kiểm thử tải](load-testing.md)).
- Kiểm toán: có ghi
- Ví dụ: `php artisan setup:demo --size=small --seed=42`
- Khi thất bại: nếu thiếu dữ liệu thiết yếu, các tài khoản demo không có vai trò và bị bỏ qua. Hãy chạy `setup:install` trước.

## Dữ liệu danh mục và học kỳ

### `reference:import`

- Mục đích: nhập dữ liệu danh mục từ CSV. Dùng cùng quy tắc nhập như **Nhập CSV** trong trang quản trị (các dòng hiện có được đối chiếu theo mã và cập nhật, ô bản dịch để trống giữ nguyên giá trị hiện có, chỉ các dòng lỗi được ghi lại riêng).
- Tham số: `{type}` là một trong `org_units`, `programs`, `buildings`, `rooms`, `lookup_values`, `students`, `instructors`, `courses`, `registration_holds` / `{file}` là tệp CSV UTF-8 có dòng tiêu đề.
- Tùy chọn: `--actor=` (bắt buộc. Bản ghi nhập và thông báo hoàn tất được gửi cho tài khoản này), `--chunk=100` (số dòng cho mỗi tác vụ hàng đợi), `--dry-run` (chỉ kiểm tra tiêu đề và đếm số dòng).
- Kiểm toán: có ghi (loại, tên tệp, số dòng, số dòng lỗi)
- Định dạng cột: [Hướng dẫn quản trị viên chương 9](../admin/09-csv-import.md).
- Mã thoát: nếu hàng đợi chạy đồng bộ, kết quả được in ngay và mã thoát là 2 khi có dòng lỗi. Nếu chạy bất đồng bộ (Horizon), việc nhập chỉ được tiếp nhận và mã thoát là 0. Kết quả được gửi dưới dạng tệp CSV các dòng lỗi trong thông báo của trang quản trị cho tài khoản `--actor`.
- Ví dụ: `php artisan reference:import org_units storage/app/org-units.csv --actor=admin@example.ac.kr --dry-run`

### `term:inspect`

- Mục đích: hiển thị học kỳ đang diễn ra tại một thời điểm nhất định và các khung thời gian đang mở (loại, học kỳ, tiêu chí đối tượng, thời điểm kết thúc). Dùng để kiểm tra cấu hình học kỳ và khung thời gian hoặc để trả lời các câu hỏi như "tại sao đăng ký học phần chưa mở".
- Tùy chọn: `--at=` (mặc định là hiện tại. Ví dụ `"2027-02-15 09:00"`. Nếu không có múi giờ, giá trị được hiểu theo múi giờ của máy chủ (UTC). Có thể chỉ định múi giờ như `"2027-02-15 09:00 Asia/Seoul"`).
- Kiểm toán: không ghi
- Đọc kết quả: việc xác định ngày dùng múi giờ của cơ sở đào tạo. Khung thời gian của học kỳ ở trạng thái dự kiến hoặc đã lưu trữ không bao giờ mở.

## Bảo mật

### `security:create-administrator`

- Mục đích: tạo một tài khoản có vai trò quản trị hệ thống. Thường được gọi bởi `setup:install`.
- Tùy chọn: `--email=`, `--name=`, `--password-stdin` (mật khẩu từ đầu vào chuẩn. Bắt buộc khi chạy không tương tác).
- Kiểm toán: có ghi (ID của người dùng được tạo)
- Ví dụ: `php artisan security:create-administrator --email=admin@example.ac.kr --name="Administrator"`
- Khi thất bại: `system_administrator role is missing` nghĩa là cần chạy `setup:install` hoặc `db:seed` trước. Mật khẩu phải có ít nhất 12 ký tự.

### `security:sync-permissions`

- Mục đích: đồng bộ danh sách quyền trong cơ sở dữ liệu với danh mục quyền trong mã nguồn. Quyền mới được tạo; quyền không còn trong danh mục bị xóa (và bị gỡ khỏi mọi vai trò). Vai trò hệ thống luôn nhận mọi quyền.
- Khi nào: sau khi triển khai (`setup:install` thực hiện việc này trong bước dữ liệu thiết yếu).
- Tùy chọn: `--dry-run` (chỉ in danh sách tạo/xóa), `--force` (bỏ qua xác nhận xóa).
- Kiểm toán: có ghi (các quyền được tạo/xóa)
- Đọc kết quả: các dòng `create` và `delete`. Khi có quyền bị xóa, một lời nhắc xác nhận sẽ xuất hiện.
- Khi thất bại: trả lời "không" ở bước xác nhận sẽ thoát với mã 1 và không thay đổi gì.

### `security:reset-mfa`

- Mục đích: đặt lại xác thực hai yếu tố của một người dùng (ứng dụng xác thực, mã qua email, mã khôi phục, thiết bị được ghi nhớ). Người dùng đăng ký lại ở lần đăng nhập tiếp theo. Dùng khi không có quản trị viên nào đăng nhập được vào trang quản trị (ví dụ quản trị viên duy nhất bị mất điện thoại). Các trường hợp khác hãy dùng **Người dùng > Đặt lại xác thực hai yếu tố** trong trang quản trị.
- Tham số/tùy chọn: `{user}` email người dùng, `--reason=` (bắt buộc), `--actor=` (bắt buộc. Quản trị viên quyết định việc đặt lại).
- Kiểm toán: có ghi (cả trong nhật ký hoạt động và nhật ký bảo mật, kèm lý do)
- Ví dụ: `php artisan security:reset-mfa admin@example.ac.kr --reason="Phone lost, identity verified by phone call" --actor=it-lead@example.ac.kr`

### `security:unlock-user`

- Mục đích: mở khóa tài khoản bị khóa do nhập sai mật khẩu nhiều lần trước khi hết thời gian khóa. Giống như **Người dùng > Mở khóa đăng nhập** trong trang quản trị.
- Tham số/tùy chọn: `{user}` email người dùng, `--reason=` (bắt buộc).
- Kiểm toán: có ghi (cả trong nhật ký bảo mật)
- Đọc kết quả: `The account is not locked.` nghĩa là không có gì thay đổi.
- Lưu ý: ngưỡng khóa (số lần thất bại, thời gian khóa) được thiết lập tại **Cài đặt > Phương thức đăng nhập** trong trang quản trị.

## Cá nhân và hồ sơ sinh viên

### `people:find-duplicates`

- Mục đích: liệt kê những cá nhân có vẻ là cùng một người. Tiêu chí là trùng số định danh cá nhân (giá trị băm tìm kiếm), hoặc trùng họ tên phiên âm Latinh và ngày sinh. Các cá nhân đã được gộp bị loại trừ.
- Tùy chọn: `--limit=100` (số nhóm được in).
- Kiểm toán: không ghi (chỉ đọc)
- Mã thoát: 2 khi có ứng viên trùng lặp.
- Xử lý tiếp: rà soát từng nhóm tại **Cá nhân** trong trang quản trị và, nếu đúng là cùng một người, chạy **Gộp bản trùng** từ trang của cá nhân cần giữ lại. Việc gộp không thể hoàn tác ([Hướng dẫn quản trị viên chương 14](../admin/14-merge-and-invitations.md)).

## Tuyển sinh

### `admission:matriculate`

- Mục đích: tạo hồ sơ sinh viên (mã số sinh viên mới) cho các thí sinh đã xác nhận nhập học của một đợt tuyển sinh. Giống như thao tác hàng loạt **Tạo hồ sơ sinh viên** tại **Tuyển sinh > Hồ sơ xét tuyển** trong trang quản trị. Mỗi thí sinh được xử lý riêng, nên một trường hợp thất bại không làm dừng các trường hợp khác.
- Tham số/tùy chọn: `{cycle}` mã đợt tuyển sinh, `--term=` (bắt buộc. Mã học kỳ nhập học, ví dụ `2027-SP`. Ngày nhập học là ngày đầu tiên của học kỳ), `--actor=` (bắt buộc. Tài khoản có quyền `Tạo hồ sơ sinh viên cho thí sinh trúng tuyển`. Chỉ các thí sinh trong phạm vi quyền của tài khoản đó được xử lý), `--dry-run` (chỉ danh sách đối tượng).
- Kiểm toán: có ghi (đợt tuyển sinh, học kỳ, số đối tượng, số đã tạo, số hồ sơ xét tuyển thất bại). Một bản ghi hoạt động (`application.matriculated`) cũng được ghi cho từng thí sinh.
- Mã thoát: 2 khi có thí sinh bị thất bại.
- Ví dụ: `php artisan admission:matriculate DEMO-2027-REG --term=2027-SP --actor=admissions@example.ac.kr --dry-run`
- Khi thất bại: `not ready` nghĩa là hồ sơ sinh viên đã tồn tại hoặc thí sinh không ở trạng thái đã xác nhận nhập học; `program is not in effect` nghĩa là chương trình đào tạo không có hiệu lực vào ngày nhập học (kiểm tra thời gian hiệu lực tại Dữ liệu danh mục > Chương trình đào tạo).

### `admission:expire-drafts`

- Mục đích: đánh dấu **Hết hạn** cho các hồ sơ xét tuyển ở dạng bản nháp thuộc các đợt đã đóng và các bản nháp không được chỉnh sửa trong khoảng thời gian đã cấu hình (**Cài đặt > Tuyển sinh > Hủy bản nháp không được chỉnh sửa sau (ngày)**, mặc định 90 ngày). Hồ sơ đã hết hạn không ngăn việc nộp hồ sơ mới trong cùng đợt.
- Khi nào: theo lịch (hằng ngày lúc 03:10, [Tác vụ định kỳ](scheduled-jobs.md)).
- Tùy chọn: `--dry-run` (chỉ đếm số lượng).
- Kiểm toán: có ghi (số đã hết hạn)

## Lớp học phần

### `section:copy`

- Mục đích: bắt đầu lập kế hoạch cho một học kỳ mới từ các lớp học phần của học kỳ trước. Giống như **Lớp học phần > Sao chép từ học kỳ khác** trong trang quản trị. Sao chép các lớp học phần không bị hủy dưới dạng bản nháp, cùng với giảng viên (nếu vẫn đang công tác), lịch học và phòng học, phân bổ chỗ (thời điểm giải phóng được dịch chuyển theo chênh lệch giữa các ngày bắt đầu học kỳ) và đề cương học phần (dưới dạng bản nháp không công khai). Phiên bản học phần được thay bằng phiên bản có hiệu lực vào thời điểm bắt đầu đợt học mới.
- Tham số/tùy chọn: `{from-term}`, `{to-term}` mã học kỳ (ví dụ `2026-FA`), `--org-unit=*` (chỉ các lớp học phần của mã đơn vị tổ chức này), `--dry-run` (chỉ kết quả, không lưu gì), `--actor=` (bắt buộc. Chỉ các lớp học phần trong phạm vi quyền `Lập kế hoạch lớp học phần` của tài khoản đó được sao chép).
- Kiểm toán: có ghi (số đã sao chép, bị bỏ qua và cần rà soát)
- Đọc kết quả: `skipped` nghĩa là học kỳ đích đã có lớp học phần cùng mã hoặc không có phiên bản học phần nào có hiệu lực; `note` nghĩa là lớp học phần đã được sao chép nhưng có nội dung bị bỏ sót (sao chép không kèm phòng vì phòng đã bị sử dụng, giảng viên đã nghỉ hưu, v.v.). Đợt học được đối chiếu theo mã; nếu không có, lớp học phần được đưa vào đợt học kéo dài cả học kỳ.
- Ví dụ: `php artisan section:copy 2026-FA 2027-FA --actor=academic@example.ac.kr --dry-run`

### `schedule:check-conflicts`

- Mục đích: kiểm tra các lớp học phần của một học kỳ trước khi công bố thời khóa biểu. Việc trùng lịch phòng học và giảng viên đã được cơ sở dữ liệu ngăn chặn; lệnh này tìm các vấn đề mà cơ sở dữ liệu cho phép: sĩ số vượt sức chứa của phòng, phiên bản học phần không có hiệu lực vào ngày bắt đầu đợt học, giảng viên đã nghỉ hưu, phân bổ chỗ vượt sĩ số, ngày học nằm ngoài đợt học. Lệnh cũng kiểm tra xung đột phòng học trong dữ liệu được đưa vào mà không qua ràng buộc, chẳng hạn như khi khôi phục bản sao lưu.
- Tham số: `{term}` mã học kỳ.
- Kiểm toán: không ghi (chỉ đọc)
- Mã thoát: 2 khi có vấn đề. Các lớp học phần chưa có giảng viên hoặc phòng học chỉ được hiển thị để tham khảo.

## Đăng ký học phần

### `registration:preflight`

- Mục đích: kiểm tra một học kỳ trước khi mở đăng ký học phần. Hiển thị các khung thời gian đăng ký và điều chỉnh đăng ký, bộ quy tắc, số lớp học phần đã công bố, các lớp học phần có sĩ số 0, phân bổ chỗ vượt sĩ số, phiên bản học phần không có hiệu lực vào ngày bắt đầu đợt học, số sinh viên đang học không thuộc giới hạn tín chỉ nào, và số sinh viên bị chặn đăng ký.
- Tham số: `{term}` mã học kỳ (ví dụ `2027-SP`).
- Kiểm toán: không ghi (chỉ đọc)
- Mã thoát: 2 khi có vấn đề. Việc thiếu bộ quy tắc được hiển thị để tham khảo, không phải là vấn đề (áp dụng giá trị mặc định của quy tắc).
- Ví dụ: `php artisan registration:preflight 2027-SP`

### `registration:simulate`

- Mục đích: hiển thị theo từng quy tắc điều gì sẽ xảy ra nếu sinh viên đăng ký lớp học phần ngay bây giờ (hoặc tại một thời điểm nhất định). Không có gì được lưu và không ghi nhận lần thử nào. Dùng cho các câu hỏi "tại sao tôi không đăng ký được?".
- Tham số: `{student-number}` mã số sinh viên, `{section}` `mã học phần-mã lớp` (ví dụ `CSE2010-01`).
- Tùy chọn: `--term=` (mã học kỳ. Mặc định là học kỳ gần nhất có lớp học phần đó), `--at=` (thời điểm đánh giá. Ví dụ `"2027-02-10 10:00 Asia/Seoul"`).
- Kiểm toán: không ghi (chỉ đọc)
- Đọc kết quả: theo từng quy tắc là đạt/không đạt, chặn/cảnh báo và thông điệp. Quy tắc được áp dụng ngoại lệ hiển thị là không đạt nhưng không chặn. Sĩ số được hiển thị riêng bên dưới bảng quy tắc.
- Mã thoát: 2 khi việc đăng ký sẽ bị từ chối.

### `waitlist:promote`

- Mục đích: lần lượt đăng ký cho các sinh viên trong danh sách chờ vào các lớp học phần còn chỗ trống. Mọi quy tắc được kiểm tra lại khi chuyển; các mục trong danh sách chờ không đạt quy tắc bị loại khỏi danh sách chờ (có ghi lại lý do). Danh sách chờ của các lớp học phần mà cả khung thời gian đăng ký và điều chỉnh đăng ký đều đã đóng sẽ hết hạn. Sinh viên nhận kết quả qua email.
- Tham số/tùy chọn: `{term?}` mã học kỳ (mặc định: mọi học kỳ có mục trong danh sách chờ), `--actor=`.
- Kiểm toán: có ghi (số lớp học phần, số được chuyển, bị loại, hết hạn)
- Khi nào: khi có hủy đăng ký, rút học phần hoặc giải phóng phân bổ chỗ, lớp học phần bị ảnh hưởng được xử lý ngay bằng một tác vụ hàng đợi. Lệnh này là cơ chế dự phòng chạy theo lịch hằng giờ để xử lý những gì bị bỏ sót.
- Ví dụ: `php artisan waitlist:promote 2027-SP --actor=registrar@example.ac.kr`

### `registration:release-reserved-seats`

- Mục đích: đánh dấu là đã xử lý các phân bổ chỗ đã qua thời điểm giải phóng (ví dụ chỗ dành riêng cho sinh viên của ngành) và đưa tác vụ chuyển sinh viên từ danh sách chờ của các lớp học phần đó vào hàng đợi. Chỗ dành riêng trở thành chỗ chung trong phép tính ngay khi qua thời điểm giải phóng, vì vậy lệnh này chỉ chuyển giao các chỗ đó cho danh sách chờ.
- Kiểm toán: không ghi (chạy mỗi 5 phút. Kết quả chuyển sinh viên nằm trong bản ghi đăng ký học phần)
- Khi nào: theo lịch (mỗi 5 phút).

## Điểm

### `term:close`

- Mục đích: khóa điểm một học kỳ. Mọi điểm trong học kỳ phải đã được chốt. Với từng sinh viên, lệnh tính kết quả học kỳ (tín chỉ, điểm TB học kỳ và tích lũy), xác định tình trạng học tập (bình thường, cảnh báo học vụ, xét buộc thôi học) và điều kiện khen thưởng (lô hàng đợi), sau đó xếp hạng khen thưởng theo chương trình đào tạo, khóa kết quả học kỳ thành hồ sơ chính thức và đặt học kỳ thành **Đã khóa điểm**. Giống như **Điểm > Kết quả học kỳ > Khóa điểm học kỳ** trong trang quản trị.
- Tham số/tùy chọn: `{term}` mã học kỳ, `--dry-run` (chỉ kiểm tra học kỳ có thể khóa điểm hay không), `--reason=` (lý do được lưu trong bản ghi), `--actor=`.
- Kiểm toán: có ghi (số sinh viên, số lớp học phần có điểm chưa chốt). Khi việc khóa điểm hoàn tất, bản ghi kiểm toán `term.closed` lưu số lượng cảnh báo học vụ, xét buộc thôi học và khen thưởng.
- Mã thoát: 2 khi một số lớp học phần có điểm chưa chốt (số điểm chưa chốt của từng lớp học phần được in ra). 1 khi học kỳ không ở trạng thái đã công bố.
- Khi nào: sau khi hoàn tất nhập điểm và chốt điểm. Theo dõi tiến độ lô trong Horizon. Nếu không có tiến trình xử lý hàng đợi, trạng thái học kỳ sẽ không thay đổi.
- Ví dụ: `php artisan term:close 2026-FA --dry-run`, `php artisan term:close 2026-FA --reason="Fall 2026 grades closed" --actor=registrar@example.ac.kr`

### `grades:recalculate`

- Mục đích: tính lại kết quả học kỳ (tín chỉ, điểm TB học kỳ và tích lũy) từ các điểm đã chốt. Dùng để áp dụng quy chế đánh giá đã thay đổi (số chữ số thập phân của điểm TB, cách xử lý học lại) cho các học kỳ trước. Tình trạng học tập và khen thưởng giữ nguyên giá trị đã xác định khi khóa điểm học kỳ. Yêu cầu sửa điểm được phê duyệt sẽ tự động tính lại cho sinh viên đó, nên không cần lệnh này.
- Tùy chọn: `--term=` (các sinh viên có điểm trong học kỳ đó, từ học kỳ đó trở đi), `--student-number=` (một sinh viên), `--reason=` (bắt buộc), `--actor=`.
- Kiểm toán: có ghi (số sinh viên, lý do)
- Ví dụ: `php artisan grades:recalculate --term=2026-SP --reason="GPA changed to three decimal places"`

### `grades:remind-missing`

- Mục đích: gửi email cho giảng viên của các lớp học phần chưa nộp điểm (chưa nhập hoặc mới lưu bản nháp). Các lớp học phần được gộp theo giảng viên thành một thư cho mỗi người.
- Tham số/tùy chọn: `{term}` mã học kỳ, `--dry-run` (in danh sách đối tượng mà không gửi), `--actor=`.
- Kiểm toán: có ghi (số lớp học phần, số giảng viên, số thư đã gửi)
- Khi nào: vài ngày trước khi khung thời gian nhập điểm đóng. Thêm vào lịch chạy nếu cần.

## Đánh giá học phần

### `evaluation:remind`

- Mục đích: gửi email cho các sinh viên vẫn còn học phần chưa đánh giá trong khi khung thời gian đánh giá học phần đang mở. Các học phần còn lại được gộp theo sinh viên thành một thư.
- Tham số/tùy chọn: `{term}` mã học kỳ, `--dry-run` (chỉ số sinh viên đối tượng, không gửi gì), `--actor=`.
- Kiểm toán: có ghi (số sinh viên đối tượng, số thư đã gửi)
- Khi nào: một hoặc hai lần trong khung thời gian đánh giá. Thêm vào lịch chạy nếu cần.

### `evaluation:publish-results`

- Mục đích: công bố kết quả đánh giá học phần của học kỳ cho giảng viên. Giống như **Đánh giá học phần > Đánh giá học kỳ > Công bố kết quả** trong trang quản trị. Ngay cả sau khi công bố, kết quả chỉ hiển thị cho các lớp học phần đạt số phản hồi và tỷ lệ phản hồi tối thiểu.
- Tham số/tùy chọn: `{term}` mã học kỳ, `--actor=`.
- Kiểm toán: có ghi (số phản hồi, đã công bố trước đó hay chưa)
- Khi nào: sau khi khung thời gian đánh giá và việc xử lý điểm hoàn tất. Học kỳ đã công bố sẽ không bị thay đổi.

## Giấy chứng nhận

### `certificate:verify`

- Mục đích: kiểm tra mã xác minh của giấy chứng nhận theo cùng tiêu chí với trang xác minh công khai. In ra loại, số hiệu, ngày cấp và người được cấp (đã che một phần).
- Tham số/tùy chọn: `{code}` mã xác minh.
- Kiểm toán: không có (chỉ đọc)
- Mã thoát: 0 khi hợp lệ, 2 khi mã không xác định, giấy chứng nhận đã bị thu hồi hoặc đã hết thời hạn hiệu lực.

### `certificate:render`

- Mục đích: tạo lại tệp PDF của một giấy chứng nhận đã cấp bằng dữ liệu và mẫu hiện tại, thay thế tệp đã lưu trữ. Giống như **Giấy chứng nhận đã cấp > Tạo lại** trong trang quản trị. Số hiệu và mã xác minh giữ nguyên; giá trị checksum thay đổi.
- Tham số/tùy chọn: `{issue}` số hiệu, `--reason=` (bắt buộc), `--actor=`.
- Kiểm toán: có ghi (số hiệu, checksum mới)
- Khi nào: để tạo lại một giấy chứng nhận cụ thể sau khi sửa mẫu hoặc chính tả họ tên.

## Kỳ thi

### `assessment:import-results`

- Mục đích: nhập kết quả của một đợt thi từ CSV. Cùng quy tắc với **Đợt thi > Nhập kết quả (CSV)** trong trang quản trị (chỉ sinh viên đã đăng ký, đạt/không đạt được xác định từ điểm số, nhập lại trước khi công bố sẽ thay thế kết quả).
- Tham số/tùy chọn: `{type}` mã loại kỳ thi / `{held_date}` ngày thi `YYYY-MM-DD` / `{file}` tệp CSV UTF-8 có dòng tiêu đề, `--actor=` (bắt buộc), `--dry-run` (chỉ kiểm tra các dòng, không ghi gì).
- Định dạng cột: [Hướng dẫn quản trị viên chương 24](../admin/24-assessments.md).
- Kiểm toán: có ghi (đợt thi, tên tệp, số dòng, số đã nhập/vắng thi/lỗi)
- Mã thoát: 2 khi có dòng lỗi (các dòng khác vẫn được ghi).
- Ví dụ: `php artisan assessment:import-results ENG_COMP 2026-05-15 storage/app/eng.csv --actor=registrar@example.ac.kr --dry-run`

### `assessment:publish-results`

- Mục đích: công bố kết quả của một đợt thi cho sinh viên. Giống như **Đợt thi > Công bố kết quả** trong trang quản trị.
- Tham số/tùy chọn: `{type}` mã loại kỳ thi / `{held_date}` ngày thi, `--actor=` (bắt buộc).
- Kiểm toán: có ghi (đợt thi, số kết quả)
- Khi nào: sau khi nhập kết quả xong. Đợt thi đã công bố sẽ báo thất bại.

## Điểm danh

### `attendance:generate-sessions`

- Mục đích: tạo các buổi học cho mọi lớp học phần của một học kỳ từ lịch học (thứ trong tuần, giờ, tiết học). Giống như **Tạo buổi học** trên cổng thông tin giảng viên và trong trang quản trị. Các buổi học hiện có và dữ liệu điểm danh của chúng được giữ nguyên (có thể chạy lại an toàn).
- Tham số/tùy chọn: `{term}` mã học kỳ, `--section=` (mã học phần-mã lớp, ví dụ `CSE2010-01`, chỉ một lớp), `--dry-run` (chỉ số lớp học phần đối tượng), `--actor=`.
- Kiểm toán: có ghi (học kỳ, số lớp học phần, số buổi học đã tạo)
- Khi nào: trước khi học kỳ bắt đầu, sau khi lịch học đã được xác nhận. Chạy lại khi lịch học thay đổi.

## Tốt nghiệp

### `degree-audit:run`

- Mục đích: lưu kết quả xét điều kiện tốt nghiệp cho mọi sinh viên đang học. Giống như **Xét tốt nghiệp > Chạy xét tốt nghiệp** trong trang quản trị. Kết quả xét được lưu gần nhất là căn cứ cho việc xử lý tốt nghiệp.
- Tùy chọn: `--term=` (mã học kỳ mà kết quả xét thuộc về), `--student-number=` (một sinh viên), `--min-year-level=` (chỉ từ năm học này trở lên), `--actor=`.
- Kiểm toán: có ghi (số sinh viên, số đã đáp ứng)
- Khi nào: trước khi xét tốt nghiệp (cuối học kỳ), và sau khi thay đổi bộ điều kiện tốt nghiệp hoặc học phần tương đương.

## Tích hợp LMS

### `lms:sync`

- Mục đích: đẩy các lớp học phần đã công bố lên nền tảng (tạo và liên kết khóa học, giảng viên, sinh viên đã đăng ký). Thực hiện cho cả một học kỳ những gì **Liên kết khóa học > Đồng bộ lại** làm trong trang quản trị. Các lớp học phần đã liên kết chỉ được bảo đảm lại (idempotent).
- Tham số/tùy chọn: `{platform}` mã nền tảng, `--term=` (mã học kỳ, mặc định là mọi học kỳ chưa kết thúc), `--section=` (một mã học phần-mã lớp), `--dry-run` (chỉ ghi lại những gì sẽ được gửi dưới dạng "skipped"), `--actor=`.
- Kiểm toán: có ghi (nền tảng, số lớp học phần, số thành viên, số thất bại)
- Mã thoát: 2 khi có lớp học phần thất bại.

### `lms:reconcile`

- Mục đích: so sánh danh sách thành viên khóa học trên nền tảng với danh sách đăng ký trong hệ thống thông tin sinh viên, thêm thành viên còn thiếu và gỡ thành viên thừa. Các khác biệt được lưu trong nhật ký đồng bộ (`reconcile`).
- Tham số/tùy chọn: `{platform?}` (mọi nền tảng đang hoạt động nếu bỏ trống), `--term=`, `--dry-run` (chỉ ghi lại khác biệt), `--actor=`.
- Kiểm toán: có ghi (số lớp học phần theo từng nền tảng, số đã thêm/đã gỡ/thất bại)
- Khi nào: theo lịch hằng ngày lúc 02:30. Chạy thủ công sau khi nền tảng gặp sự cố.

## Kiểm tra và dọn dẹp vận hành

### `system:health-check`

- Mục đích: kiểm tra cấu hình và khóa (APP_KEY, khóa PII, APP_DEBUG), kết nối và vai trò cơ sở dữ liệu (runtime, migrator, reporting), RLS trên các bảng dữ liệu cá nhân, migration chưa chạy, việc đồng bộ danh mục quyền, Redis, hàng đợi (Horizon), Gotenberg, khả năng ghi vào bộ lưu trữ media và nhịp tín hiệu của bộ lập lịch.
- Tùy chọn: `--json` (kết quả dành cho hệ thống giám sát).
- Kiểm toán: không có (chỉ đọc)
- Mã thoát: 2 khi có mục kiểm tra thất bại.
- Khi nào: ngay sau khi triển khai, khi nghi ngờ có sự cố, định kỳ từ hệ thống giám sát.

### `audit:seal`

- Mục đích: liên kết các dòng mới của các bảng kiểm toán vào chuỗi băm ([Tính toàn vẹn kiểm toán](audit-and-maintenance.md)).
- Tùy chọn: `--table=` (một trong record_changes, activity_log, security_events; có thể lặp lại), `--actor=`.
- Kiểm toán: có ghi (số dòng đã niêm phong theo từng bảng)
- Khi nào: theo lịch hằng ngày lúc 03:30.

### `audit:verify-chain`

- Mục đích: tính lại giá trị băm nội dung và chuỗi của các dòng kiểm toán để phát hiện giả mạo, xóa và đảo thứ tự.
- Tùy chọn: `--table=`.
- Kiểm toán: không có (chỉ đọc)
- Mã thoát: 2 khi có sai lệch.
- Khi nào: trước khi sao lưu, trong các đợt rà soát bảo mật, sau khi xóa bớt.

### `audit:prune`

- Mục đích: xóa các dòng kiểm toán **đã niêm phong** đã quá thời hạn lưu giữ. Chạy trên kết nối của chủ sở hữu schema.
- Tùy chọn: `--before=YYYY-MM-DD` (bắt buộc), `--table=`, `--dry-run` (chỉ đếm số lượng), `--actor=`.
- Kiểm toán: có ghi (ngày mốc, số dòng đã xóa theo từng bảng)
- Khi nào: theo chính sách lưu giữ, sau khi sao lưu.

### `media:prune-orphans`

- Mục đích: xóa các dòng media mà đối tượng sở hữu không còn tồn tại và các thư mục không có dòng tương ứng (ảnh cá nhân, tài liệu hồ sơ xét tuyển, tệp PDF giấy chứng nhận, minh chứng kỳ thi).
- Tùy chọn: `--dry-run` (chỉ liệt kê), `--actor=`.
- Kiểm toán: có ghi (số dòng, số thư mục)
- Khi nào: hằng tháng hoặc sau một đợt xóa hàng loạt.

## Khóa mã hóa dữ liệu cá nhân

### `pii:generate-key`

- Mục đích: in ra khóa mới cho `PII_ENCRYPTION_KEY` và `PII_HASH_KEY`. Không lưu gì.
- Kiểm toán: không ghi
- Thận trọng: thay đổi `PII_HASH_KEY` làm mất hiệu lực mọi giá trị băm tìm kiếm. Quy trình xoay vòng khóa nằm trong [security-keys.md](security-keys.md).

### `pii:reencrypt`

- Mục đích: sau khi xoay vòng khóa, mã hóa lại bằng khóa hiện tại các dữ liệu cá nhân đã được mã hóa bằng khóa cũ. Chạy dưới dạng lô hàng đợi.
- Tham số: lớp model (hiện tại là `App\\Models\\Person\\Person`).
- Tùy chọn: `--chunk=500`, `--dry-run` (chỉ đếm số bản ghi đang dùng khóa cũ), `--force`.
- Kiểm toán: có ghi (ID lô, số tác vụ / số lượng xem trước)
- Kiểm tra: theo dõi tiến độ lô trong Horizon (`/horizon`). Khi lô hoàn tất và `--dry-run` báo 0, hãy xóa khóa cũ khỏi `PII_PREVIOUS_KEYS`.

## Bản địa hóa

### `i18n:missing`

- Mục đích: hiển thị theo từng ngôn ngữ các khóa còn thiếu so với ngôn ngữ gốc (en) và các khóa không còn được sử dụng.
- Tham số/tùy chọn: `{locale?}` (chỉ một ngôn ngữ), `--strict` (mã thoát 2 khi thiếu khóa. Dùng trong CI).
- Kiểm toán: không ghi

### `i18n:export`

- Mục đích: tạo tệp JSON để giao cho người dịch. Định dạng: `{"key": {"source": "English text", "translation": "translation"}}`.
- Tham số/tùy chọn: `{locale} {path}`, `--missing` (chỉ các khóa còn thiếu).
- Kiểm toán: không ghi
- Ví dụ: `php artisan i18n:export ko storage/app/ko.json --missing`

### `i18n:import`

- Mục đích: áp dụng tệp JSON do người dịch điền vào `lang/{locale}`. Chỉ các khóa có trong ngôn ngữ gốc mới được nhập; bản dịch để trống bị bỏ qua.
- Tham số/tùy chọn: `{locale} {path}`, `--dry-run` (chỉ in danh sách các khóa sẽ được ghi).
- Kiểm toán: có ghi
- Thận trọng: các tệp bản dịch được quản lý trong repository. Hãy commit các thay đổi sau khi nhập.

## Kiểm tra

### `naming:check`

- Mục đích: kiểm tra tên bảng và tên cột trong migration theo [quy ước đặt tên](../../../development/naming.md).
- Tùy chọn: `--path=*` (các thư mục cần kiểm tra, mặc định `database/migrations`).
- Kiểm toán: không ghi
- Mã thoát: 2 khi có vấn đề.

### `pdf:sample`

- Mục đích: xuất ra PDF một bảng kết hợp chữ Hangul, CJK, Lào, Thái, Devanagari, Kirin và Latinh để kiểm tra phông chữ và kết nối với dịch vụ PDF (Gotenberg).
- Tham số: `{path=pdf-sample.pdf}` (tương đối so với ổ đĩa cục bộ `storage/app/private`).
- Kiểm toán: không ghi
- Khi thất bại: với lỗi kết nối, hãy kiểm tra `GOTENBERG_URL` và trạng thái của container Gotenberg.
