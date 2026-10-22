# Phase 2 인물과 학적 결과 보고

- 작성일: 2026-10-22
- 대상: 개발팀, 프로젝트 관리자
- 관련: [이식 계획 14절 Phase 2](porting-plan.md#phase-2-인물과-학적-l), [설계 결정 0007](decisions/0007-people-accounts-and-sign-in.md)

## 1. 요약

인물, 학적, 교원, 보호자, 계정 연결, 로그인 수단(학번 로그인, 계정 잠금, 학교 통합 로그인), 학적 변동, 지도교수, 포털 화면과 13.5절 공통 완료 기준을 구현했다.

- 테스트 259개(단언 828개) 통과. Phase 1 대비 61개 추가. Larastan 레벨 6 오류 0, Pint 위반 0, 명명 검사 위반 0, 번역 누락 0, `composer audit` 통과
- 빈 DB에서 `setup:install --force --profile=korean-university`와 `setup:demo --size=small --seed=42` 성공 확인 (학적 104건, 교원 20명, 지도 배정 61건, 처리 대기 신청 3건, 보호자 34명)
- CI 결과는 7절

## 2. 완료 기준

| 완료 기준 | 결과 | 근거 |
|---|---|---|
| 역할별 조회 범위 테스트 | 충족 | `StudentRecordAccessTest`: 학적 담당(전체), 학과 조교(학과), 단과대학 조교(하위 학과 포함), 프로그램 담당, 지도교수, 학생 본인, 권한 없음 7가지 사용자에 대해 애플리케이션 범위와 DB(RLS) 결과가 같음. 범위 밖 수정은 DB에서도 0건. `PeopleAdminTest`: 학과 조교가 다른 학과 학적 상세를 열면 404 |
| 한 사람이 여러 학적을 가질 때 학적 선택과 권한이 정확 | 충족 | `StudentRecordActionsTest`: 재입학 졸업생에게 새 학적과 새 학번, 기존 학적 유지. `PortalTest`: 재입학자가 학적을 바꿔 보고, 현재 학적에서만 변동 신청 가능, 졸업 학적은 읽기 전용. RLS 하에서 학생은 자기 학적만 보임 |
| 학적 변경 감사 기록 | 충족 | 학적 등록, 변동 신청/승인/반려/취소/직접 처리, 지도교수 배정, 병합, 초대가 업무 감사에 남고, 학적 상태 변경은 트리거 데이터 변경 기록에도 남음 (`StudentRecordActionsTest`) |
| 학번 로그인 설정 3가지 값별 동작 | 충족 | `SignInMethodsTest`: 허용 안 함 / 재학생만 / 재학생과 졸업생 각각에서 재학생·졸업생 학번 로그인 결과. 개인 이메일은 항상 허용, 모든 실패에 같은 오류 |
| SSO 연결 사용자가 졸업 후 개인 이메일로 로그인 | 충족 | `SignInMethodsTest`: 학교 계정으로 로그인하던 졸업생이 개인 이메일과 비밀번호로 로그인 |

13.5절 공통 완료 기준

| 기준 | 결과 |
|---|---|
| 명령과 테스트 | `security:reset-mfa`, `security:unlock-user`, `people:find-duplicates` 추가, `reference:import`에 `students`, `instructors` 유형 + 테스트 |
| 매뉴얼 | 운영 명령 레퍼런스 갱신, 관리자 매뉴얼 12~15장 추가, 3장(2단계 인증)과 9장(CSV) 보완 |
| 시더, 팩토리, 데모 | 필수 시더(학적 변동 유형 6종), 팩토리 7개, 데모 인물/학적(`DemoPeopleSeeder`)과 포털 데모 계정 4개 |
| `setup:install`, `setup:demo --size=small` | 빈 DB에서 성공 |
| 번역 en, ko, zh_CN | 화면 전체 |
| 결과 보고 | 이 문서 |

## 3. 구현 내용

| 영역 | 테이블 | 화면 | 비고 |
|---|---|---|---|
| 인물 | `people`, `guardians` | 인물(목록, 상세, 만들기, 수정, 보호자/학적 탭, 사진) | 로마자 법적 이름 + 언어별 이름, 신분 번호 암호화와 표시 해제(보안 로그) |
| 계정 | `users.person_id`, `account_invitations`, `user_identities` | 사용자 화면에 연결된 인물 표시, 로그인 잠금 해제 | 초대 메일로 계정 발급, 인물 병합 |
| 학적 | `student_records`, `student_record_programs`, `student_number_sequences` | 학적(목록, 등록, 상세, 수정), 설정 > 학적(학번 형식) | 입학마다 새 학적과 학번, 동시 등록에도 중복 없는 학번 생성 |
| 학적 변동 | `student_status_change_types`, `student_status_changes` | 학적 변동(처리 대기 목록, 승인/반려), 학적 변동 유형, 학적 상세 직접 처리 | 직무 분리, 신청 후 상태 변경 감지 |
| 교원, 지도교수 | `instructors`, `advisor_assignments` | 교원, 학적 상세 지도교수 탭 | 주 지도교수 하나, 교체 시 이력 |
| 로그인 수단 | `authentication.*` 설정 | 설정 > 로그인 수단 | 학번 로그인 3단계, 계정 잠금, OIDC 제공자, 연결 방식 |
| 포털 | — | 내 학적(학적 선택, 변동 신청/취소), 내 정보(연락처, 보호자), 지도 학생, 보안 > 학교 통합 로그인 연결/해제 | 메뉴는 persona(학생, 졸업생, 지도교수)에 따라 표시 |
| 권한 | 권한 11개 추가 | — | 역할 템플릿(학적 담당, 학과 조교, 교무, 증명서 담당)에 반영 |
| RLS | people, guardians, student_records, student_status_changes 정책 | — | [0007](decisions/0007-people-accounts-and-sign-in.md) |

## 4. 계획과 달라진 점

| 항목 | 계획 | 실제 | 이유 |
|---|---|---|---|
| SSO 방식 | OIDC 또는 SAML | OIDC만 | 대상 IdP(Google Workspace, Microsoft Entra ID, Keycloak)가 모두 OIDC를 지원. SAML은 요청이 있을 때 추가 |
| SSO 구현 | (정하지 않음) | 자체 OIDC 클라이언트(firebase/php-jwt) | Socialite는 제공자별 드라이버가 필요하고 nonce 검증을 직접 해야 함 ([0007](decisions/0007-people-accounts-and-sign-in.md)) |
| 조교 조회 | 포털의 교수/조교 조회 | 지도교수는 포털 **지도 학생**, 조교는 관리 화면 학적 조회(조직 범위) | 조교 업무는 목록 검색, 필터, 상세가 필요해 관리 화면이 맞음. 같은 RLS 범위 적용 |
| 졸업생 포털 | 제한 권한 | 내 학적(읽기 전용), 내 정보, 보안 | 성적 조회와 증명서는 해당 Phase(6, 9)에서 추가 |
| 가져오기의 상태 값 | — | 이력 없는 자료 정정 | 최초 이관용. 실제 변동은 화면에서 이력과 함께 처리 |
| 계정 잠금 | 7.1절에 없음 | 계정 단위 잠금 추가 | 학번 로그인을 허용하면 학번이 추측 가능한 로그인 이름이 되므로, IP 속도 제한만으로는 부족 |

## 5. 발견해 고친 문제

- **RLS 배열 비교 오류**: `= ANY ((SELECT 함수()))`가 행 집합으로 해석되어 `bigint = bigint[]` 오류. 배열 캐스트로 해결하고 정책 작성 규칙에 추가.
- **보이지 않는 행과 중복 검사**: RLS 때문에 담당자가 볼 수 없는 학번을 "없다"고 판단해 unique 위반이 났다. 존재 여부만 돌려주는 SECURITY DEFINER 함수로 해결.
- **실패 후 트랜잭션 중단**: PostgreSQL은 잡은 unique 위반 뒤에도 트랜잭션 전체를 중단한다. 신청 생성을 세이브포인트로 감쌈.
- **삭제 제한 오류 코드**: `RESTRICT` 외래 키는 23503이 아니라 23001을 낸다. 사용 중 데이터 삭제 안내(`SafeDeleteAction`)가 두 코드를 모두 처리하도록 수정.
- **테스트 픽스처와 RLS**: 테스트가 RLS를 거치는 런타임 역할로 실행되어 팩토리가 행을 만들 수 없었다. 테스트에만 기본 컨텍스트(`system:test`)를 두고, 정책 테스트는 이를 끄고 실행.
- **포털 첫 화면 이름**: `users.name`이 비어 있는 인물 계정에서 환영 문구가 비던 문제. 인물 이름 표시로 수정.

## 6. 실행 방법

[README](../README.md)의 "개발 환경 준비" 후 `./vendor/bin/sail artisan setup:demo --size=small`. 포털 데모 계정(비밀번호 `demo-password`):

| 계정 | 내용 |
|---|---|
| `student@demo.test` | 재학생. 지도교수 있음 |
| `alumni@demo.test` | 졸업생 |
| `readmitted@demo.test` | 졸업 후 재입학. 학적 2개 |
| `instructor@demo.test` | 교원, 위 재학생의 지도교수 |

## 7. CI

push 후 GitHub Actions 결과를 여기에 기록한다.

## 8. 남은 일

| 항목 | 시점 |
|---|---|
| 지원자 → 학생 전환(`RegisterStudent` 사용) | Phase 3 |
| 복수전공/부전공(`student_record_programs`) 관리 화면과 신청 | 테이블과 모델만 있음. 졸업 사정(Phase 8) 전 |
| SAML 제공자 | 요청 시 |
| 학적, 인물 보고용 `reporting` 뷰 | Phase 11 |
| 관리자 매뉴얼 화면 캡처 | Phase 13 |
