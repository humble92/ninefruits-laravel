# 0008. 입학 지원과 학생 전환

- 상태: 채택 (2026-10-22, Phase 3)
- 관련: [이식 계획 10.1절, 10.2절](../porting-plan.md), [0007 인물, 학적, 계정](0007-people-accounts-and-sign-in.md)

레거시에는 입학 지원 기능이 없다(`신규`). 목표는 "지원부터 첫 수강신청까지 같은 계정"이다.

## 모델

| 테이블 | 내용 |
|---|---|
| `admission_cycles` | 모집 회차. 접수 시작/마감(마감 NULL = 상시 접수), 공개 여부, 결정 즉시 발표, 합격 응답 기한(일). 학기와 연결하지 않는다(입학 학기는 학적 생성 때 정함) |
| `admission_cycle_programs` | 모집 프로그램과 모집 인원(참고용, 강제하지 않음) |
| `admission_cycle_document_types` | 제출 서류(코드 목록 `application_document_type`)와 필수 여부 |
| `applications` | 지원 1건. 인물, 회차, 프로그램, 관리 조직(프로그램의 조직, 범위 권한용), 지원 내용, 상태와 단계별 시각, 결정, 발표 시각, 응답 기한, 생성된 학적 |
| `application_number_sequences` | 회차별 수험 번호 일련번호. 감사 대상 아님 |

- 상태: `draft → submitted → under_review → admitted | rejected`, `admitted → confirmed | withdrawn`. 학적 생성 전이면 언제든 `withdrawn`. 작성 중인 채 마감되거나 오래 방치되면 `expired`(매일 스케줄). 계획의 7개 상태에 `expired`를 더했다. 방치된 초안이 같은 회차 재지원을 막지 않게 하려는 것이다.
- 회차당 진행 중 지원서 하나(부분 unique 인덱스, 닫힌 상태 제외).
- 수험 번호는 제출할 때 `{회차 코드}-{00001}`로 붙인다. 제출 여부와 번호 유무가 일치하도록 CHECK 제약을 둔다.
- 결정과 발표를 분리했다. `decided_at`(담당자 결정)과 `decision_released_at`(지원자 공개)이 따로 있고, 발표 전 지원자에게는 `under_review`로 보인다(`Application::applicantStatus()`). 발표 전에는 결정을 바꿀 수 있고, 발표 후에는 바꿀 수 없다. 상시 접수 회차는 `should_release_decisions_immediately`로 결정 즉시 발표한다.
- 증빙은 medialibrary(`documents` 컬렉션, 사용자 정의 속성 `document_type_id`), 비공개 디스크. 다운로드는 권한 확인 라우트(`applications.documents.show`)로만 한다.

## 계정과 학생 전환

- 지원자 가입은 Fortify registration + email verification. `RegisterApplicant`가 인물과 계정을 함께 만든다. 지원 화면은 `verified` 미들웨어로 이메일 확인을 요구한다. 가입은 설정(`admissions.applicant_registration_enabled`)으로 끌 수 있다.
- `User`가 `MustVerifyEmail`을 구현한다. 이메일 확인을 요구하는 화면은 지원 화면뿐이라 기존 교직원 계정에는 영향이 없다. 로그인 이메일을 바꾸면 새 주소를 다시 확인해야 한다(7.1절).
- 이미 학적이 있는 사람(재학생, 졸업생)은 기존 계정으로 지원한다. 이름, 생년월일 같은 신원 정보는 학적이나 교원 정보가 생기면 학적 담당이 관리하고, 지원서에서는 학적이 없는 사람만 고칠 수 있다.
- `MatriculateApplicant`는 Phase 2의 `RegisterStudent`로 학적을 만든다(새 학번, 입학일 = 입학 학기 첫날, 입학 구분 = 지원서 값). 인물과 계정이 그대로이므로 persona가 지원자에서 학생으로 바뀐다. 재입학이면 이전 학적은 그대로 남는다.
- persona `applicant`: 진행 중이고 학적이 아직 없는 지원서가 있으면 부여된다. 재학생이 다른 프로그램에 지원 중이면 학생이자 지원자다.
- 인물 병합은 지원서도 옮긴다. 두 인물에게 같은 회차의 진행 중 지원서가 있으면 병합을 거부한다.

## 권한과 행 수준 보안

| 권한 | 범위 |
|---|---|
| `admission_cycles.manage` | 전역(회차, 설정) |
| `applications.view` | 전역 / 조직 단위(하위 포함) / 프로그램 |
| `applications.review` | 같은 범위. 조회도 포함 |
| `applications.matriculate` | 같은 범위 |

RLS(`database/sql/policies/admissions.sql`)

- 지원자: 자기 지원서 조회. 추가는 `draft`로만. 수정은 `draft/submitted/under_review/admitted`인 행을 `draft/submitted/confirmed/withdrawn`으로만 남길 수 있다. 지원자가 SQL로 직접 합격 처리할 수 없다(`ApplicationAccessTest`).
- 담당자: `view` 또는 `review` 범위로 조회, `review` 범위로 수정.
- `people_via_applications_select`: 볼 수 있는 지원서의 지원자 인물을 볼 수 있다.

### 시스템 컨텍스트로 잠시 전환 (`DatabaseSessionContext::asSystem`)

세 경우는 호출자의 RLS 범위를 넘는 쓰기가 업무상 정당하다.

| 경우 | 이유 |
|---|---|
| 지원자 가입 | 계정이 아직 없어 인물 행을 만들 수 없다 |
| 학적 생성 | 입학 담당은 학적 관리 권한이 없어도 합격자 학적은 만들어야 한다 |
| 인물 병합 중 지원서 이동 | 학적 담당은 지원서 수정 권한이 없다 |

애플리케이션이 권한을 먼저 확인한 뒤 해당 단계만 `system:{이름}` 수행자로 실행하고 이전 컨텍스트로 되돌린다. `app.user_id`는 유지하므로 트리거 데이터 변경 기록에 실제 담당자가 남고, 업무 감사도 담당자 이름으로 기록한다. 다른 곳에는 쓰지 않는다. 새로 쓰려면 이 표에 추가한다.

## 고려했지만 택하지 않은 것

| 대안 | 택하지 않은 이유 |
|---|---|
| 회차에 입학 학기 고정 | 상시 접수는 합격 시점에 따라 입학 학기가 달라진다(10.2절) |
| 모집 인원 초과 시 합격 차단 | 추가 합격, 정원 외 전형 등 사람이 판단할 일이 많다. 화면에 인원을 보여 주는 데 그친다 |
| 지원서 항목을 회차별로 설계(폼 빌더) | 최소 기능 범위 밖. 공통 항목(출신 학교, 자기소개서, 서류)으로 시작하고 수요가 확인되면 확장 |
| 입학 담당에게 `student_records.manage` 부여 | 학적 수정 권한이 너무 넓다. 학적 생성만 허용하려고 시스템 컨텍스트 전환을 택했다 |
