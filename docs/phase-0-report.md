# Phase 0 기반 구축 결과 보고

- 작성일: 2026-10-22
- 대상: 개발팀, 프로젝트 관리자
- 관련: [이식 계획 14절 Phase 0](porting-plan.md#phase-0-기반-구축-l)

## 1. 요약

Phase 0 작업 항목 11개를 모두 구현했고, 완료 기준 5개 중 4개를 자동 테스트로 확인했다. 나머지 1개(CI 통과)는 CI와 같은 검사를 로컬 Docker 환경에서 통과시켰지만, 원격 저장소가 아직 없어 GitHub Actions에서는 실행하지 못했다.

- 테스트 109개(단언 254개) 통과, 약 7초
- Larastan 레벨 6 오류 0, Pint 위반 0, 명명 검사 위반 0, 번역 누락 0 (ko, zh_CN)
- `composer audit` 보안 권고 없음

## 2. 완료 기준

| 완료 기준 | 결과 | 근거 |
|---|---|---|
| 2단계 인증 정책 변경이 즉시 반영 | 충족 | `MfaPolicyTest`: 권장 → 전체 필수로 바꾸면 다음 요청에서 등록 화면으로 이동. 역할별 필수, 유예 기간, 관리 화면 차단 포함 |
| 역할/할당 변경만으로 메뉴와 데이터 범위가 바뀜 | 충족 | `AuthorizationTest`: 할당 → 관리 화면 접근, 종료 → 403. 역할에 권한 추가 → 해당 화면 열림, 메뉴 항목 표시. 범위(조직 단위, 프로그램) 병합, 유효기간 밖 할당 무시 |
| 트리거 감사가 애플리케이션 외부 변경도 기록 | 충족 | `AuditTriggerTest`: Eloquent를 거치지 않은 INSERT도 기록, 비밀 컬럼 마스킹, 감사 테이블 수정/삭제 차단(소유자 포함) |
| RLS가 다른 사용자 데이터 조회를 DB에서도 차단 | 충족 (PoC 테이블) | `RowLevelSecurityTest`: ID를 알고 직접 조회해도 0건, 컨텍스트 없으면 0건, 큐 작업/요청 종료 후 컨텍스트 초기화 |
| CI 통과 | 부분 충족 | `.github/workflows/ci.yml` 작성. 같은 검사(`composer audit`, `composer check`)를 로컬 Sail에서 통과. GitHub에서 실행 기록 없음 |

## 3. 작업 항목별 결과

| 계획 항목 | 결과 | 주요 위치 |
|---|---|---|
| Laravel 13 + PHP 8.5, Sail, 버전 고정 | Laravel 13.38.0, PHP 8.5, PostgreSQL 18.6, Redis 8, Mailpit, Gotenberg 8. `composer.lock`, `package-lock.json` 고정 | `compose.yaml`, `composer.json` |
| Pest, Larastan, Pint, 원시 SQL 검사, `composer audit`, CI | 원시 SQL 검사는 자체 PHPStan 규칙으로 구현 (상수 문자열이 아닌 SQL 탐지) | `tools/phpstan/Rules/NoDynamicRawSqlRule.php`, `.github/workflows/ci.yml` |
| 명명 규칙 확정, 리뷰 체크리스트 | 확정. 용어 추가(persona, role assignment 등), 약어 허용 목록에 `pii`, `i18n` 추가. 마이그레이션 이름 자동 검사 명령 | `docs/conventions/`, `php artisan naming:check` |
| 스키마(`audit`, `reporting`, `util`), DB 역할 분리 | `app_migrator`/`app_runtime`/`app_reporting`. 마이그레이션 명령은 자동으로 소유자 연결 사용 | [0002](decisions/0002-database-roles-schemas-audit.md) |
| 공용 로그인(Fortify), 2단계 인증 정책, Filament 연동 | Fortify 공용 구현 채택(Filament 내장 MFA와 비교). 정책 4단계, 유예 기간, 기기 기억, 관리자 초기화(사유 필수) | [0001](decisions/0001-shared-authentication.md) |
| 권한 카탈로그, 역할 관리 화면, 범위 할당, persona 기반 | 카탈로그 enum + 동기화 명령, 역할 화면(다국어 이름, 권한 선택), 사용자별 할당 화면(범위, 기간, 사유), persona 판정 인터페이스, 레거시 역할 템플릿 | [0005](decisions/0005-authorization-model.md) |
| 감사 3층, 감사 조회 화면 | 업무 감사(사유 포함), 데이터 감사(트리거), 보안 로그. 관리 화면에 세 가지 조회 화면 | `app/Filament/Admin/Resources/{Activities,RecordChanges,SecurityEvents}` |
| RLS PoC (Octane/큐 포함) | 정책 패턴 확정, 50만 건 측정. 함수를 `(SELECT ...)`로 감싸면 1,156 ms → 25 ms | [0003](decisions/0003-row-level-security.md) |
| 개인정보 암호화 캐스트, 검색 인덱스, 마스킹/표시 해제 | 별도 키(AES-256-GCM), 이전 키 목록, HMAC 검색 해시, 유형별 마스킹, 표시 해제 시 보안 로그, 키 교체 재암호화 큐 배치 | `app/Support/Pii`, `HasEncryptedPii`, `RevealSensitiveValue` |
| 다국어 기반 | 로케일 결정(사용자 > 세션 > 브라우저 > 기관 기본), 언어 전환, `util.i18n` 함수 3종, ICU 정렬, translatable trait, 번역 누락 검사와 내보내기/가져오기. 화면 전체 en/ko/zh_CN 번역 | `lang/`, `database/sql/functions/i18n.sql` |
| PDF 렌더링 검증 | Gotenberg 채택. 한/중/영 혼합 문서에서 Noto CJK KR, SC 임베딩 확인 | [0004](decisions/0004-pdf-rendering.md) |

추가로 한 일

- 비밀번호 해시 Argon2id 적용.
- Eloquent strict mode, 요청 ID(`X-Request-Id`)를 로그/큐/감사에 전파(Laravel Context).
- Horizon 대시보드를 권한(`queue_monitor.view`)으로 보호.
- 설치용 명령 `security:create-administrator` (비대화식 설치 시 표준 입력으로 비밀번호).

## 4. 계획과 달라진 점

| 항목 | 계획 | 실제 | 이유 |
|---|---|---|---|
| 사용자-역할 연결 | spatie/laravel-permission | 역할/권한 저장만 spatie 사용, 사용자 연결은 `role_assignments` | spatie의 사용자 연결은 범위와 유효기간을 표현할 수 없음 |
| 감사 테이블 쓰기 권한 | "트리거만 기록" / "INSERT 외 권한 없음"이 섞여 있었음 | `record_changes`는 트리거만, `activity_log`·`security_events`는 런타임이 추가만 가능 | 업무 감사와 보안 로그는 애플리케이션이 직접 써야 함 |
| Fortify 패스키 | 언급 없음 | 사용 안 함 | 2단계 인증 정책과의 관계를 정한 뒤 검토 |

## 5. 남은 일과 위험

| 항목 | 내용 | 시점 |
|---|---|---|
| CI 실제 실행 | 원격 저장소 생성 후 GitHub Actions 첫 실행 확인 | Phase 1 시작 전 |
| 이메일 일회용 코드 | 2026-10-22 구현 완료 ([0001](decisions/0001-shared-authentication.md)) | 완료 |
| 감사 해시 체인 | 선택 사항. 미구현 | Phase 13 |
| 감사 보존 기간 정리 | 수정 차단 트리거를 우회하는 운영 절차 필요 | Phase 13 |
| Octane | 미설치. 연결 재사용 대비는 구현과 테스트 완료 | Phase 13 부하 테스트 |
| 개인정보 표시 해제 화면 | Action만 있음. 암호화 컬럼이 생기는 Phase 2에서 화면 구성 | Phase 2 |
| validation 메시지 번역 | Laravel 기본 검증 메시지는 영어. ko/zh_CN 번역 파일 추가 필요 | Phase 1 |
| 조직 단위 범위 선택 | 할당 화면에서 범위 ID를 숫자로 입력. 조직 단위/프로그램 선택기로 교체 | Phase 1 |

## 6. 실행 방법

[README](../README.md)의 "개발 환경 준비"를 따른다. 전체 검사는 `./vendor/bin/sail composer check`.
