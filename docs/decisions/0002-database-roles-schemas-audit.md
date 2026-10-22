# 0002. DB 역할 분리, 스키마 구성, 감사 3층

- 상태: 채택 (2026-10-22, Phase 0)
- 관련: [이식 계획 5.3절, 8절](../porting-plan.md#53-postgresql-스키마-구분)

## PostgreSQL 버전

PostgreSQL 18 (Sail 이미지 `postgres:18-alpine`, 확인 시점 18.6). ICU collation(`ko-x-icu`, `zh-Hans-x-icu`), `btree_gist`, `pg_trgm`, RLS를 사용한다.

## DB 역할

| 역할 | 용도 | 권한 |
|---|---|---|
| `app_migrator` | 마이그레이션 전용, 모든 스키마와 테이블의 소유자 | DB 소유자. 슈퍼유저 아님, `BYPASSRLS` 없음 |
| `app_runtime` | 애플리케이션 실행 | `public` DML, `audit` 조회와 일부 추가, `util` 함수 실행. DDL 불가. 테이블 소유자가 아니므로 RLS 적용 |
| `app_reporting` | 보고/BI 읽기 | `reporting` 스키마 조회만 |

- 역할 생성은 클러스터 작업이라 슈퍼유저가 한 번 실행한다: `database/sql/roles/create_roles.sql`, DB별로 `prepare_database.sql`. Sail은 컨테이너 최초 기동 시 `docker/pgsql/20-create-app-roles.sh`로 자동 실행한다.
- 애플리케이션 기본 연결(`pgsql`)은 `app_runtime`, 마이그레이션 연결(`pgsql_migrator`)은 `app_migrator`이다. `migrate*`, `db:wipe`, `schema:dump` 명령은 `--database`를 주지 않으면 자동으로 마이그레이터 연결을 쓴다(`DatabaseServiceProvider`). 테스트도 같은 방식(`tests/DatabaseTestCase.php`)이다.
- 기본 권한(`ALTER DEFAULT PRIVILEGES`)은 첫 마이그레이션(`0000_00_00_000000_create_database_schemas`)에서 설정하므로 이후 만드는 테이블에 런타임 권한이 자동으로 붙는다.

## 스키마

| 스키마 | 내용 |
|---|---|
| `public` | 애플리케이션 테이블 |
| `audit` | `record_changes`(트리거 감사), `activity_log`(업무 감사), `security_events`(보안 로그) |
| `reporting` | 보고용 뷰 (Phase 11부터) |
| `util` | 세션 컨텍스트, 다국어, 권한 판정, 감사 활성화 함수 |

함수와 트리거는 `database/sql/` 아래 SQL 파일로 두고 마이그레이션에서 `SqlFile::apply()`로 적용한다.

## 요청 컨텍스트 전달

`DatabaseSessionContext`가 요청과 작업마다 PostgreSQL 세션 설정을 지정하고 끝나면 비운다.

| 설정 | 값 | 지정 위치 |
|---|---|---|
| `app.user_id` | 로그인 사용자 ID | `SetDatabaseSessionContext` 미들웨어, 로그인 이벤트 |
| `app.actor` | `user:{id}` 또는 `system:job:{class}`, `system:command:{name}`, `system:seed` | 미들웨어, 큐 작업 이벤트, Artisan 명령 이벤트 |
| `app.request_id` | 요청 ID (`X-Request-Id`) | 미들웨어 (Laravel Context로 큐 작업에도 전달) |
| `app.locale`, `app.fallback_locale` | 화면 언어 | `SetLocale` 미들웨어 |

세션 수준 설정이므로 요청 종료(`terminate`), 작업 종료(`JobProcessed`/`JobFailed`), 명령 종료 시 반드시 초기화한다. Octane이나 큐 워커처럼 연결을 재사용하는 환경에서 이전 사용자 정보가 남지 않게 하기 위함이다.

## 감사 3층

| 층 | 저장 위치 | 기록 주체 | 내용 |
|---|---|---|---|
| 업무 감사 | `audit.activity_log` | `AuditLogger` (spatie/laravel-activitylog) | 이벤트, 대상, 수행자, **사유**, 속성, 요청 ID, IP, 사용자 에이전트 |
| 데이터 감사 | `audit.record_changes` | 트리거 `audit.record_change()` (SECURITY DEFINER) | 변경 전/후 행(jsonb), 변경 컬럼, 트랜잭션 ID, `app.user_id`/`app.actor`, DB 사용자. 애플리케이션 밖 변경도 기록 |
| 보안 로그 | `audit.security_events` | `RecordSecurityEvent` | 로그인 성공/실패/잠금, 로그아웃, 비밀번호 재설정, 2단계 인증 등록/해제/실패/초기화, 역할 할당/종료, 권한 변경, 민감 정보 표시, 내보내기 |

- 감사 대상 테이블은 마이그레이션에서 `SELECT util.enable_audit('table', ARRAY['비밀 컬럼'])`으로 지정한다. 지정한 컬럼 값은 `[redacted]`로 남는다. 내용이 바뀌지 않은 UPDATE는 기록하지 않는다.
- `app_runtime`은 `record_changes`에 쓰기 권한이 없고(트리거만 기록), `activity_log`/`security_events`는 추가와 조회만 가능하다.
- 세 테이블 모두 `{table}_before_change_prevent_modification` 트리거로 UPDATE/DELETE/TRUNCATE를 막는다. 소유자(`app_migrator`)도 막힌다. 보존 기간 정리는 트리거를 잠시 끄는 별도 운영 절차로 한다(Phase 13 운영 가이드).
- 행 해시 체인(선택 사항)은 아직 구현하지 않았다.

## 결과

테스트: `tests/Database/DatabaseRolesTest.php`, `tests/Database/AuditTriggerTest.php`, `tests/Feature/Security/AuthenticationTest.php`.
