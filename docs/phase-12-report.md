# Phase 12 LMS 연동 기반 결과 보고

- 작성일: 2026-10-23
- 대상: 개발팀, 프로젝트 관리자
- 관련: [이식 계획 12절, 14절 Phase 12](porting-plan.md#phase-12-lms-연동-기반-sm), [설계 결정 0017](decisions/0017-lms-integration.md), [LMS 연동 가이드](lms-integration-guide.md), [관리자 매뉴얼 28장](manual/admin/28-lms-integration.md)

## 1. 요약

특정 LMS를 붙이지 않고 연동 자리를 만들었다: 플랫폼(드라이버, 암호화 인증 정보), 강좌·사용자 연결, 단계별 동기화 기록, `LmsConnector` 인터페이스와 `NullConnector`, 도메인 이벤트(강좌 공개, 수강 확정, 수강 취소, 성적 확정) → 큐 작업, 야간 전체 대조, 플랫폼 최종 점수를 성적 입력 초안으로 불러오기, 관리 화면 3개와 포털 링크, 명령 2개, 다음 단계 가이드.

- 테스트 453개(단언 2,350개) 통과. Phase 11 대비 4개 추가(LMS 4). 전체 실행 4분 54초. Larastan 레벨 6 오류 0, Pint 위반 0, 명명 검사 위반 0, 번역 누락 0
- 빈 DB에서 `migrate:fresh` → `setup:install --force --profile=korean-university --grade-scale=4.5`, `setup:demo --size=small --seed=42` 성공(데모 약 30초).
  - 데모: null 플랫폼 `DEMO`에 이번 학기 강좌 20개 연결, 사용자 연결 3건(데모에서 계정이 있는 구성원만), 동기화 기록 23건(실패 0)
- CI(GitHub Actions): 7절

## 2. 완료 기준

계획서 12절 산출물

| 항목 | 결과 | 근거 |
|---|---|---|
| `lms_platforms`, `lms_course_links`, `lms_user_links`, `lms_sync_logs` | 충족 | 마이그레이션, RLS, 인증 정보 암호화·감사 비밀 칼럼 |
| `LmsConnector` 인터페이스와 `NullConnector` | 충족 | 6개 메서드, 결정적 ID, `ConnectorRegistry`(미구현 드라이버는 실패 기록, `LmsTest`) |
| 도메인 이벤트 → 연동 작업 큐 리스너 | 충족 | `SectionPublished`, `EnrollmentConfirmed`, `EnrollmentDropped`(신규), `GradesFinalized`(기존) → `QueueLmsSync` → `SyncSectionJob`/`SyncEnrollmentJob`(`afterCommit`, 비활성 플랫폼 제외, `LmsTest`) |
| `lms:sync`, `lms:reconcile` | 충족 | 밀어넣기(학기·강좌, dry-run), 대조(빠진 구성원 추가·남은 구성원 제거, 매일 02:30) |
| `docs/lms-integration-guide.md` | 충족 | 원칙, 기반 설명, 커넥터 구현 절차, Moodle/Open edX/OneRoster 메모, 운영 |

13.5절 공통 완료 기준

| 기준 | 결과 |
|---|---|
| 명령과 테스트 | `lms:sync`, `lms:reconcile` + `LmsTest` |
| 매뉴얼 | 관리자 매뉴얼 28장, 목차, 운영 명령 레퍼런스, 연동 가이드 |
| 시더, 팩토리, 데모 | 역할 템플릿 권한 `lms.manage`. 데모: null 플랫폼 `DEMO`와 이번 학기 강좌 20개 연결 |
| `setup:install`, `setup:demo --size=small` | 빈 DB에서 성공 |
| 번역 en, ko, zh_CN | 화면, 상태, 포털 링크, 성적 입력 버튼 |
| 결과 보고 | 이 문서 |

## 3. 구현 내용

- 데이터·보안: 플랫폼 인증 정보 `encrypted:array`, 모델 `hidden`, 데이터 감사 비밀 칼럼. RLS: 강좌 연결은 로그인 사용자 조회, 사용자 연결은 본인, 기록·쓰기는 `lms.manage`.
- 업무 로직(`app/Domain/Lms`): `SyncSection`(강좌 보장 → 교원·수강생 구성원 보장, 링크 상태·오류), `SyncEnrollment`, `PullGrades`(수강별 점수 매핑), `ReconcilePlatform`(차이 로그 + 수정), `SyncLogger`.
- 화면: **LMS 연동 > LMS 플랫폼**(드라이버·인증 정보·옵션), **강좌 연결**(상태, 다시 동기화), **동기화 기록**. 포털 강좌 상세·담당 강좌의 플랫폼 강좌 링크. 성적 입력의 **LMS 점수 불러오기**(초안만, 저장 안 함).

## 4. 레거시와의 차이

레거시에는 LMS가 없었고 강의실 기능이 학사 시스템 안에 있었다. 신규 시스템은 강의실 기능을 LMS에 맡기고(Phase 10) 학사 데이터만 내보낸다.

## 5. 하지 않은 것, 남은 일

- 실제 커넥터(Moodle, Open edX), LTI 1.3, 통합 로그인(IdP), OneRoster 내보내기(가이드 4절).
- 점수 자동 저장(초안 불러오기까지), 강좌 취소 시 플랫폼 강좌 정리.
- 플랫폼별 역할 매핑 UI.

## 6. 검토 요청 사항

1. 첫 대상 플랫폼(Moodle 4.x 가정). 가이드의 함수 목록은 대상 버전에서 검증이 필요하다.
2. 통합 로그인용 IdP(Keycloak 등)를 학사 시스템 바깥에 둔다는 전제.
3. 성적 확정 결과를 LMS로 보내지 않는 원칙.

## 7. CI 결과

커밋 `ab3ca66`, GitHub Actions 실행 39995031167: 성공.

| 작업 | 결과 | 소요 |
|---|---|---|
| Style, static analysis, conventions | 성공 | 44초 |
| Tests (PostgreSQL 18) | 성공 | 8분 9초 |
