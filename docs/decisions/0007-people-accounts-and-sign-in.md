# 0007. 인물, 학적, 계정과 로그인 수단

- 상태: 채택 (2026-10-22, Phase 2)
- 관련: [이식 계획 7.1절, 10.1절](../porting-plan.md), [0003 행 수준 보안](0003-row-level-security.md), [0005 권한 모델](0005-authorization-model.md)

## 인물

- `people` 한 행이 한 사람이다. 학생, 졸업생, 교원, 지원자(Phase 3)가 모두 인물을 공유한다.
- 법적 이름은 로마자(`family_name`, `given_name`)로 두고, 언어별 표기는 `name_variants` jsonb(`{"ko": "김민수"}`)에 둔다. 표시 이름은 현재 언어 표기 → 로마자 순. 검색은 `util.i18n_search_text()`로 둘 다 찾는다. 이름은 translatable 속성이 아니다(로마자 이름은 번역이 아니라 법적 원본이므로).
- 주민등록번호, 여권번호는 `HasEncryptedPii`(Phase 0)로 암호화하고 HMAC 해시로 정확 일치 검색만 한다.
- 사진은 medialibrary(`MEDIA_DISK`, 기본 로컬 비공개 디스크). 공개 URL 없이 `people.photo` 라우트가 권한을 확인한 뒤 내려 준다.
- 중복은 병합(`MergePeople`)으로 고친다. 없앨 인물은 지우지 않고 `merged_into_person_id`로 남긴다(외부 참조와 감사 기록이 계속 풀리도록). 둘 다 계정이 있으면 없앨 쪽 계정을 비활성화하고 SSO 연결을 옮긴다.

## 계정

- `users.person_id`(unique, nullable). 계정의 표시 이름은 인물 이름이고, `users.name`(nullable)은 인물 없는 계정(서비스 계정 등)에만 쓴다.
- 계정 없는 인물(CSV로 가져온 학생, 교원)은 초대(`InvitePerson`)로 계정을 만든다. 토큰은 SHA-256 해시만 저장하고, 새 초대가 이전 초대를 무효로 한다. 유효 기간은 설정(`student_records.invitation_valid_days`).

## 학적

- `student_records` 한 행이 입학 1건이다. 재입학은 같은 인물에 새 학적(새 학번)을 만든다. 포털은 세션에 고른 학적(`CurrentStudentRecord`)을 기억한다.
- 학번 생성(`StudentNumberGenerator`): 설정의 형식(`{YYYY}{SEQ:5}` 등)에서 `{SEQ}` 앞부분별 일련번호를 `student_number_sequences`에 한 번의 upsert(`... ON CONFLICT DO UPDATE ... RETURNING`)로 증가시킨다. 동시 등록에도 중복이 없고, 가져온 학적이 이미 쓴 번호는 건너뛴다.
- 상태 변경은 `student_status_changes` 이력으로만 한다: 학생 신청 → 승인/반려, 또는 담당자 직접 처리(즉시 승인). 신청자는 자기 신청을 처리할 수 없다(직무 분리). 승인 시 신청 때의 상태와 현재 상태가 다르면 거부한다. 처리 중 신청은 학적당 하나(부분 unique 인덱스).
- CSV 가져오기의 상태 값은 자료 정정으로 보고 이력을 만들지 않는다(최초 이관이 목적).
- 지도교수 `advisor_assignments`는 기간을 갖고, 열린 주 지도교수는 하나(부분 unique 인덱스). 새 주 지도교수를 배정하면 이전 배정을 종료한다.
- `StudentRecord`가 `TargetSubject`(0006)를 구현한다. 학기 업무 기간의 대상 조건을 학적으로 판단한다.

## 행 수준 보안

0003의 패턴을 people, guardians, student_records, student_status_changes에 적용했다. 애플리케이션 스코프(`StudentRecord::constrainVisibleTo`, `Person::constrainVisibleTo`)와 DB 정책이 같은 규칙이고, `StudentRecordAccessTest`가 역할별로 두 결과가 같은지 확인한다.

