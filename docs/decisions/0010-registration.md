# 0010. 수강신청: 규칙 엔진, 좌석, 대기자

- 상태: 채택 (2026-10-22, Phase 5)
- 관련: [이식 계획 10.6절, 10.7절, 17.6](../porting-plan.md), [0009 교과목, 학기 세션, 개설강좌](0009-courses-sections-and-sessions.md), [관리자 매뉴얼 20장](../manual/admin/20-registration.md)

## 데이터

| 테이블 | 내용 |
|---|---|
| `enrollments` | 학생 × 강좌. 상태 `enrolled`, `dropped`, `withdrawn`, `completed`. 신청 시점의 과목, 버전, 학점, 교과 구분, 학점 상한 포함 여부를 복사해 둔다(나중에 교과목이 바뀌어도 그 학기 기록은 그대로). 재수강이면 `is_retake`, `repeats_enrollment_id`. 성적 칸(`grade_status` 등)은 Phase 6 |
| `waitlist_entries` | 대기. `waiting` → `promoted`, `removed`, `expired`. 결과 사유 `outcome_note` |
| `registration_holds`, `registration_overrides` | 레거시 `SRDENY`, `SRALLOW` |
| `registration_rule_sets`, `registration_rules` | 학기당 규칙 세트 하나, 규칙별 사용 여부·심각도·예외 허용·매개변수(JSON 객체) |
| `credit_limit_policies` | 학기 종류 + 대상 조건 + 최대 학점 + 우선순위 |
| `cart_items` | 장바구니 |
| `registration_attempts` | 모든 시도와 규칙별 결과. 추가만 한다 |

- 부분 unique 인덱스: 한 강좌에 유효한 수강 하나(`enrolled`, `completed`), 한 학기에 같은 과목 신청 하나(`enrolled`), 한 강좌에 대기 하나(`waiting`). 이중 클릭이나 동시 요청이 같은 학생을 두 번 넣지 못한다. 위반은 "이미 신청" 거절로 바꾼다.
- 카운터: `sections.enrolled_count`, `section_seat_groups.enrolled_count`. CHECK로 정원 이하를 보장한다.

## 좌석 확보: 강좌 행 잠금

10.7절은 조건부 UPDATE(`enrolled_count < capacity`)를 기본으로 적었다. 좌석 할당(유보석)이 들어오면서 바꿨다.

- 일반석 = 정원 − 신청 인원 − **아직 전환되지 않은 할당 그룹의 남은 좌석**. 학생이 할당 그룹 대상이면 그 그룹 좌석을 먼저 쓴다. 이 계산은 그룹별 대상 조건과 전환 시각이 필요해 UPDATE 한 문장의 조건으로 쓰기 어렵다.
- 그래서 트랜잭션 안에서 `SELECT ... FOR UPDATE`로 강좌 행 하나를 잠그고, 좌석을 계산해 카운터를 올리고 수강 행을 넣은 뒤 커밋한다. 잠금은 그 강좌에만, 규칙 검사가 끝난 뒤 짧게 걸린다(규칙 검사는 잠금 밖).
- 검증: `tests/Database/Registration/ConcurrentRegistrationTest.php`가 실제 프로세스 16개로 정원 5인 강좌에 동시에 신청해 정확히 5명만 들어가는지 확인한다. 잠금을 빼면 실패하는 것을 확인했다.
- 정원 변경(`UpdateSection`)도 같은 행을 잠그고 신청 인원 미만으로 줄이지 못하게 한다.

## 규칙 엔진

