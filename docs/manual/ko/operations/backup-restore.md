# 백업과 복구

- 대상: IT 운영자

## 백업 대상

| 대상 | 방법 | 주기(권장) |
|---|---|---|
| PostgreSQL | `pg_dump -Fc` 또는 PITR(WAL 보관) | 매일 전체 + WAL 연속 |
| 파일 저장소 (`storage/app/private` 또는 S3) | 스냅샷 또는 버전 관리 버킷 | DB와 같은 주기 |
| 환경 설정과 키 | 비밀 저장소 | 변경 시 |

DB와 파일 저장소는 같은 시점으로 복원할 수 있어야 한다(사진, 증명서 PDF 메타데이터가 DB에 있음).

## DB 백업 예

```bash
pg_dump -U app_migrator -d sis -Fc -f sis-$(date +%F).dump
```

`app_migrator`는 모든 스키마(`public`, `audit`, `reporting`, `util`)의 소유자이므로 이 계정으로 백업한다. 역할(`app_*`)은 클러스터 객체라 덤프에 들어가지 않는다. 복원 서버에서 [설치](installation.md) 2절의 역할 생성을 먼저 한다.

## 복원 예

```bash
createdb -U postgres sis_restore
psql -U postgres -d sis_restore -f database/sql/roles/prepare_database.sql
pg_restore -U app_migrator -d sis_restore --no-owner --role=app_migrator sis-2026-10-22.dump
```

## 복구 훈련

분기마다 별도 서버에 복원하고 다음을 확인한다: 로그인, 개인정보 복호화(키 일치), 감사 기록 조회, 파일(사진, PDF) 열람.