| 규칙 | 이유 |
|---|---|
| 배열 함수는 `= ANY ((SELECT util.permission_org_unit_ids('...'))::bigint[])` | `ANY (SELECT ...)`는 행 집합으로 해석되어 `bigint = bigint[]` 오류. 괄호와 캐스트로 배열 하나로 만든다 |
| `people.created_by_id DEFAULT util.current_user_id()` + 작성자 조회 정책 | `INSERT ... RETURNING`은 새 행의 SELECT 권한이 필요하다. 학적이 아직 없는 새 인물도 만든 사람은 볼 수 있어야 한다 |
| `advisor_assignments`는 RLS 없음 | student_records 정책이 지도 관계를 참조하므로, 여기에도 정책을 두면 정책끼리 재귀한다. 지도 관계 자체는 민감 정보가 아니다 |
| SECURITY DEFINER 함수: `util.student_number_exists()`, `util.user_id_for_student_number()`, `util.user_id_for_institutional_email()` | 학번 중복 검사와 로그인 식별은 호출자가 볼 수 없는 행도 확인해야 한다. 함수는 존재 여부나 사용자 ID만 돌려준다 |

테스트는 `DatabaseTestCase`가 기본 컨텍스트로 `app.actor=system:test`를 두어 팩토리가 RLS를 통과하게 한다(`DatabaseSessionContext::useBaseline`). 정책 테스트와 포털 RLS 테스트는 `useBaseline([])`로 기본 컨텍스트를 없애고, 요청 미들웨어가 설정한 사용자 컨텍스트로만 조회한다.

## 로그인 수단

| 설정 (`authentication.*`) | 동작 |
|---|---|
| `student_number_login` | `disabled`, `active_records`(재학·휴학), `all_records`. `LoginIdentifierResolver`가 이메일 또는 학번을 계정으로 바꾼다 |
| `lockout_threshold`, `lockout_minutes` | 계정 단위 잠금(`AccountLockout`). 이메일과 학번 어느 쪽 시도든 같은 계정으로 센다. IP 단위 속도 제한(`login` 제한기, 키 `user:{id}\|ip`)과 별도 |
| `sso_providers` | OpenID Connect 제공자 목록. 클라이언트 비밀값은 암호화 저장 |
| `sso_link_mode` | `manual`(로그인 후 본인이 연결), `institutional_email`(제공자가 확인한 이메일 = 학적의 학교 이메일이면 자동 연결) |
| `password_login_for_sso_users` | 끄면 SSO를 연결한 계정의 비밀번호 로그인을 막는다 |

SSO 구현

- Socialite 대신 작은 OIDC 클라이언트(`OidcClient`, firebase/php-jwt)를 썼다. 표준 discovery(`/.well-known/openid-configuration`), authorization code + PKCE(S256), state와 nonce, ID 토큰을 JWKS로 서명·issuer·audience·만료·nonce까지 로컬 검증한다. 제공자별 패키지 없이 Entra ID, Google Workspace, Keycloak을 같은 코드로 쓴다.
- `user_identities`(provider, subject unique)에 연결을 둔다. 연결 시 최근 비밀번호 확인이 필요하고 연결/해제는 보안 로그에 남는다.
- SSO 로그인만으로 계정을 만들지 않는다(계정은 인물과 연결되어야 하므로).
- 2단계 인증: 제공자의 `trusts_mfa`가 켜져 있지 않으면 SSO 로그인 뒤에도 Fortify 2단계 인증 화면으로 넘긴다(세션 `login.id`).
- 졸업생: 학교 계정이 없어져도 계정의 로그인 이메일(개인 이메일)과 비밀번호로 로그인한다. `SignInMethodsTest`가 SSO로 쓰던 졸업생의 이메일 로그인을 확인한다.

## 고려했지만 택하지 않은 것

| 대안 | 택하지 않은 이유 |
|---|---|
| 학적마다 계정 | 재입학, 복수 학적 학생이 계정을 여러 개 갖게 됨. 7.1절의 "한 사람 한 계정" 원칙 |
| 학번을 인물에 두기 | 학번은 입학마다 새로 부여된다(10.1절) |
| Laravel Socialite | 제공자별 드라이버와 추가 패키지가 필요하고, ID 토큰 nonce 검증을 직접 해야 한다 |
| 학적 상태를 수정 화면에서 바로 변경 | 사유와 승인 이력이 남지 않는다 |
