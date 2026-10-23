# Phase 9 어학/종합시험 결과 보고

- 작성일: 2026-10-23
- 대상: 개발팀, 프로젝트 관리자
- 관련: [이식 계획 10.8절, 14절 Phase 9](porting-plan.md#phase-9-어학종합시험-sm), [설계 결정 0014](decisions/0014-assessments.md), [관리자 매뉴얼 24장](../docs/manual/ko/admin/24-assessments.md)

## 1. 요약

레거시의 세 가지 시험 구조(CET 어학, SSCORE_ADD 종합시험, COMEXAM 전산)를 하나로 일반화했다. 시험 종류가 영역과 판정 방식을 정의하고, 회차(접수 기간, 정원, 결과 공개), 접수(학생 포털·직원), 결과(영역 점수, 자동 합격 판정, CSV 가져오기), 외부 성적(증빙 파일), 졸업 요건 종류 **시험 합격**을 구현했다.

- 테스트 437개(단언 2,086개) 통과. Phase 8 대비 6개 추가(시험 6). 전체 실행 4분 26초(시험 테스트는 PDF를 만들지 않아 6초). Larastan 레벨 6 오류 0, Pint 위반 0, 명명 검사 위반 0, 번역 누락 0
- 빈 DB에서 `migrate:fresh` → `setup:install --force --profile=korean-university --grade-scale=4.5`, `setup:demo --size=small --seed=42` 성공(데모 약 32초, 시험 시더 5초).
  - 시험 종류 4개(영어종합시험 4영역, 전산종합시험, 졸업논문, TOPIK 외부)
  - 데모: 회차 4개(영어종합시험 지난 회차 2개와 접수 중 1개, 전산종합시험 1개), 접수 61건(결시 2), 결과 57건(외부 TOPIK 4, 합격 42), 교육과정 5개에 시험 합격 요건
- CI(GitHub Actions): 7절

## 2. 완료 기준

계획서 Phase 9 항목(10.8절 구조)

| 항목 | 결과 | 근거 |
|---|---|---|
| `assessment_types` 합격 기준(총점, 영역별 최저) | 충족 | 판정 방식 점수/합격·불합격, 합격 점수(회차 override), 영역 최저점. 판정 테스트(`AssessmentTest`) |
| `assessment_components` | 충족 | 종류별 영역(코드, 만점, 최저점). 종류 수정 화면의 관계 관리자 |
| `assessment_sessions`, `assessment_registrations` | 충족 | 회차(접수 기간, 정원, 장소, 결과 공개), 학생 접수·취소(기간·정원·중복·학적 상태 확인), 직원 접수, 결시 |
| `assessment_results`, `assessment_component_scores`, 외부 성적 증빙 | 충족 | 회차 결과 1:1, 영역 점수, 재입력 교체, 외부 성적(등급, 증서 번호, 증빙 파일 PDF/JPG/PNG) |
| `curriculum_requirements`에서 시험 합격 참조 | 충족 | 요건 종류 `assessment_pass` + 시험 종류, CHECK 제약, `AssessmentPassCheck`, 포털 요건 표시 |

13.5절 공통 완료 기준

| 기준 | 결과 |
|---|---|
| 명령과 테스트 | `assessment:import-results`(dry-run, 오류 행 종료 코드 2), `assessment:publish-results` + `AssessmentTest` |
| 매뉴얼 | 관리자 매뉴얼 24장 추가, 17장(요건 종류)과 목차 보완, 운영 명령 레퍼런스 |
| 시더, 팩토리, 데모 | 프로파일: 종류 4개와 영역. 데모: 지난 회차 2개(결과 공개, 결시 포함), 전산종합시험 회차, 접수 중인 회차, TOPIK 외부 성적, 교육과정별 시험 합격 요건 |
| `setup:install`, `setup:demo --size=small` | 빈 DB에서 성공 |
| 번역 en, ko, zh_CN | 화면, 포털, 오류 문구, 프로파일 종류·영역 |
| 결과 보고 | 이 문서 |

## 3. 구현 내용

### 데이터

- `assessment_types`, `assessment_components`(종류 × 코드 unique), `assessment_sessions`(종류 × 시험일 unique), `assessment_registrations`(회차 × 학적 unique, 상태 CHECK), `assessment_results`(접수 1:1, 출처/회차 CHECK), `assessment_component_scores`. `curriculum_requirements.assessment_type_id` 추가와 종류별 CHECK 재정의. 감사 트리거 6개 테이블.
- RLS(`database/sql/policies/assessments.sql`): 접수·결과는 보이는 학적의 것만, 쓰기는 `assessments.record`. 학생은 자기 접수와 **공개된 회차의 결과**, 외부 성적만 읽는다. 종류·영역·회차는 설정 데이터.

### 업무 로직 (`app/Domain/Assessment`)

- `RegisterForAssessment`: `register`(학생: 접수 기간·정원·중복·재학 상태 확인 후 시스템 컨텍스트로 기록 / 직원: 기간 무관), `cancel`(결과 있으면 불가, 취소 행 유지·재접수 시 되살림), `markAbsent`.
- `RecordAssessmentResult`: `forRegistration`(영역 점수 검증 → 총점 → 합격 판정: 총점 ≥ 합격 점수 ∧ 모든 영역 ≥ 최저점, 합격/불합격 종류는 담당자 판정), `external`(증빙 파일), `remove`(사유), `publish`.
- `AssessmentResultImporter`: CSV(학번, 영역 코드 열 대소문자 무관, total, passed, absent, note). 화면 액션과 명령이 공유.
- `AssessmentPassCheck`: 종류 합격 여부, 학생 교육과정의 시험 요건과 충족 상태.

### 화면

- 관리: **어학/종합시험 > 시험 회차**(목록·생성·수정, 응시자 관계 관리자: 학생 접수, 결과 입력(영역별 동적 폼), 결시, 접수 취소; 결과 공개, CSV 가져오기), **시험 결과**(대장, 외부 성적 등록, 증빙, 삭제), **시험 종류**(+ 영역). **졸업 요건** 편집에 요건 종류 **시험 합격**.
- 포털: **어학/종합시험**(교육과정 요건과 합격 여부, 접수 중인 회차 접수·취소, 내 접수, 내 결과와 증빙).
- 증빙 다운로드 라우트(정책: 기록 권한자 또는 본인).

### 권한과 역할

- `assessments.record`, `assessments.manage`(학적 담당, 교무 담당).

## 4. 레거시와의 차이

| 레거시 | 신규 | 이유 |
|---|---|---|
| 시험마다 다른 테이블, 고정 영역 칼럼 | 종류가 영역과 판정을 정의 | 새 시험을 데이터로 추가 |
| 합계 ≥ 커트라인만 | 합격 점수 + 영역 최저점, 판정 저장 | CET4처럼 영역 조건이 있는 시험 |
| 최신 회차만 조회(`V_LCET`) | 모든 회차 보존, 회차 삭제 제한 | 이력 유지, 고아 행 방지 |
| 접수 기간·정원 없음 | 회차별 기간·정원, 포털 접수 | 접수 관리 |
| 입력 즉시 학생에게 노출 | 결과 공개 후 노출(RLS) | 정정 전 노출 방지 |
| 외부 시험 기록 없음 | 외부 성적 + 증빙 | 졸업 요건 인정 |
| 사람이 졸업 사정표를 보고 판단 | 요건 종류 시험 합격 | Phase 11 자동 판정 |

## 4a. 검토 후 변경 (2026-10-23)

- **공개 뒤 잠금과 정정 요청**: 결과 공개 뒤에는 결과를 직접 고치거나 지울 수 없다. `assessment_result_corrections`(요청·승인·반려·취소, 본인 결정 불가, 승인 시 반영)와 관리 화면 **결과 정정 요청**을 추가했다. 매뉴얼 24장, 0014 참조.
- **지난 회차 접수 제한**: 시험일이 지난 회차에는 아무도 접수할 수 없다. CSV의 미접수 학생 행은 건너뛰고 보고한다(접수 없는 응시는 없다는 원칙).
- **합격 점수 변경과 재판정**: 회차 합격 점수는 공개 전에만 바꿀 수 있고, 바꾸면 입력된 결과를 모두 재판정한다(감사에 바뀐 학번). 공개 뒤 차단.
- **영역 최저점 경계 테스트** 추가(최저점과 같으면 합격, 1점 아래면 총점이 높아도 불합격).

## 5. 하지 않은 것, 남은 일

- 응시료, 좌석 배정, 고사장 분반.
- 학생의 외부 성적 직접 제출·승인 흐름(직원 등록만).
- 결과 공개 알림 메일.
- 외부 시험 등급별 합격 기준 표(등급은 문자열, 합격은 담당자 판정).
- 레거시 이관 매핑(0014 참조).
- Phase 11 졸업 사정에서 `AssessmentPassCheck` 호출.

## 6. 검토 요청 사항

1. 학생 접수를 **재학·휴학** 학생으로 제한했다. 졸업 예정자(졸업 처리 뒤 시험)는 직원 접수로만 가능하다.
2. ~~결과 재입력을 공개 뒤에도 허용~~ → 잠금과 정정 요청으로 바꿈(4a절).
3. 외부 성적 삭제를 허용했다(사유, 감사). 삭제 대신 "무효" 상태로 남길지.

## 7. CI 결과

커밋 `c824918`, GitHub Actions 실행 39942586009: 성공.

| 작업 | 결과 | 소요 |
|---|---|---|
| Style, static analysis, conventions | 성공 | 55초 |
| Tests (PostgreSQL 18) | 성공 | 7분 30초 |

테스트 작업이 Phase 8(11분 11초)보다 3분 41초 짧다. 추가된 시험 테스트는 PDF를 만들지 않고, 러너 상태 차이가 큰 것으로 보인다.
