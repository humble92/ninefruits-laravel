# Phase 8 증명서 결과 보고

- 작성일: 2026-10-23
- 대상: 개발팀, 프로젝트 관리자
- 관련: [이식 계획 14절 Phase 8](porting-plan.md#phase-8-증명서-m), [설계 결정 0013](decisions/0013-certificates.md), [관리자 매뉴얼 23장](manual/admin/23-certificates.md)

## 1. 요약

증명서 종류와 언어별 서식, 직원 발급(학적 화면), 원자적 발급번호, PDF 보관과 체크섬, 발급 대장(다시 만들기, 취소), 확인 코드·QR과 공개 진위 확인 페이지, 학생 포털 다운로드, 증명서 발급 제한(holds) 적용을 구현했다. 레거시는 화면에서 바로 인쇄하고 번호만 대장에 남겼으며, 진위 확인 수단이 없었다.

- 테스트 431개(단언 1,986개) 통과. Phase 7 대비 6개 추가(증명서 6). 전체 실행 4분 00초. Larastan 레벨 6 오류 0, Pint 위반 0, 명명 검사 위반 0, 번역 누락 0
- 빈 DB에서 `migrate:fresh` → `setup:install --force --profile=korean-university --grade-scale=4.5`, `setup:demo --size=small --seed=42` 성공(데모 약 70초, 증명서 시더 1.4초).
  - 증명서 종류 3개(재학, 졸업, 성적), 언어별 서식 9개
  - 데모 발급 7건(취소 1건 포함), PDF 7개
- CI(GitHub Actions): 7절

## 2. 완료 기준

계획서 Phase 8 항목

| 항목 | 결과 | 근거 |
|---|---|---|
| 종류 | 충족 | 코드, 이름, 대상(모든 학적/재학생/졸업생), 성적 포함, 사진 포함. 대상이 맞지 않으면 발급 거부(`CertificateTest`) |
| 언어별 서식 | 충족 | 종류 × 언어의 제목·본문·하단·발급자, 17개 치환 항목. 서식 없는 언어는 거부 |
| 발급 대장 | 충족 | 발급자, 용도, 매수, 상태, 체크섬. 화면에서 수정·삭제 불가. 취소는 사유 필수, 행과 파일 유지 |
| 일련번호 | 충족 | 설정 형식 `{TYPE}-{YYYY}-{SEQ:6}`, 접두어별 원자적 증가(`INSERT … ON CONFLICT … RETURNING`). 종류별로 1부터(`CertificateTest`) |
| 진위 확인 코드/QR과 공개 확인 페이지 | 충족 | 20자 무작위 코드, QR(SVG) 인쇄. `/verify/{code}` 로그인 없이, 분당 30회 제한, 성명·학번 마스킹. 없는 코드·취소·기간 만료 구분 |
| PDF 보관 | 충족 | 발급 시 Gotenberg로 생성해 미디어 컬렉션에 저장, SHA-256 체크섬. 다운로드는 정책(발급 권한자 또는 명의자) |

추가로 적용한 결정

- 증명서 발급 제한(`registration_holds.blocks_certificates`, 2026-10-22 결정)이 적용 중이면 발급을 거부한다.
- 발급은 직원만 한다(계획서 17.5). 학생은 포털 **증명서**에서 내려받는다.

13.5절 공통 완료 기준

| 기준 | 결과 |
|---|---|
| 명령과 테스트 | `certificate:verify`(종료 코드 2), `certificate:render`(감사, `--reason` 필수) + `CertificateTest` |
| 매뉴얼 | 관리자 매뉴얼 23장 추가, 20·21장과 목차 보완, 운영 명령 레퍼런스 |
| 시더, 팩토리, 데모 | 프로파일: 종류 3개와 서식 9개(관리자 수정 유지). 데모: 재학생·졸업생 발급, 성적증명서 1건, 취소 1건 |
| `setup:install`, `setup:demo --size=small` | 빈 DB에서 성공 |
| 번역 en, ko, zh_CN | 화면, PDF, 확인 페이지, 서식 전체 |
| 결과 보고 | 이 문서 |

## 3. 구현 내용

### 데이터

- `certificate_types`, `certificate_templates`(종류 × 언어 unique), `certificate_number_sequences`(접두어, 마지막 값), `certificate_issues`(발급번호 unique, 확인 코드 unique, 상태/취소 CHECK). 감사 트리거 적용.
- RLS(`database/sql/policies/certificates.sql`): 대장은 보이는 학적의 것만 조회, 쓰기는 `certificates.issue`. 종류·서식은 설정 데이터(RLS 없음).
- 설정 `CertificateSettings`: 발급번호 형식, 진위 확인 유효 기간(기본 0 = 항상), 언어별 발급자.

### 업무 로직 (`app/Domain/Certificate`)

- `IssueCertificate::handle` 권한·범위, 종류 사용 여부, 대상, 발급 제한 확인 → 대장 행 생성(번호, 코드) → PDF 생성·보관·체크섬 → 감사 `certificate.issued`. `rerender`, `revoke`.
- `CertificateSerialGenerator`: 형식 토큰 `{TYPE}`, `{YYYY}`, `{YY}`, `{SEQ:n}`.
- `CertificateRenderer`: 서식 치환, 인적 사항 표(졸업생은 졸업일, 아니면 학적 상태·학년), 성적 포함 종류는 `GradeReport`의 학기별 확정 성적과 누계, 사진, QR. 날짜는 서식 언어 형식.
- `CertificateVerification::check`: 시스템 컨텍스트에서 코드 조회, 상태·유효 기간 판정, 마스킹.

### 화면

- 관리: **증명서 > 발급 대장**(필터, PDF, 확인 페이지, 다시 만들기, 취소), **증명서 종류**(+ 서식 관계 관리자, 치환 항목 도움말), **설정 > 증명서**. 학적 보기 화면의 **증명서 발급** 헤더 액션(완료 후 PDF 새 탭).
- 공개: `/verify/{code?}` 확인 페이지.
- 포털: **증명서** 목록과 다운로드.
- PDF: `pdf.certificate` 뷰(머리글, 발급번호, 제목, 인적 사항 표, 문단, 성적 표, 발급자, 확인 URL과 QR).

### 권한과 역할

- `certificates.issue`(증명서 담당, 학적 담당), `certificates.manage`(학적 담당, 교무 담당).

## 4. 레거시와의 차이

| 레거시 | 신규 | 이유 |
|---|---|---|
| 종류·언어별 PDFlib 스크립트 | DB 서식 + 하나의 레이아웃 | 기관이 문구를 직접 고친다 |
| `MAX+1` 번호, 잠금 없음 | 접두어별 원자적 시퀀스 | 동시 발급 시 중복 방지 |
| 대장에 번호만 | 담당자, 용도, 매수, 상태, 체크섬, 파일 | 발급 이력과 재검증 |
| 진위 확인 없음 | 확인 코드·QR, 공개 페이지 | 제출처가 직접 확인 |
| 수강거부와 무관 | 증명서 제한 적용 | 행정 처리 수단 |
| 수수료 금액 기록 | 없음 | 결제 연동 없이 금액만 적는 것은 의미가 없음 |

## 5. 하지 않은 것, 남은 일

- 학생 온라인 신청·결제(계획 17.5대로 제외), 수수료 기록.
- 전자서명(PAdES)·타임스탬프.
- 레거시 대장 이관(번호 체계가 달라 별도 결정 필요).
- Phase 11 졸업 처리에서 학위번호가 들어오면 졸업증명서 `{degree_number}`가 채워진다.

## 6. 검토 요청 사항

1. 발급번호 기본 형식 `{TYPE}-{YYYY}-{SEQ:6}`(종류·연도별 1부터). 레거시 YYMM+순번을 원하면 `{YY}{MM}` 토큰을 추가해야 한다(현재 월 토큰 없음).
2. 진위 확인 페이지의 마스킹 수준(성명 첫·마지막 글자, 학번 앞 4자리).
3. 진위 확인 유효 기간 기본값 0(항상). 1년 등으로 제한할지.

## 7. CI 결과

커밋 `3c5d42c`, GitHub Actions 실행 39919417111: 성공.

| 작업 | 결과 | 소요 |
|---|---|---|
| Style, static analysis, conventions | 성공 | 49초 |
| Tests (PostgreSQL 18) | 성공 | 11분 11초 |

테스트 작업이 Phase 7(10분 26초)보다 45초 늘었다. 증명서 테스트 6개가 Gotenberg로 PDF를 실제 생성한다.
