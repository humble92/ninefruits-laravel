# Sao lưu và khôi phục

- Đối tượng: cán bộ vận hành CNTT

## Những gì cần sao lưu

| Đối tượng | Phương pháp | Tần suất (khuyến nghị) |
|---|---|---|
| PostgreSQL | `pg_dump -Fc` hoặc PITR (lưu trữ WAL) | Bản đầy đủ hằng ngày + WAL liên tục |
| Bộ lưu trữ tệp (`storage/app/private` hoặc S3) | Snapshot hoặc bucket có quản lý phiên bản | Giống như cơ sở dữ liệu |
| Cấu hình môi trường và khóa | Kho lưu trữ bí mật | Mỗi khi có thay đổi |

Cơ sở dữ liệu và bộ lưu trữ tệp phải khôi phục được về cùng một thời điểm (siêu dữ liệu của ảnh và tệp PDF giấy chứng nhận nằm trong cơ sở dữ liệu).

## Ví dụ sao lưu cơ sở dữ liệu

```bash
pg_dump -U app_migrator -d sis -Fc -f sis-$(date +%F).dump
```

`app_migrator` sở hữu mọi schema (`public`, `audit`, `reporting`, `util`), vì vậy hãy sao lưu bằng tài khoản này. Các vai trò (`app_*`) là đối tượng cấp cluster và không nằm trong bản dump. Trên máy chủ khôi phục, hãy tạo các vai trò trước theo mục 2 của [Cài đặt](installation.md).

## Ví dụ khôi phục

```bash
createdb -U postgres sis_restore
psql -U postgres -d sis_restore -f database/sql/roles/prepare_database.sql
pg_restore -U app_migrator -d sis_restore --no-owner --role=app_migrator sis-2026-10-22.dump
```

## Diễn tập khôi phục

Mỗi quý, khôi phục lên một máy chủ riêng và kiểm tra các nội dung sau: đăng nhập, giải mã dữ liệu cá nhân (khóa khớp nhau), xem bản ghi kiểm toán, mở tệp (ảnh, PDF).
