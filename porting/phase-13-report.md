# Phase 13 안정화와 배포 결과 보고

- 작성일: 2026-10-23
- 대상: 개발팀, 프로젝트 관리자, 운영 담당자
- 관련: [이식 계획 14절 Phase 13](porting-plan.md#phase-13-안정화와-배포-m), [감사와 유지보수](../docs/manual/ko/operations/audit-and-maintenance.md), [배포 체크리스트](../docs/manual/ko/operations/deployment-checklist.md), [부하 테스트](../docs/manual/ko/operations/load-testing.md)

## 1. 요약

새 기능 없이 기반을 굳혔다: Livewire 테스트가 로그인 사용자의 DB 컨텍스트(RLS)로 돌고, 감사 기록에 해시 체인을 붙여 봉인·검증·정리 명령을 갖췄으며, 상태 점검과 파일 정리 명령을 추가했다. 보안 점검(읽기 전용 검토)에서 나온 높음 5건, 중간 4건, 낮음 5건을 고치고 나머지는 후속 과제로 적었다. 수강신청 피크 부하 테스트를 k6로 돌려 Octane 도입 여부를 판단했고, 보류했던 PHP 보안 패치(php2shell)를 반영했다. 문서는 처음 쓰는 사람 기준으로 재편했다(README 3개 언어, `docs/`는 사용자 문서만, 이식 이력은 `porting/`).

- 테스트 464개(단언 2,464개) 통과. Phase 12 대비 11개 추가(보안 수정 6, 운영 명령 5, 라오어 로케일 1, 기존 조정). 전체 실행 5분 4초. Larastan 레벨 6 오류 0, Pint 위반 0, 명명 검사 위반 0, 번역 누락 0
- 빈 DB에서 `migrate:fresh` → `setup:install --force --profile=korean-university --grade-scale=4.5`, `setup:demo --size=small --seed=42` 성공(35초). 설치 결과에 라오어 증명서 서식 3종과 지원 언어 4개 확인. `setup:demo --size=large --student-accounts`(학생 20,004명, 계정 11,432개)는 약 65분
- CI(GitHub Actions): 9절

## 2. 완료 기준

계획서 Phase 13 항목

| 항목 | 결과 | 근거 |
|---|---|---|
| 수강신청 부하 테스트와 Octane 판단 | 충족 | 7절. `tests/load/registration.js`(Livewire 수강신청 화면·등록 요청), `setup:demo --size=large --student-accounts` |
| 보안 점검(권한 우회, RLS, 개인정보 노출), 감사 무결성 | 충족 | 5절(보안 점검 결과와 수정), 4절(감사 해시 체인) |
| 운영 명령 `system:health-check`, `audit:verify-chain`, `audit:prune`, `media:prune-orphans` | 충족 | 4절, `OperationsCommandsTest` 5개 |
| Livewire 테스트의 DB 사용자 컨텍스트 | 충족 | 3절 |
| 명령과 매뉴얼 최종 정리, 영문판 | 충족 | 명령 레퍼런스는 테스트로 강제. 영문·중문 매뉴얼 전체 번역, 라오어 추가(6절) |
| 시드 최종 점검, 화면 캡처 | 충족 | README 스크린샷(관리자·교원·학생, 3개 언어)을 데모 데이터로 Gotenberg 캡처 |
| 설치 리허설(온보딩 검증) | 미실시 | 외부 참여자가 필요. 10절 |
| 백업/복구 훈련, 운영 배포 가이드 | 충족 | 배포 체크리스트(PHP 버전, php.ini, .env, 절차, 정기 점검), 백업/복구 매뉴얼은 Phase 0 것을 유지 |
| PHP 보안 패치(php2shell) | 일부 | composer 하한, 배포 체크리스트 반영. Sail 이미지는 배포판 패키지 대기(8절) |

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

## 6. 문서 재편과 다국어

### 6.1 재편

- `README.md`, `README.en.md`, `README.zh_CN.md`, `README.lo.md`: 처음 설치·운영하는 사람 기준. 언어별 링크(README + 매뉴얼 디렉터리), 첫 화면 스크린샷(최고관리자·교수·학생), 기능 요약, 설치, 데모 설치와 역할별 데모 계정, 문서 링크, 감사의 말.
- `docs/`는 사용자 문서만: `docs/README.md`, `docs/manual/{ko,en,zh_CN,lo}`, `docs/development/`(명명 규칙, 검토 체크리스트, 언어 추가 가이드), `docs/lms-integration-guide.md`, `docs/screenshots/`.
- 이식 이력은 `porting/`: 계획, 단계별 보고, 설계 결정.

### 6.2 매뉴얼 번역 (영어, 중국어)

그동안 영문·중문 매뉴얼은 색인만 있고 본문은 한국어를 가리켰다. 한국어 원본(관리자 28장 + 색인, 운영 9개, 매뉴얼 색인)을 두 언어로 모두 번역했다. 원칙: 굵게 쓴 화면 이름은 그 언어의 UI 라벨(`lang/{locale}`)과 같게, 명령·옵션·경로·키는 그대로, 상대 링크는 파일 이름을 맞춰 언어 간에 동일. 번역은 언어별 3개(관리자 1~15장, 16~28장, 운영+색인)로 나눠 병렬로 했고, 역할 이름 같은 용어 불일치는 시더 표시명 기준으로 맞췄다. 원어민 검수는 아직 없다(10절).

### 6.3 라오스어 추가 (첫 고객)

첫 고객이 라오스 대학이어서 네 번째 언어 `lo`를 추가했다. 로케일은 코드에 목록이 없고 `lang/{locale}/` 디렉터리와 기관 설정으로 결정되므로 다음이 전부였다.

| 항목 | 내용 |
|---|---|
| UI 문자열 | `lang/lo/*.php` 23개(영문과 같은 키 약 2,070개), `lang/lo.json`. `i18n:missing --strict`: ko, lo, zh_CN 모두 누락 0 |
| 기본 지원 언어 | 설정 마이그레이션 `2026_10_23_008010_add_lao_locale`: 목록이 기본값이면 `lo`를 덧붙인다 |
| 시드 이름 | 필수 시더 4개(조직 단위 유형, 역할 템플릿, 학적 변동 유형, 코드 유형)와 프로파일 2개에 `'lo'` 값, 증명서 서식 3종의 라오어 판과 바닥글 |
| 문서 | `docs/manual/lo/`(관리자 28장 + 색인, 운영 9개, 색인), `README.lo.md`, 모든 README의 언어 표에 행 추가 |
| PDF | Gotenberg 이미지에 Noto Sans Lao가 있어 추가 글꼴 불필요(컨테이너에서 `fc-list` 확인) |
| 테스트 | `LocaleTest`: 기본 지원 언어에 `lo` 포함, `Accept-Language: lo` 로그인 화면 |

용어는 번역 전에 용어집(학적 = ບັນທຶກນັກສຶກສາ, 학기 = ພາກຮຽນ, 개설강좌 = ກຸ່ມຮຽນ 등 50여 개)을 정해 UI·매뉴얼·시더 에이전트가 같은 말을 쓰게 했다. 기계 번역이므로 라오어 원어민 검수가 필요하다(10절).

### 6.4 언어 추가 가이드

전 세계 개발자가 언어를 추가할 수 있도록 절차를 `docs/development/adding-a-language.md`(영문, 한국어판 `adding-a-language.ko.md`)로 정리했다: 로케일 코드 → `i18n:export`/`i18n:import`로 UI 문자열 → `i18n:missing --strict` → 기관 설정 또는 설정 마이그레이션 → 시드 이름 → 매뉴얼·README·언어 표 → 스크린샷(`tools/screenshots.py`) → PDF 글꼴 → 체크리스트. README 상단 언어 표 아래에서 링크한다.

## 7. 부하 테스트와 Octane 판단

2026-10-23 측정(Phase 13). 환경: 개발용 Sail 컨테이너(macOS Docker, `php artisan serve` 내장 서버, opcache 없음, `APP_DEBUG=true`), 데모 `--size=large`(학생 20,004명, 학생 계정 11,432개), 2027년 봄학기 수강신청 기간을 전 학년에 개방, 공개 강좌 62개(총 정원 약 2,200). 시나리오: 로그인 → 수강신청 화면 → 강좌 검색(Livewire) → 등록 → 취소, 반복마다 다른 학생.

| 조건 | 처리량 | 화면 p95 | 등록 p95 | 실패 | 등록 성공/거절 |
|---|---|---|---|---|---|
| 2 VU(기준선) | 1.0 iter/s | 150ms | 209ms | 0 | 14 / 2 |
| 100 VU, 서버 워커 4 | 24.7 req/s | 6.73s | 4.30s | 0 / 1,751 | 251 / 49 |
| 100 VU, 서버 워커 16 | 34.5 req/s | 4.33s | 3.18s | 0 / 2,359 | 359 / 41 |

읽는 법

- 요청 하나의 서버 처리 시간은 화면 약 120ms, 등록 약 190ms(opcache·디버그 모드 없는 운영 환경이면 더 짧다). 100 VU에서의 수 초 지연은 애플리케이션이 아니라 요청 대기열이다: 내장 서버 워커 4개로 초당 25건, 16개로 35건이 상한이고 그 이상은 노트북 CPU 포화다.
- 측정 중 PostgreSQL 연결은 4개, 잠금 대기 없음, HTTP 실패 0, 초과 등록 0. 좌석 잠금(`SeatAllocator::lock`)이 병목이 되지 않았다. 등록 거절 49/41건은 만석·규칙 거절로 정상 동작이다.
- 수강신청 피크를 "10분 동안 재학생 2,000명이 각 6회 요청"으로 잡으면 초당 20건이다. 운영 환경(php-fpm 워커 16개 이상, opcache)에서 요청당 50~100ms로 보면 초당 160~320건이 가능하므로 여유가 있다.

**Octane 판단**: 도입하지 않는다. 처리량은 PHP 워커 수(CPU)에 비례해 늘고 DB는 한참 남으므로, php-fpm 워커 수와 앱 서버 수평 확장으로 충분하다. Octane은 요청당 부트스트랩 비용을 줄이지만 상태 격리 점검(싱글턴, 정적 캐시)이 필요한데, 현재 수치로는 그 비용을 정당화하지 못한다. 운영 전 할 일: 운영과 같은 php-fpm + opcache 구성의 스테이징에서 같은 스크립트를 200·500 VU로 다시 돌려 기준(화면 p95 < 800ms, 등록 p95 < 1.5s, 실패 < 1%)을 확인한다. 이번 환경 수치는 하한선이다.

부하 테스트 준비에서 드러난 개선점: 대용량 데모(`--size=large`)가 약 65분 걸렸다(수강·성적 단계가 행 단위 액션 호출). 다음 정리 때 수강·성적 시더를 출석 시더처럼 대량 삽입으로 바꾼다. 스크립트는 Livewire 4의 버전 접두어가 붙은 `livewire-<hash>/update` 엔드포인트를 페이지에서 읽어 쓰고, 반복마다 쿠키를 지워 다른 학생으로 로그인한다.

## 8. PHP 보안 패치 (php2shell)

- php.net 공지와 대조: 2026-10-21 릴리스 8.5.12 / 8.4.27 / 8.3.36이 CVE-2026-109618(OOM 상태 손상), CVE-2026-109619(요청 간 상태 잔존)를 고쳤다.
- `composer.json` `"php": "^8.5.12"`. Sail 이미지를 캐시 없이 재빌드했지만 packages.sury.org가 2026-10-23 기준 `php8.5-cli 8.5.11-1+0~20260924`까지만 제공해 로컬은 8.5.11에 머문다. 패키지가 나오면 `sail build --no-cache` 후 `php -v`로 확인한다. 그때까지 로컬 `composer install`은 `--ignore-platform-req=php`가 필요하다(기존 vendor는 그대로 동작).
- 운영 `php.ini`: `session.upload_progress.enabled = Off`, `fatal_error_backtraces = Off`, 업로드 크기 제한. 배포 체크리스트에 기록.

## 9. CI 결과

{CI}

## 10. 남은 일

- 영문·중문·라오어 매뉴얼과 라오어 UI의 원어민 검수(기계 번역 상태).
- 설치 리허설(프로젝트 밖 사람이 매뉴얼만으로 설치부터 첫 학기까지).
- 5.2절 후속 과제.
- 매뉴얼 화면 캡처는 README의 첫 화면 3장(언어별 4벌)만 있다. 장별 캡처는 리허설 때 데모 데이터로 만든다.
- 라오어 화면에서 Filament 자체 문구(Dashboard, Search, Sign out, 표 페이지 이동 등)는 Filament에 `lo` 번역이 없어 영어로 나온다. `lang/vendor/filament*/lo/`를 추가하면 해결된다(언어 추가 가이드에 적음).
- 대용량 데모 시더(수강·성적)를 대량 삽입으로 바꿔 65분을 줄인다(7절).
- 라오어 매뉴얼의 CSV 예시는 한국어 이름 열(`name_ko`)을 그대로 보여 준다. 가져오기는 `name_{locale}` 열을 지원하므로 라오어 고객 문서에서는 `name_lo` 예시로 바꾸는 것이 좋다.
