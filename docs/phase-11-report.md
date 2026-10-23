# Phase 11 졸업 사정과 보고서 결과 보고

- 작성일: 2026-10-23
- 대상: 개발팀, 프로젝트 관리자
- 관련: [이식 계획 14절 Phase 11](porting-plan.md#phase-11-졸업-사정과-보고서-m), [설계 결정 0016](decisions/0016-graduation-and-reporting.md), [관리자 매뉴얼 26·27장](manual/admin/README.md)

## 1. 요약

교육과정 연도별 졸업 요건 세트에 대한 **요건 판정**(교과 구분 하위 포함, 대체과목 인정, 지정 과목 선택, 시험 합격, 총 학점, 최저 평점, 요건 면제), 점검 스냅샷 저장(개별·일괄·명령), 저장된 최신 점검에 근거한 **졸업 처리**(학적 변동 + 학위번호 발급), `reporting` 스키마의 `security_invoker` 뷰 8개와 **표준 보고서** 7종(화면 + CSV)을 구현했다. 레거시는 졸업사정표를 찍기만 하고 사람이 서명으로 판정했으며, 졸업 처리는 요건 점검 없이 학적을 다른 테이블로 옮기는 것이었다.

- 테스트 449개(단언 2,309개) 통과. Phase 10 대비 4개 추가(졸업·보고서 4). 전체 실행 4분 48초. Larastan 레벨 6 오류 0, Pint 위반 0, 명명 검사 위반 0, 번역 누락 0
- 빈 DB에서 `migrate:fresh` → `setup:install --force --profile=korean-university --grade-scale=4.5`, `setup:demo --size=small --seed=42` 성공(데모 약 30초, 졸업 점검 시더 1초).
  - 데모: 이번 학기 3·4학년 32명의 점검 저장(충족 0명: 데모 수강 이력이 요건에 못 미침), reporting 뷰 인원 합계 104명
- CI(GitHub Actions): 7절

## 2. 완료 기준

계획서 Phase 11 항목

| 항목 | 결과 | 근거 |
|---|---|---|
| 교육과정 연도와 과목 버전 기준 졸업 요건 판정 | 충족 | `DegreeAudit::evaluate`: 학생의 `catalog_year`에 맞는 요건 세트, 수강 스냅샷의 교과 구분(하위 구분 포함), 수강 학기 기준 대체과목, 지정 과목 선택, 시험 합격, 총 학점, 최저 평점, 면제(`GraduationTest`) |
| 졸업 처리(학적 상태, 학위번호, 졸업생 persona) | 충족 | 최신 저장 점검이 충족이어야 처리. 학적 변동 `graduation` 기록 → 상태 `graduated`·졸업일, 학위번호 `{YYYY}-{SEQ:5}` 원자적 발급, 감사. 졸업생 persona는 기존 감지기가 적용 |
| 표준 보고서 | 충족 | 학생 명단, 인원 현황, 학기 성적 요약, 졸업 예정자, 학적 변동, 강좌별 수강 인원, 미수강 학생. 화면 500행 + CSV(감사) |
| `reporting` 뷰(다국어, RLS 적용) | 충족 | 뷰 8개, `security_invoker`, `util.i18n` 번역 칼럼. 학생 컨텍스트로 조회하면 자기 행만(`GraduationTest`) |

13.5절 공통 완료 기준

| 기준 | 결과 |
|---|---|
| 명령과 테스트 | `degree-audit:run`(학기·학생·학년 옵션) + `GraduationTest` |
| 매뉴얼 | 관리자 매뉴얼 26·27장 추가, 목차, 운영 명령 레퍼런스 |
| 시더, 팩토리, 데모 | 역할 템플릿 권한 3개. 데모: 이번 학기 3·4학년 점검 저장 |
| `setup:install`, `setup:demo --size=small` | 빈 DB에서 성공 |
| 번역 en, ko, zh_CN | 화면, 판정 문구, 보고서 이름·열 |
| 결과 보고 | 이 문서 |

## 3. 구현 내용

- 데이터: `degree_audits`(스냅샷, 요건별 결과 jsonb), `degree_requirement_waivers`(학적 × 요건 unique, 감사), `degree_number_sequences`. 설정 `GraduationSettings`(학위번호 형식, 최저 누계 평점). RLS: 점검·면제는 학적 조회 범위, 쓰기는 `graduation.audit`/`graduation.process`.
- 업무 로직(`app/Domain/Graduation`): `DegreeAudit`(판정), `RunDegreeAudits`(저장: 개별·전체), `WaiveRequirement`, `GraduateStudents`(개별·일괄, 학생별 트랜잭션), `DegreeNumberGenerator`.
- 보고서(`app/Domain/Reporting`): `ReportCatalog`(보고서 정의 = 뷰 + 매개변수 + 열), Filament **보고서** 페이지, CSV 라우트.
- 화면: **졸업 > 졸업 요건 점검**(최신 점검 목록, 점검 실행, 열기, 다시 점검, 일괄 **졸업 처리**), 학적 보기의 **졸업 요건 점검**(즉시 판정 + 저장), 포털 **졸업 요건**(즉시 판정).
- 권한: `graduation.audit`, `graduation.process`, `reports.view`.

## 4. 레거시와의 차이

| 레거시 | 신규 | 이유 |
|---|---|---|
| 졸업사정표 인쇄, 서명으로 판정 | 요건별 자동 판정, 스냅샷 저장 | 판정 근거 보존 |
| 코드 Z 입력, 점검 없음 | 최신 점검 충족 필수 | 요건 미달 졸업 방지 |
| 학위번호 미사용, 졸업증번호 수기 | 형식 설정 + 원자적 발급 | 유일성 |
| 졸업생을 별도 테이블로 이동 | 상태 변경만 | 이력 유지, 계정 유지 |
| 보고서 메뉴 22개 중 다수 사망 | 표준 보고서 7종 + 뷰 | BI 연계 |

## 5. 하지 않은 것, 남은 일

- 수료, 졸업유예, 조기졸업, 우등졸업, 학위 종류.
- 졸업 심사 결재 워크플로, PDF 졸업사정표.
- 복수전공·부전공 이수 인정 표기(학위 표기)와 졸업증명서 반영.

## 6. 검토 요청 사항

1. 최저 누계 평점 기본값(현재 없음). 2.0을 기본으로 둘지.
2. 학위번호 기본 형식 `{YYYY}-{SEQ:5}`. 과정 코드를 넣을지(`{PROGRAM}` 토큰 지원).
3. 졸업 처리 조건을 "주전공 충족"으로 두었다. 복수전공 미충족 시 경고만 표시한다.

## 7. CI 결과

커밋 `b09b663`, GitHub Actions 실행 39987147514: 성공.

| 작업 | 결과 | 소요 |
|---|---|---|
| Style, static analysis, conventions | 성공 | 41초 |
| Tests (PostgreSQL 18) | 성공 | 11분 41초 |
