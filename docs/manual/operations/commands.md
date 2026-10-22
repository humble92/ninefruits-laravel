# Artisan 명령 레퍼런스

- 대상: IT 운영자
- 기준: 이 저장소에 등록된 애플리케이션 명령 전체. 명령을 추가하면 이 문서에도 추가해야 CI가 통과한다(`tests/Feature/Tooling/CommandDocumentationTest.php`).

## 공통 사항

모든 명령은 `php artisan {명령}`으로 실행한다. 개발 환경에서는 `./vendor/bin/sail artisan {명령}`.

| 항목 | 규칙 |
|---|---|
| 감사 기록 | 데이터를 바꾸는 명령(아래 "감사: 기록")은 실행할 때마다 업무 감사(로그 `command`)에 명령, 인수, 옵션, OS 사용자, 종료 코드, 결과를 남긴다. 관리 화면 **감사 > 업무 감사 기록**에서 확인한다 |
| `--actor=<이메일>` | 감사 기록 명령에 공통으로 있다. 책임자 관리자 계정을 기록한다. 없는 이메일이면 실행하지 않는다 |
| `--dry-run` | 변경 없이 결과만 출력한다. 되돌리기 어려운 작업에 있다 |
| `--force` | 확인 질문 없이 실행한다(스케줄, 배포 스크립트용) |
| `--reason=` | 사유가 필요한 명령에 있다(Phase 2부터) |
| 종료 코드 | 0 성공, 1 실패 또는 취소, 2 점검 명령이 문제를 발견함 |
| DB 컨텍스트 | 실행 중에는 DB 세션의 수행자가 `system:command:{명령}`으로 설정되어 데이터 변경 기록(트리거)에 남는다 |

## 설치와 데모

### `setup:install`

- 용도: 신규 설치와 업데이트. 마이그레이션, 필수 데이터, 기관 기본 설정, 설치 프로파일, 최초 관리자를 차례로 처리한다. 다시 실행해도 안전하다(이미 있는 데이터와 관리자가 바꾼 값은 유지).
- 실행 시점: 최초 설치, 그리고 배포할 때마다.
- 옵션: `--institution-name=`, `--locale=`(기본 언어), `--timezone=`(예: `Asia/Seoul`), `--profile=*`(설치 프로파일 키), `--admin-email=`, `--admin-name=`, `--force`(질문 없이 실행. 관리자 비밀번호는 표준 입력으로 받음).
- 감사: 기록
- 예시

  ```bash
  php artisan setup:install
  echo "$ADMIN_PASSWORD" | php artisan setup:install --force --locale=ko --timezone=Asia/Seoul \
      --institution-name="데모대학교" --admin-email=admin@example.ac.kr --admin-name="관리자"
  ```

- 출력 해석: 단계별 `DONE`/`FAIL`. `Administrator exists`는 시스템 관리자 할당이 이미 있어 생성을 건너뛴 것이다.
- 실패 시: 키가 없다는 오류면 `key:generate`, `pii:generate-key`로 만든 값을 환경 설정에 넣는다. 마이그레이션 실패는 DB 역할 준비(설치 매뉴얼)를 확인한다.

### `setup:demo`

- 용도: 팩토리로 가상 대학 데이터를 만든다. 역할별 고정 데모 계정(`registrar@demo.test` 등)을 출력한다.
- 실행 시점: 개발, 교육, 시연 환경. **운영 환경(`APP_ENV=production`)에서는 `--force`를 줘도 실행되지 않는다.**
- 옵션: `--size=small|medium|large`(학생 약 100/2,000/20,000명), `--seed=`(같은 값이면 같은 데이터), `--force`.
- 감사: 기록
- 예시: `php artisan setup:demo --size=small --seed=42`
- 실패 시: 필수 데이터가 없으면 데모 계정의 역할이 없어 건너뛴다. 먼저 `setup:install`을 실행한다.

## 기준정보와 학기

### `reference:import`

- 용도: 기준정보 CSV를 가져온다. 관리 화면의 **CSV 가져오기**와 같은 가져오기 규칙을 쓴다(코드로 기존 행을 찾아 갱신, 빈 번역 칸은 기존 값 유지, 실패한 행만 따로 기록).
- 인수: `{type}` `org_units`, `programs`, `buildings`, `rooms`, `lookup_values` / `{file}` 머리글 행이 있는 UTF-8 CSV.
- 옵션: `--actor=`(필수. 가져오기 기록과 완료 알림이 이 계정으로 간다), `--chunk=100`(큐 작업당 행 수), `--dry-run`(머리글 검사와 행 수만).
- 감사: 기록 (유형, 파일 이름, 행 수, 실패 행 수)
- 열 형식: [관리자 매뉴얼 9장](../admin/09-csv-import.md).
- 종료 코드: 큐가 동기 실행이면 결과를 바로 출력하고 실패 행이 있으면 2. 비동기(Horizon)이면 접수만 하고 0. 결과는 `--actor` 계정의 관리 화면 알림에서 실패 행 CSV로 받는다.
- 예시: `php artisan reference:import org_units storage/app/org-units.csv --actor=admin@example.ac.kr --dry-run`

### `term:inspect`