- 규칙 목록은 코드(`RuleCatalog`)다. 권한처럼 코드가 카탈로그이고, 학기 규칙 세트는 그 설정만 저장한다. 규칙 세트가 없거나 규칙 행이 없으면 규칙의 기본값을 쓴다.
- **항상 적용되는 규칙**: 기간, 개설 상태, 정원. 규칙 세트에 나타나지 않는다. 정원은 엔진이 아니라 좌석 확보 단계에서 판단한다.
- 엔진은 모든 규칙을 검사해 결과를 모두 돌려준다(첫 실패에서 멈추지 않음). 학생에게 모든 이유가 보이고 `registration_attempts`에 남는다.
- 실패한 규칙이 통과로 바뀌는 경우: 규칙의 **예외 허용**이 켜져 있고, (a) 학생에게 유효한 규칙 예외가 있거나 (b) 직원이 신청하면서 그 규칙을 골랐을 때. 직원 신청과 직원 취소·철회에는 기간 규칙을 적용하지 않는다. 예외로 통과한 규칙은 기록에 "실패 + 예외 적용"으로 남는다.
- 대기 신청은 정원을 뺀 모든 규칙을 통과해야 한다. **승격 때 모든 규칙을 다시 검사**한다(`EnrollStudent`를 시스템으로 다시 호출). 정원이 아닌 이유로 실패하면 대기에서 제외하고 사유를 남긴다.
- 레거시 규칙 재현: `YustLegacyRegistrationProfileSeeder`(프로파일 `yust-legacy-registration`)가 학점 상한 7개와 학점은행 과목 제외를 설정으로 넣는다. `tests/Feature/Registration/LegacyRulesTest.php`가 학번·학과·학기 조합별 상한을 확인한다. 3.4절의 21학점 버그는 재현하지 않는다.

## 대기자 승격

- 자리가 나는 때: 취소·철회(`DropEnrollment`가 커밋 뒤 `PromoteWaitlistJob` 발송), 유보석 전환(`registration:release-reserved-seats`가 5분마다 전환 시각이 지난 그룹을 처리하고 작업 발송), 정원 증가(직원이 **지금 승격** 또는 매시간 `waitlist:promote`).
- `PromoteWaitlistJob`은 강좌별 `ShouldBeUnique`. 한 강좌의 승격이 동시에 두 번 돌지 않는다.
- 17.6 질문 6의 기본값대로 **자동 신청 + 메일 알림**만 구현했다. "자리 제안 후 수락 기한" 방식은 구현하지 않았다(설정 항목도 없음). 필요해지면 대기 상태 `offered`와 기한 만료 작업을 추가한다.
- 수강신청·수강 정정 기간이 모두 닫힌 강좌의 대기는 만료한다.

## 감사

- 직원의 신청·취소·철회, 제한 설정·해제, 예외 허용·취소, 규칙 세트 준비는 `AuditLogger::record()`로 사유와 함께 기록한다. 학생 본인의 신청은 `registration_attempts`가 기록이다.
- DB 감사 트리거 `util.enable_audit(table, redacted, ignored)`에 세 번째 인수(무시할 열)를 추가했다. 좌석 카운터(`enrolled_count`, `release_processed_at`)만 바뀌는 UPDATE는 데이터 변경 기록을 남기지 않는다. 신청마다 강좌 행이 바뀌어 기록이 폭증하는 것을 막는다. 카운터는 `updated_at`을 건드리지 않는다.
- `registration_attempts`, `cart_items`는 감사 트리거를 걸지 않는다(시도 기록 자체가 기록, 장바구니는 임시 데이터).

## 행 수준 보안

- 학생은 자기 학적의 수강, 대기, 장바구니, 시도 기록만 본다. 직접 쓰기는 정책이 허용하는 값으로 제한한다(예: 학생은 `enrolled`로 넣고 `dropped`, `withdrawn`으로만 바꿀 수 있으며 성적 칸은 못 바꾼다).
- 직원은 강좌 개설 조직 범위(`enrollments.view`, `registration.manage`) 또는 학적 조회 범위로 본다. 담당 교원은 자기 강좌 수강생을 본다.
- 시도 기록은 본인 이름(`actor_id`)으로만 넣을 수 있다.

## 하지 않은 것

- 멱등 키, 강좌 목록 캐시(10.7절): 부분 unique 인덱스와 사용자별 요청 제한(분당 30회)으로 시작한다. Phase 13 부하 테스트에서 다시 본다.
- 직전 학기 평점에 따른 추가 학점(10.6절 `신규`): 평점은 Phase 6. 지금은 대상 조건(학년, 입학 연도, 프로그램, 조직)으로만 상한을 고른다.
- 대기 순서 변경 화면.
