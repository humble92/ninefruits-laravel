# 설치

- 대상: IT 운영자
- 관련: [명령 레퍼런스](commands.md), [키 관리](security-keys.md)

## 1. 요구 사항

| 구성 요소 | 버전 | 비고 |
|---|---|---|
| PHP | 8.5 | 확장: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | `btree_gist`, `pg_trgm` 확장(마이그레이션이 설치), ICU collation |
| Redis | 8 | 캐시, 세션, 큐 |
| Gotenberg | 8 | PDF 생성. 내부망에만 노출 |
| 메일 서버 | SMTP | 비밀번호 재설정, 2단계 인증 이메일 코드 |
| 큐 워커 | Horizon | `php artisan horizon`을 프로세스 관리자(systemd, supervisor)로 상시 실행 |
| 스케줄러 | cron | `* * * * * php artisan schedule:run` |

## 2. 데이터베이스 준비

DB 슈퍼유저가 한 번 실행한다. 애플리케이션은 슈퍼유저를 쓰지 않는다.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| 역할 | 환경 변수 | 용도 |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | 애플리케이션 실행 |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | 마이그레이션만 |
| `app_reporting` | (BI 도구에 설정) | `reporting` 스키마 조회 |

## 3. 환경 설정

`.env.example`을 복사해 값을 채운다. 반드시 바꿀 값

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`. **분실하면 암호화된 개인정보를 복구할 수 없다.** [키 관리](security-keys.md)에 따라 별도 보관한다.
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: 최초 설치 시 기관 시간대 기본값

## 4. 설치

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install`은 질문에 답하면서 진행한다. 배포 스크립트에서는 `--force`와 옵션으로 실행한다([명령 레퍼런스](commands.md#setupinstall)).

설치 프로파일(`--profile=`): `korean-university` — 1학기/2학기/계절학기, 4.5 성적 척도, 교과 구분, 09:00부터 50분 교시, 강의실 용도. 적용하지 않으면 관리 화면에서 직접 입력한다. 나중에 다시 적용해도 관리자가 바꾼 값은 유지된다.

설치 후 업무 관리자는 [관리자 매뉴얼 5장 초기 세팅 순서](../admin/05-institution-setup.md)에 따라 기준정보를 입력한다.

## 5. 설치 확인

1. `/login`에서 최초 관리자로 로그인한다.
2. 관리 화면 **설정 > 보안**에서 2단계 인증 정책을 정한다.
3. 관리자 본인의 2단계 인증을 등록한다(포털 **보안**).
4. `php artisan pdf:sample`로 PDF 서비스를 점검한다.
5. 관리 화면 **감사 > 데이터 변경 기록**에 설치 작업이 `system:command:setup:install`로 남았는지 확인한다.

## 6. 업데이트

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # 마이그레이션, 필수 데이터, 권한 동기화
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # 워커가 새 코드로 다시 시작
php artisan up
```