- 용도: 지정 시각에 수업 중인 학기와 열려 있는 업무 기간(종류, 학기, 대상 조건, 종료 시각)을 보여 준다. 학기와 업무 기간 설정을 점검하거나 "왜 수강신청이 안 열렸는가" 같은 문의에 쓴다.
- 옵션: `--at=`(기본 현재. 예: `"2027-02-15 09:00"`. 시간대를 붙이지 않으면 서버 시간대(UTC)로 해석한다. `"2027-02-15 09:00 Asia/Seoul"`처럼 지정할 수 있다).
- 감사: 기록 안 함
- 출력 해석: 날짜 판단은 기관 시간대 기준이다. 계획(planned)이나 보관(archived) 상태 학기의 업무 기간은 열리지 않는다.

## 보안

### `security:create-administrator`

- 용도: 시스템 관리자 역할을 가진 계정을 만든다. 보통 `setup:install`이 호출한다.
- 옵션: `--email=`, `--name=`, `--password-stdin`(표준 입력으로 비밀번호. 비대화식 실행 시 필수).
- 감사: 기록 (생성한 사용자 ID)
- 예시: `php artisan security:create-administrator --email=admin@example.ac.kr --name="관리자"`
- 실패 시: `system_administrator role is missing`이면 `setup:install` 또는 `db:seed`를 먼저 실행한다. 비밀번호는 12자 이상이어야 한다.

### `security:sync-permissions`

- 용도: 코드의 권한 카탈로그와 DB 권한 목록을 맞춘다. 새 권한은 만들고, 카탈로그에서 빠진 권한은 삭제한다(모든 역할에서 제거됨). 시스템 역할은 항상 모든 권한을 받는다.
- 실행 시점: 배포 후(`setup:install`이 필수 데이터 단계에서 함께 처리한다).
- 옵션: `--dry-run`(생성/삭제 목록만 출력), `--force`(삭제 확인 생략).
- 감사: 기록 (생성/삭제한 권한)
- 출력 해석: `create`, `delete` 줄. 삭제가 있으면 확인 질문이 나온다.
- 실패 시: 확인 질문에 "아니요"를 고르면 종료 코드 1로 아무것도 바꾸지 않는다.

## 개인정보 암호화 키

### `pii:generate-key`

- 용도: `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`에 넣을 새 키를 출력한다. 아무것도 저장하지 않는다.
- 감사: 기록 안 함
- 주의: `PII_HASH_KEY`를 바꾸면 모든 검색 해시가 무효가 된다. 키 교체 절차는 [security-keys.md](security-keys.md).

### `pii:reencrypt`

- 용도: 키 교체 후 이전 키로 암호화된 개인정보를 현재 키로 다시 암호화한다. 큐 배치로 실행한다.
- 인수: 모델 클래스(예: `App\\Models\\Person\\Person`, Phase 2부터).
- 옵션: `--chunk=500`, `--dry-run`(이전 키를 쓰는 레코드 수만 셈), `--force`.
- 감사: 기록 (배치 ID, 작업 수 / 미리보기 건수)
- 확인: Horizon(`/horizon`)에서 배치 진행률을 본다. 끝난 뒤 `--dry-run` 결과가 0이면 이전 키를 `PII_PREVIOUS_KEYS`에서 뺀다.

## 다국어

### `i18n:missing`

- 용도: 기준 언어(en) 대비 언어별 누락 키와 더 이상 쓰지 않는 키를 보여 준다.
- 인수/옵션: `{locale?}`(한 언어만), `--strict`(누락이 있으면 종료 코드 2. CI에서 사용).
- 감사: 기록 안 함

### `i18n:export`

- 용도: 번역가에게 줄 JSON 파일을 만든다. 형식: `{"키": {"source": "영문", "translation": "번역"}}`.
- 인수/옵션: `{locale} {path}`, `--missing`(누락 키만).
- 감사: 기록 안 함
- 예시: `php artisan i18n:export ko storage/app/ko.json --missing`

### `i18n:import`

- 용도: 번역가가 채운 JSON을 `lang/{locale}`에 반영한다. 기준 언어에 있는 키만 가져오고 빈 번역은 무시한다.
- 인수/옵션: `{locale} {path}`, `--dry-run`(쓸 키 목록만 출력).
- 감사: 기록
- 주의: 번역 파일은 저장소에서 관리한다. 가져온 뒤 변경 내용을 커밋한다.

## 점검

### `naming:check`

- 용도: 마이그레이션의 테이블/컬럼 이름을 [명명 규칙](../../conventions/naming.md)으로 검사한다.
- 옵션: `--path=*`(검사할 디렉토리, 기본 `database/migrations`).
- 감사: 기록 안 함
- 종료 코드: 문제가 있으면 2.

### `pdf:sample`

- 용도: 한국어, 중국어, 영어가 섞인 표를 PDF로 렌더링해 글꼴과 PDF 서비스(Gotenberg) 연결을 점검한다.
- 인수: `{path=pdf-sample.pdf}` (로컬 디스크 `storage/app/private` 기준).
- 감사: 기록 안 함
- 실패 시: 연결 오류면 `GOTENBERG_URL`과 Gotenberg 컨테이너 상태를 확인한다.
