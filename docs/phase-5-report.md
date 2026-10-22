# Phase 5 수강신청 결과 보고

- 작성일: 2026-10-22
- 대상: 개발팀, 프로젝트 관리자
- 관련: [이식 계획 14절 Phase 5](porting-plan.md#phase-5-수강신청-l), [설계 결정 0010](decisions/0010-registration.md), [관리자 매뉴얼 20장](manual/admin/20-registration.md)

## 1. 요약

학기별 규칙 세트와 규칙 엔진, 수강신청 제한(hold)과 규칙 예외(override), 학점 상한 정책, 장바구니, 시차 오픈(Phase 1 업무 기간 사용), 좌석 할당 적용, 대기자 자동 승격, 학생 신청·취소·철회 화면과 시간표, 직원 수강 조정, 수강 현황, 수강신청 기록을 구현했다.

- 테스트 355개(단언 1,579개) 통과. Phase 4 대비 34개 추가. Larastan 레벨 6 오류 0, Pint 위반 0, 명명 검사 위반 0, 번역 누락 0, `composer audit` 통과
- 빈 DB에서 `setup:install --force --profile=korean-university`, `setup:demo --size=small --seed=42` 성공(데모 약 15초). 데모 수강 270건, 모든 강좌의 신청 인원 카운터가 수강 행 수와 일치하고 정원 초과 0건
- CI(GitHub Actions) 통과 (7절)

## 2. 완료 기준

| 완료 기준 | 결과 | 근거 |
|---|---|---|
| 레거시 샘플 규칙을 설정만으로 재현 (3.4절 버그는 재현하지 않음) | 충족 | `LegacyRulesTest`(10개, 데이터셋 포함): `yust-legacy-registration` 프로파일이 넣은 학점 상한 7개로 입학 연도(2013/2014)·간호학부 여부·학기 종류(정규/계절) 8가지 조합의 상한을 확인. 14학번 이후 간호학부는 21학점(18+3 신청 성공, 다음 3학점 거절). 학점은행 과목(`009%`, `900%`)은 상한 계산에서 제외. 코드 변경 없이 설정 행만으로 재현 |
| 동시 요청에서도 정원 초과 없음 | 충족 | `ConcurrentRegistrationTest`: 실제 프로세스 16개(`pcntl_fork`, 각자 DB 연결)가 정원 5인 강좌에 동시에 신청 → 정확히 5명 신청, 11명은 정원 마감으로 거절, 카운터와 수강 행 모두 5. 강좌 행 잠금을 빼면 이 테스트가 실패하는 것을 확인. DB CHECK(`enrolled_count <= capacity`)가 마지막 방어선 |
| 대기자 승격 시 규칙 재검증 | 충족 | `EnrollmentTest`: 대기 중 다른 과목을 신청해 시간이 겹치게 된 학생은 자리가 나도 승격되지 않고 사유와 함께 제외, 다음 순번 학생이 승격. 승격은 `EnrollStudent`를 다시 호출하므로 모든 규칙을 그대로 검사. 결과 메일 발송 |

13.5절 공통 완료 기준

| 기준 | 결과 |
|---|---|
| 명령과 테스트 | `registration:preflight`, `registration:simulate`, `waitlist:promote`, `registration:release-reserved-seats`(5분마다), `reference:import registration_holds` + `RegistrationCommandsTest` |
| 매뉴얼 | 관리자 매뉴얼 20장 추가, 9·17·18장 보완, 운영 명령 레퍼런스와 스케줄 작업 표 |
| 시더, 팩토리, 데모 | 팩토리(수강), 설치 프로파일 `yust-legacy-registration`, 데모 수강신청(규칙 세트 2개, 학점 상한, 이번 학기 수강, 다음 학기 대기자, 수강신청 제한 2건) |
| `setup:install`, `setup:demo --size=small` | 빈 DB에서 성공 |
| 번역 en, ko, zh_CN | 화면, 규칙 메시지, 메일 전체 |
| 결과 보고 | 이 문서 |

## 3. 구현 내용

| 영역 | 테이블 | 화면 | 비고 |
|---|---|---|---|
| 수강 | `enrollments`, `sections.enrolled_count`, `section_seat_groups.enrolled_count` | 포털 수강신청, 관리 화면 수강 현황, 개설강좌 수강생 탭 | 과목·학점 스냅샷, 재수강 자동 판정, 부분 unique 인덱스 |
| 규칙 | `registration_rule_sets`, `registration_rules` | 수강신청 규칙(학기 규칙 준비: 복사 또는 기본값, 규칙별 매개변수) | 규칙 10개. 기간·개설 상태·정원은 항상 적용 |
| 학점 상한 | `credit_limit_policies` | 학점 상한 | 학기 종류 + 대상 조건 + 우선순위 |
| 제한과 예외 | `registration_holds`, `registration_overrides` | 수강신청 제한(설정, 해제, CSV), 규칙 예외(허용, 취소) | 레거시 `SRDENY`, `SRALLOW` |
| 대기자 | `waitlist_entries`, `sections.waitlist_capacity` | 포털 대기 신청·취소, 개설강좌 대기자 탭(지금 승격, 제외) | 큐 작업 `PromoteWaitlistJob`(강좌별 하나), 메일 알림 |
| 장바구니 | `cart_items` | 포털 장바구니(담기 때 사전 점검, 모두 신청) | |
| 기록 | `registration_attempts` | 수강신청 기록 | 모든 시도와 규칙별 결과, 처리자, 요청 ID |
| 권한 | 권한 4개 | — | 학적 담당(전부), 교무 담당(조회, 규칙), 학과 조교(조회, 조정) 역할 템플릿 |
| RLS | 새 테이블 전체 | — | 학생은 자기 것만, 직원은 개설 조직·학적 범위, 교원은 담당 강좌 수강생 |

## 4. 계획과 달라진 점

| 항목 | 계획 | 실제 | 이유 |
|---|---|---|---|
| 좌석 확보 방식 | 조건부 UPDATE, 긴 잠금 없음(10.7절) | 강좌 행 `SELECT ... FOR UPDATE`(규칙 검사 뒤 짧게) | 좌석 할당(유보석)의 대상 조건과 전환 시각을 한 UPDATE 조건으로 표현하기 어려움. 잠금은 강좌 하나에만([0010](decisions/0010-registration.md)) |
| 대기자 승격 방식 | 자동 등록 + 알림, 수락 기한 방식은 설정(17.6) | 자동 등록 + 메일 알림만 | 기본값부터 구현. 수락 기한 방식은 요청이 있을 때 |
| 멱등 키, 강좌 목록 캐시 | 10.7절 | 부분 unique 인덱스 + 사용자별 요청 제한(분당 30회) | 중복 신청은 DB가 막는다. 캐시는 Phase 13 부하 테스트 결과로 결정 |
| 직전 학기 평점에 따른 추가 학점 | 10.6절 `신규` | 미구현 | 평점은 Phase 6 |
| 감사 트리거 | 변경마다 기록 | 좌석 카운터만 바뀐 UPDATE는 기록 안 함 | 신청마다 강좌 행이 바뀌어 데이터 변경 기록이 폭증. `util.enable_audit`에 무시할 열 인수 추가 |
| 명명 규칙 | 불리언은 `is_`, `has_`, `can_` 또는 동사형 | 검사기에 `blocks_` 추가 | `blocks_registration` 등. 이미 허용된 `counts_`, `allows_`와 같은 동사형 |

## 5. 발견해 고친 문제

- **빈 매개변수 저장 실패**: 매개변수가 없는 규칙을 저장하면 PHP 빈 배열이 JSON `[]`이 되어 "JSON 객체" CHECK 제약에 걸렸다. 데모 설치에서 드러났다(테스트는 매개변수가 있는 규칙만 만들고 있었다). 빈 값은 NULL로 저장하고 관리 화면 테스트에 확인을 추가했다.
- **화면이 넘긴 강좌의 관계 미로딩**: 규칙이 강좌의 학기·버전 관계를 읽는데, 화면마다 미리 읽는 관계가 달라 엄격 모드에서 지연 로딩 오류가 났다. 규칙 엔진이 필요한 관계를 직접 불러오게 했다.
- **학점 상한 입력 검증**: 최소 학점을 비우면 "최대 학점은 최소 학점 이상" 검증이 실패해 저장할 수 없었다. 최소 학점이 있을 때만 비교한다.
- **정원 축소**: 이미 신청한 인원보다 정원을 줄이면 DB 제약 오류가 그대로 났다. 같은 행 잠금 아래에서 확인해 안내 메시지를 보여 준다.

## 6. 실행 방법

`./vendor/bin/sail artisan setup:demo --size=small` 후 (비밀번호 `demo-password`):

| 계정 | 볼 것 |
|---|---|
| `registrar@demo.test` | 관리 화면 수강신청: 수강 현황(학생 수강신청, 취소·철회), 수강신청 제한, 규칙 예외, 규칙, 학점 상한, 수강신청 기록. 개설강좌 수정 화면의 수강생·대기자 탭(다음 학기에 정원 2명, 대기 2명인 강좌가 하나 있다) |
| `student@demo.test` | 포털 수강신청: 다음 학기 장바구니(열림), 수강신청은 2일 뒤 열림(2학년 시차 오픈). 이번 학기로 바꾸면 신청 내역과 시간표 |
| `academic-affairs@demo.test` | 수강신청 규칙, 학점 상한 |

명령 예: `php artisan registration:preflight {다음 학기 코드}`, `php artisan registration:simulate {학번} {과목코드-분반}`.

## 7. CI

| 실행 | 결과 | 내용 |
|---|---|---|
| `9a06d5c` | 통과 | 품질 작업 30초(audit, Pint, Larastan, 명명, 번역), 테스트 작업 7분 37초(PostgreSQL 18 서비스, 테스트 355개) |

## 8. 남은 일

| 항목 | 시점 |
|---|---|
| 성적 칸 사용, 철회 W, 재수강 성적 반영 | Phase 6 |
| 직전 학기 평점에 따른 추가 학점 | Phase 6 이후 |
| 수강신청 제한의 성적 조회·증명서 효과 적용 | Phase 6, Phase 9 |
| 대기자 수락 기한 방식 | 요청 시 |
| 동시 접속 목표(17.7)에 따른 부하 테스트, 캐시와 멱등 키 | Phase 13 |
| 수강 보고용 `reporting` 뷰 | Phase 11 |
