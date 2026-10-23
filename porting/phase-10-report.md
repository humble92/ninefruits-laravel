# Phase 10 교수 강의실 최소 기능과 출석 결과 보고

- 작성일: 2026-10-23
- 대상: 개발팀, 프로젝트 관리자
- 관련: [이식 계획 10.9절, 14절 Phase 10](porting-plan.md#phase-10-교수-강의실-최소-기능과-출석-sm), [설계 결정 0015](decisions/0015-attendance.md), [관리자 매뉴얼 25장](../docs/manual/ko/admin/25-classroom-and-attendance.md)

## 1. 요약

교원 포털에 **수강생과 출석** 화면(사진 포함 명단, 명단 CSV, 출석부 PDF, 수업 회차 생성·추가·휴강, 회차별 출석 입력)을, 학생 포털에 **출석** 화면을, 관리 화면 개설강좌에 **수업 회차** 탭을 추가했다. 레거시는 출석을 전혀 기록하지 않았고 출석부는 빈 칸 인쇄였다. 작업 중 교원이 담당 강좌 수강생의 학적·인물을 RLS에서 볼 수 없던 기존 결함(지도 학생만 허용)을 발견해 정책을 추가했다.

- 테스트 442개(단언 2,176개) 통과. Phase 9 대비 5개 추가(출석 5). 전체 실행 4분 40초. Larastan 레벨 6 오류 0, Pint 위반 0, 명명 검사 위반 0, 번역 누락 0
- 빈 DB에서 `migrate:fresh` → `setup:install --force --profile=korean-university --grade-scale=4.5`, `setup:demo --size=small --seed=42` 성공(데모 약 40초, 출석 시더 3초(대량 삽입)).
  - 데모: 이번 학기 개설강좌의 수업 회차 1,404개, 출석 기록 3,922건(출석 3,324 · 지각 302 · 결석 213 · 공결 83), 강좌 5개 중 1개는 최근 회차 미입력
- CI(GitHub Actions): 7절

## 2. 완료 기준

계획서 10.9절 항목

| 항목 | 결과 | 근거 |
|---|---|---|
| 담당 강좌 목록, 강좌 정보 | 충족(Phase 4·6에 있던 것) | **담당 강좌**에 **수강생과 출석** 링크 추가 |
| 수강생 명단(사진 포함, 권한 범위 내) | 충족 | 사진, 학번, 이름, 과정, 학년 + 출석 합계. 담당 교원과 `sections.view/manage` 범위 직원만. 교원의 수강생 학적 조회 RLS 정책 추가(`AttendanceTest` DB 수준 검증) |
| 명단 엑셀 | 충족(CSV) | UTF-8 BOM CSV, 내려받기 감사. `.xlsx` 라이브러리는 넣지 않았다 |
| 출석 회차(강의 시간 기준 자동 생성) | 충족 | 강의 시간의 요일·기간으로 멱등 생성. 교원 버튼, 관리 화면, `attendance:generate-sessions`. 보강 추가, 휴강·되돌리기 |
| 출석/지각/결석/공결 입력 | 충족 | 회차별 입력, 전원 출석, 비고, 바뀐 것만 기록·감사. 미래 회차·휴강·미수강생 거부 |
| 출석부 PDF | 충족 | 가로 A4, 학생 × 회차, O/△/X/공, 합계 |
| 공지·과제·자료는 LMS로 | 해당 없음 | 구현하지 않음(12절) |

13.5절 공통 완료 기준

| 기준 | 결과 |
|---|---|
| 명령과 테스트 | `attendance:generate-sessions`(`--section`, `--dry-run`) + `AttendanceTest` |
| 매뉴얼 | 관리자 매뉴얼 25장 추가, 목차, 운영 명령 레퍼런스 |
| 시더, 팩토리, 데모 | 데모: 이번 학기 모든 개설강좌의 회차, 진행된 회차의 출석(일부 미입력) |
| `setup:install`, `setup:demo --size=small` | 빈 DB에서 성공 |
| 번역 en, ko, zh_CN | 화면, PDF, 오류 문구 |
| 결과 보고 | 이 문서 |

## 3. 구현 내용

### 데이터

- `class_meetings`(강좌·날짜·시작 시간 unique, 상태/휴강 사유 CHECK), `attendance_records`(회차·수강 unique, 상태 CHECK). 감사 트리거.
- RLS(`database/sql/policies/attendance.sql`): 회차는 로그인 사용자 모두 조회, 쓰기는 담당 교원·`sections.manage`. 출석 기록은 수강을 보는 사람이 조회, 쓰기는 교원·직원.
- **`student_records_instructor_select`**: 담당 강좌 수강생의 학적을 교원이 조회. `enrollments`↔`student_records` 정책 상호 참조로 인한 무한 재귀를 피하려고 `SECURITY DEFINER` 함수 `util.taught_student_record_ids()`를 두었다. `StudentRecord::constrainVisibleTo`에 같은 조건 반영.

### 업무 로직 (`app/Domain/Attendance`)

- `GenerateClassMeetings`(강좌·학기, 멱등), `ManageClassMeetings`(추가·휴강·되돌리기·주제), `RecordAttendance`(검증, 변경분만 저장, 감사에 학생별 이전·이후), `AttendanceSummary`(진행 회차, 출석률), `SectionRoster`(명단, CSV, 조회 권한).

### 화면

- 교원 포털 `/portal/sections/{section}/attendance`: 명단과 합계, 회차 목록(입력 수/수강생 수), 회차 생성·추가·휴강, 출석 입력 폼.
- 학생 포털 `/portal/attendance`: 학기별 과목 출석 합계와 회차별 기록.
- 관리 화면 개설강좌 **수업 회차** 탭: 회차 생성, 휴강, 되돌리기.
- 다운로드: `/sections/{section}/roster.csv`, `/sections/{section}/attendance-sheet`(PDF).

## 4. 레거시와의 차이

| 레거시 | 신규 | 이유 |
|---|---|---|
| 출석 기록 없음, 빈 칸 출석부 | 회차별 기록, 집계, 기록된 출석부 | 10.9절 범위 |
| 날짜 없는 주간 시간표 | 강의 시간 → 날짜별 회차(보강·휴강) | 집계 기준 |
| 명단 팝업에 주민번호·주소 | 사진, 학번, 이름, 과정, 학년, 이메일 | 개인정보 최소화 |
| 명단 엑셀 없음 | CSV(감사) | 요구 사항 |
| 성적 입력이 담당 여부 미확인 | 액션·RLS 모두 담당 교원 확인 | 보안 |

## 5. 하지 않은 것, 남은 일

- 결석 상한(1/3 규칙)·지각 환산·출석 점수 자동 반영. 대학별 규칙이라 설정으로 둘지 검토 필요.
- 공휴일 달력(휴강 처리로 대신), 강의 시간 변경 시 회차 자동 삭제.
- 학생의 공결 신청·승인.
- `.xlsx` 내보내기.

## 6. 검토 요청 사항

1. 출석률 정의: (출석 + 지각 + 공결) ÷ 진행 회차. 지각을 다르게 셀지.
2. 교원에게 연 수강생 정보 범위(사진, 학번, 이름, 과정, 학년, 연락용 이메일). 전화번호를 포함할지.
3. 기존 결함 정정: 교원이 담당 강좌 수강생의 학적을 보게 한 RLS 정책. 이전엔 지도 학생만 보여 성적 입력·명단에서 이름이 비었을 상황이다(Livewire 테스트가 DB 컨텍스트를 타지 않아 못 잡았음). Livewire 테스트에 DB 컨텍스트를 태우는 개선을 Phase 13에 넣을지.

## 7. CI 결과

| 커밋 | 실행 | 결과 | 비고 |
|---|---|---|---|
| `52f2cd9` Phase 10 | 39962000527 | 테스트 실패 | `GradingAdminTest`가 무작위 `tiny` 데모에서 성적우수 학생 0명을 만나 실패(Phase 10과 무관한 기존 불안정 테스트). 스타일·정적 분석 통과(52초) |
| `a51dee5` 데모 테스트 시드 고정 | 39964034262 | 성공 | 스타일 46초, 테스트 11분 59초 |

데모 기반 관리 화면 테스트 두 개(`GradingAdminTest`, `RegistrationAdminTest`)가 `DemoSeeder`처럼 faker와 `mt_rand`에 시드를 주도록 바꿔 결정적으로 만들었다.
