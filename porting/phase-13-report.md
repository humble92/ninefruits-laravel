# Phase 13 안정화와 배포 결과 보고

- 작성일: 2026-10-23
- 대상: 개발팀, 프로젝트 관리자, 운영 담당자
- 관련: [이식 계획 14절 Phase 13](porting-plan.md#phase-13-안정화와-배포-m), [감사와 유지보수](../docs/manual/ko/operations/audit-and-maintenance.md), [배포 체크리스트](../docs/manual/ko/operations/deployment-checklist.md), [부하 테스트](../docs/manual/ko/operations/load-testing.md)

## 1. 요약

새 기능 없이 기반을 굳혔다: Livewire 테스트가 로그인 사용자의 DB 컨텍스트(RLS)로 돌고, 감사 기록에 해시 체인을 붙여 봉인·검증·정리 명령을 갖췄으며, 상태 점검과 파일 정리 명령을 추가했다. 보안 점검(읽기 전용 검토)에서 나온 높음 5건, 중간 4건, 낮음 5건을 고치고 나머지는 후속 과제로 적었다. 수강신청 피크 부하 테스트를 k6로 돌려 Octane 도입 여부를 판단했고, 보류했던 PHP 보안 패치(php2shell)를 반영했다. 문서는 처음 쓰는 사람 기준으로 재편했다(README 3개 언어, `docs/`는 사용자 문서만, 이식 이력은 `porting/`).

- 테스트 {TESTS}개(단언 {ASSERTIONS}개) 통과. Phase 12 대비 {ADDED}개 추가. 전체 실행 {DURATION}. Larastan 레벨 6 오류 0, Pint 위반 0, 명명 검사 위반 0, 번역 누락 0
- 빈 DB에서 `migrate:fresh` → `setup:install --force --profile=korean-university --grade-scale=4.5`, `setup:demo --size=small --seed=42` 성공(커밋 `fb0b458` 기준)
- CI(GitHub Actions): 9절

## 2. 완료 기준

계획서 Phase 13 항목

| 항목 | 결과 | 근거 |
|---|---|---|
| 수강신청 부하 테스트와 Octane 판단 | 충족 | 7절. `tests/load/registration.js`(Livewire 수강신청 화면·등록 요청), `setup:demo --size=large --student-accounts` |
| 보안 점검(권한 우회, RLS, 개인정보 노출), 감사 무결성 | 충족 | 5절(보안 점검 결과와 수정), 4절(감사 해시 체인) |
| 운영 명령 `system:health-check`, `audit:verify-chain`, `audit:prune`, `media:prune-orphans` | 충족 | 4절, `OperationsCommandsTest` 5개 |
| Livewire 테스트의 DB 사용자 컨텍스트 | 충족 | 3절 |
| 명령과 매뉴얼 최종 정리, 영문판 | 일부 | 명령 레퍼런스는 테스트로 강제. 영문·중문 매뉴얼은 색인만 두고 본문은 한국어판을 가리킨다(10절 남은 일) |
| 시드 최종 점검, 화면 캡처 | 충족 | README 스크린샷(관리자·교원·학생, 3개 언어)을 데모 데이터로 Gotenberg 캡처 |
| 설치 리허설(온보딩 검증) | 미실시 | 외부 참여자가 필요. 10절 |
| 백업/복구 훈련, 운영 배포 가이드 | 충족 | 배포 체크리스트(PHP 버전, php.ini, .env, 절차, 정기 점검), 백업/복구 매뉴얼은 Phase 0 것을 유지 |
| PHP 보안 패치(php2shell) | 충족 | 8절 |

13.5절 공통 완료 기준

| 기준 | 결과 |
|---|---|
| 명령과 테스트 | `audit:seal`, `audit:verify-chain`, `audit:prune`, `media:prune-orphans`, `system:health-check` + `OperationsCommandsTest`; 보안 수정 + `HardeningTest` 6개 |
| 매뉴얼 | 운영: 감사와 유지보수, 배포 체크리스트, 부하 테스트, 명령 레퍼런스, 예약 작업. 관리자: 2장(계정·역할 할당 안전장치), 4장, 21장, 23장, 25장, 27장, 28장 보강 |
| 시더, 팩토리, 데모 | `setup:demo --student-accounts`(부하 테스트용 학생 계정과 CSV). 마이그레이션 1개(감사 해시 체인 칼럼, 기존 행 `row_hash` 채움) |
| `setup:install`, `setup:demo --size=small` | 빈 DB에서 성공 |
| 번역 en, ko, zh_CN | 보안 이벤트 3종, 역할 할당·계정 수정 오류 5종, 비활성 계정 안내 |
| 결과 보고 | 이 문서 |

## 3. Livewire 테스트의 DB 사용자 컨텍스트

Phase 10 보고서 검토 3의 결정. 그동안 `Livewire::actingAs()` 테스트는 HTTP 미들웨어를 거치지 않아 DB 세션 변수(`app.user_id` 등)가 비어 있었고, RLS가 아닌 "시스템" 권한으로 쿼리가 돌았다. 화면 테스트가 통과해도 실제 요청에서는 RLS에 막힐 수 있었다.

- `tests/Support/LivewireDatabaseContextHook`: Livewire `ComponentHook`. `boot`에서 로그인 사용자로 `DatabaseSessionContext::forUser()`, `dehydrate`·예외 시 `reset()`. `Tests\DatabaseTestCase::setUp`이 등록하므로 모든 Livewire 테스트에 자동 적용된다.
- 적용 결과 실패한 테스트는 1개뿐이었다: LMS 점수 불러오기. `PullGrades`가 동기화 기록과 사용자 연결을 교원 권한으로 쓰려 해 RLS에 막혔다. 교원이 아니라 시스템이 쓰는 기록이므로 `asSystem('lms:pull-grades', ...)`로 감쌌다. 이런 종류의 누락을 이제 테스트가 잡는다.

## 4. 감사 무결성과 운영 명령

### 4.1 감사 해시 체인

`audit.record_changes`, `audit.activity_log`, `audit.security_events`에 `row_hash`, `chain_hash`, `sealed_at`을 두었다(마이그레이션 `2026_10_23_006000`, SQL `database/sql/triggers/audit_chain.sql`).

- `row_hash`: INSERT 트리거가 행 내용(SHA-256)을 즉시 계산한다. 기존 행은 마이그레이션이 채운다.
- `chain_hash`: `audit:seal`이 id 순서로 `sha256(이전 chain_hash || row_hash)`를 이어 붙이고 `sealed_at`을 찍는다. 밤 03:30 예약.
- `audit:verify-chain`: 행마다 `row_hash`를 다시 계산해 비교하고 체인을 따라간다. 변조·삭제·끼워넣기가 있으면 첫 불일치 id를 출력하고 종료 코드 2.
- `prevent_modification` 트리거는 그대로 UPDATE/DELETE를 막고, `audit:prune`만 트랜잭션 안에서 `app.audit_maintenance = on`을 켜고 지운다(`SECURITY DEFINER` 함수와 같은 수준의 예외).

### 4.2 감사 정리의 감사 (사용자 요청)

로그를 지우는 행위 자체가 보안상 중요하므로 `audit:prune`은 지울 때마다 보안 이벤트 `audit.pruned`를 남긴다: 테이블, 기준일, 지운 행 수, id 범위(`first_id`~`last_id`), 경계 행의 `chain_hash`. `AuditedCommand`의 실행 기록(`--actor`, 옵션, 결과)과 함께 "언제 누가 어떤 조건으로" 정리했는지 남고, 경계 해시로 `audit:verify-chain` 결과와 대조할 수 있다. 봉인되지 않은 행은 지우지 않는다(먼저 `audit:seal`).

### 4.3 운영 명령

| 명령 | 내용 |
|---|---|
| `audit:seal` | 봉인되지 않은 행을 체인에 잇는다. 예약 03:30 |
| `audit:verify-chain` | 체인 검증. 문제 시 종료 코드 2 |
| `audit:prune --before= --table=` | 보존 기간이 지난 봉인된 행 삭제, dry-run, 보안 이벤트 |
| `media:prune-orphans` | 모델이 없는 미디어 행과 디스크의 고아 디렉터리 정리, dry-run |
| `system:health-check [--json]` | DB(런타임·마이그레이터 연결, 대기 마이그레이션), Redis, 큐(Horizon), 스케줄러 심장박동(1분마다 갱신), 저장소 쓰기, Gotenberg, 앱 키, 디버그 모드, PHP 버전, 권한 카탈로그 동기화 등 12항목. 문제 시 종료 코드 2 |

## 5. 보안 점검 결과와 수정

읽기 전용 검토(권한 우회, RLS, 개인정보 노출, 감사 공백)를 하고 결과를 분류했다. 고친 것은 `tests/Feature/Security/HardeningTest.php`와 기존 테스트 수정으로 확인한다.

### 5.1 고친 것

| 번호 | 심각도 | 내용 | 수정 |
|---|---|---|---|
| H1 | 높음 | 인물 수정 화면의 폼 상태(Livewire 스냅샷)에 암호화 신분증·여권 칼럼이 실렸다 | `Person` 모델에 `#[Hidden]`: 암호화 칼럼과 해시는 배열·JSON에서 제외. 화면은 기존대로 입력 전용 필드를 쓴다 |
| H3 | 높음 | `AssignRole`이 행위자 검사 없이 자기 자신, 어떤 역할, 전체 범위도 할당했다 | 자기 할당 금지, 행위자가 갖지 않은 권한이 든 역할 금지, 행위자의 `role_assignments.manage` 범위(조직 하위 포함) 밖 금지. CLI `security:create-administrator`는 운영자 경로로 제외(`enforceActorLimits: false`) |
| H4 | 높음 | 사용자 수정이 누구의 이메일이든 바꿀 수 있고 흔적이 없었다 | `UpdateUserAccount` 액션: 이메일 변경 시 인증 초기화·세션 삭제·보안 이벤트 `email.changed`·감사, 자기 비활성화 금지, 더 높은 권한의 계정 수정 금지 |
| H5 | 높음 | LMS 점수 불러오기가 권한 검사 없이 실행됐다 | `GradeEntry::importLmsScores`와 `PullGrades::handle(actor)`가 `SaveGrades::canEnter`로 검사(교원은 입력 기간 안, 직원은 범위 안) |
| M1 | 중간 | 직원이 제출된 성적을 덮어써도 이전 값이 남지 않았다 | 감사 `grades.submitted_changed`(from/to) |
| M6 | 중간 | CSV 수식 주입(`=`, `+`, `-`, `@`) | `ReportCatalog::cell`이 작은따옴표를 붙인다. 보고서 CSV와 수강생 명단 CSV 모두 |
| M7 | 중간 | 비밀번호 변경이 다른 세션을 끝내지 않고 기록도 없었다 | `remember_token` 교체, `logoutOtherDevices`, 보안 이벤트 `password.changed` |
| M8 | 중간 | 비활성 계정의 기존 세션이 살아 있었다 | `EnsureUserIsActive` 미들웨어(web 그룹과 관리자 패널): 다음 요청에서 로그아웃, 안내 메시지 |
| L1 | 낮음 | 수업 회차 생성·대기자 승격 헤더 액션이 `visible`만 있었다 | `->authorize()` 추가 |
| L2 | 낮음 | 출석부 PDF와 직원의 성적표 열람이 감사에 없었다 | `attendance.sheet_exported`, `transcript.viewed` |
| L4 | 낮음 | 취소된 증명서를 학생이 내려받을 수 있었다 | 발급 권한자만 가능 |
| L5 | 낮음 | 증빙 파일 이름이 헤더에 그대로 들어가고 CSV 업로드 크기 제한이 없었다 | 따옴표·개행 제거, `maxSize(5120)` |

### 5.2 후속 과제로 남긴 것

| 번호 | 심각도 | 내용 | 메모 |
|---|---|---|---|
| H2 | 높음 | CSV 가져오기(학생·교원·교과목·수강 제한)가 시스템 컨텍스트로 실행되어 행 단위로 가져오는 사람의 범위를 검사하지 않는다. `학적 관리` 등을 조직 범위로 받은 사람이 범위 밖 행을 넣을 수 있다 | 기본 역할 템플릿은 이 권한을 전역으로만 준다. 범위 할당을 쓰기 전에 각 `Importer::resolveRecord`에 행의 과정·조직을 행위자 범위와 대조하는 검사를 넣는다 |
| M2 | 중간 | 포털 일부 화면이 모델 정책 대신 소속 비교에 의존 | RLS가 2차 방어. 정책으로 옮기는 정리 |
| M3 | 중간 | 2단계 인증 복구 코드 사용 알림 없음 | 알림 추가 |
| M4 | 중간 | 공개 증명서 진위 확인 페이지의 속도 제한이 느슨 | 라우트 throttle 강화 |
| M5 | 중간 | 로그인 실패 잠금이 계정 단위만 | IP 단위 제한 추가 |
| M9 | 중간 | 세션 수명과 "모든 기기에서 로그아웃" 화면 없음 | 보안 설정과 포털 화면 |
| L3, L6~L9 | 낮음 | 헤더 보안(CSP) 세분화, 알림 메일의 민감 정보 최소화, 파일 다운로드의 `X-Content-Type-Options`, 데모 계정 비밀번호 정책 | 배포 체크리스트에 메모 |

## 6. 문서 재편

- `README.md`, `README.en.md`, `README.zh_CN.md`: 처음 설치·운영하는 사람 기준. 언어별 링크(README + 매뉴얼 디렉터리), 첫 화면 스크린샷(최고관리자·교수·학생), 기능 요약, 설치, 데모 설치와 역할별 데모 계정, 문서 링크, 감사의 말.
- `docs/`는 사용자 문서만: `docs/README.md`, `docs/manual/{ko,en,zh_CN}`, `docs/development/`(명명 규칙, 검토 체크리스트), `docs/lms-integration-guide.md`, `docs/screenshots/`.
- 이식 이력은 `porting/`: 계획, 단계별 보고, 설계 결정.

## 7. 부하 테스트와 Octane 판단

{LOAD}

## 8. PHP 보안 패치 (php2shell)

- php.net 공지와 대조: 2026-10-21 릴리스 8.5.12 / 8.4.27 / 8.3.36이 CVE-2026-109618(OOM 상태 손상), CVE-2026-109619(요청 간 상태 잔존)를 고쳤다.
- `composer.json` `"php": "^8.5.12"`. Sail 이미지 재빌드 뒤 `php -v` {PHPV}.
- 운영 `php.ini`: `session.upload_progress.enabled = Off`, `fatal_error_backtraces = Off`, 업로드 크기 제한. 배포 체크리스트에 기록.

## 9. CI 결과

{CI}

## 10. 남은 일

- 영문·중문 매뉴얼 본문 번역(색인만 있음).
- 설치 리허설(프로젝트 밖 사람이 매뉴얼만으로 설치부터 첫 학기까지).
- 5.2절 후속 과제.
- 매뉴얼 화면 캡처는 README의 첫 화면 3장만 갱신했다. 장별 캡처는 리허설 때 데모 데이터로 만든다.
