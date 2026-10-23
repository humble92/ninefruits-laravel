# LMS 연동 가이드 (다음 단계)

- 작성일: 2026-10-23 (Phase 12)
- 대상: LMS 연동을 구현할 개발자, 연동을 운영할 시스템 관리자
- 관련: [이식 계획 12절](porting-plan.md), [설계 결정 0017](decisions/0017-lms-integration.md), [관리자 매뉴얼 28장](manual/admin/28-lms-integration.md)

## 1. 원칙

| 데이터 | 원본 | 흐름 |
|---|---|---|
| 사용자, 개설강좌, 수강 명단 | 학사 시스템 | 학사 → LMS (이벤트 즉시 + 야간 대조) |
| 강의 콘텐츠, 과제, 퀴즈, 원점수 | LMS | LMS 안에 머묾 |
| 최종 점수 | LMS → 학사 **초안** | 교원이 성적 입력 화면에서 불러와 확인 후 저장·제출 |
| 확정 성적 | 학사 시스템 | LMS로 보내지 않음 |

## 2. 이번 범위에서 만든 기반

- 테이블: `lms_platforms`(드라이버, URL, 암호화된 인증 정보, 옵션), `lms_course_links`(강좌 ↔ 플랫폼 강좌), `lms_user_links`(사용자 ↔ 플랫폼 사용자), `lms_sync_logs`(단계별 결과).
- 인터페이스 `App\Domain\Lms\Contracts\LmsConnector`: `ensureCourse`, `ensureUser`, `enroll`, `unenroll`, `members`, `finalScores`. 구현체는 상태를 갖지 않고 호출마다 플랫폼(인증 정보)을 받는다.
- `NullConnector`: 호출 없이 결정적 ID(`course-{section id}`, `user-{user id}`)를 돌려준다. 이벤트·잡·로그·대조 루프를 실제 플랫폼 없이 검증하는 용도.
- `ConnectorRegistry::DRIVERS`: 드라이버 → 커넥터. 구현이 없는 드라이버(`moodle`, `openedx`)는 동기화 시 실패로 기록된다.
- 도메인 이벤트 → 큐 작업: `SectionPublished` → `SyncSectionJob`, `EnrollmentConfirmed`/`EnrollmentDropped` → `SyncEnrollmentJob`, `GradesFinalized` → (보내지 않음). 리스너 `QueueLmsSync`.
- 액션: `SyncSection`(강좌 + 교원 + 수강생), `SyncEnrollment`, `PullGrades`, `ReconcilePlatform`.
- 명령: `lms:sync {platform}`, `lms:reconcile [platform]`(매일 02:30 스케줄).
- 화면: **LMS 연동 > LMS 플랫폼 / 강좌 연결 / 동기화 기록**, 포털 강좌 상세와 담당 강좌의 LMS 링크, 성적 입력의 **LMS 점수 불러오기**.

## 3. 커넥터 구현 절차

1. `app/Domain/Lms/Connectors/{Driver}Connector.php`에 `LmsConnector`를 구현한다. HTTP 호출은 `Http::baseUrl($platform->base_url)`로, 인증 정보는 `$platform->credentials['token']` 등 키 이름을 정해 문서화한다.
2. `ConnectorRegistry::DRIVERS`에 드라이버를 등록한다(`LmsDriver` enum에 없는 드라이버면 case를 추가).
3. 식별자 매핑: 학사 측 키는 `sections.id`, `users.id`. 플랫폼 측 ID는 `lms_course_links.external_course_id`, `lms_user_links.external_user_id`에 저장되고, 커넥터는 **같은 입력에 같은 ID**를 돌려주어야 한다(멱등). 사용자 매칭 키는 기관 이메일(`student_records.institutional_email` 또는 `users.email`)을 권장한다.
4. 오류는 예외로 던진다. `SyncLogger`가 실패로 기록하고 큐가 3회 재시도한다. 영구 오류(권한 없음 등)는 재시도해도 소용없으므로 `$tries`를 고려해 명확한 메시지를 남긴다.
5. 테스트: `tests/Feature/Lms/LmsTest.php`처럼 커넥터를 컨테이너에 바인딩해 플랫폼 없이 검증한다. 실제 플랫폼 테스트는 Sail `docker-compose.override.yml`에 테스트 인스턴스를 붙여 수동으로 한다(4절).

## 4. 대상 플랫폼별 메모 (구현 시 대상 버전에서 검증할 것)

### Moodle (4.x)

- 인증: Web Services 토큰(사이트 관리 > 서버 > 웹 서비스), REST 프로토콜. 필요한 함수 권한: `core_course_create_courses`, `core_course_get_courses_by_field`, `core_user_create_users`, `core_user_get_users_by_field`, `enrol_manual_enrol_users`, `enrol_manual_unenrol_users`, `core_enrol_get_enrolled_users`, `gradereport_user_get_grade_items`(최종 점수).
- 엔드포인트: `POST {base}/webservice/rest/server.php?wstoken=…&wsfunction=…&moodlewsrestformat=json`.
- 매핑: 강좌 `shortname` = `{term code}-{course code}-{section code}`, `idnumber` = `sections.id`; 사용자 `username`/`idnumber` = 기관 이메일 / `users.id`. 역할 ID는 플랫폼 설정(기본 editingteacher=3, student=5)을 `options`에 둔다.
- 통합 로그인: Moodle OIDC 플러그인(auth_oidc) 또는 SAML2(auth_saml2)를 공용 IdP에 연결. 학사 시스템은 IdP가 아니므로 IdP를 별도 운영한다(13.1절 SSO 참조).
- 강좌 바로가기: `external_url` = `{base}/course/view.php?id={course id}`. LTI 1.3은 IdP와 같은 신원 체계가 있어야 의미가 있으므로 다음 단계.

### Open edX (Teak 이상)

- 인증: OAuth2 클라이언트(JWT), `{lms}/oauth2/access_token`. 강좌 생성은 Studio(CMS) API, 등록은 `POST {lms}/api/enrollment/v1/enrollment`, 명단은 `GET {lms}/api/enrollment/v1/enrollments?course_id=…`, 점수는 `GET {lms}/api/grades/v1/courses/{course_id}/`.
- 강좌 키 형식 `course-v1:{Org}+{Number}+{Run}`: `Org` = 기관 코드, `Number` = 과목 코드, `Run` = 학기 코드 + 분반.
- 사용자: 이메일로 매칭. 계정 자동 생성은 플랫폼 정책(`ALLOW_PUBLIC_ACCOUNT_CREATION`)에 따른다.

### 1EdTech OneRoster 1.2

- 명단 교환 표준. 플랫폼이 OneRoster 소비자를 지원하면 커넥터 대신 `reporting` 뷰(`students`, `section_enrollments`)를 CSV 번들로 내보내는 편이 단순하다.

## 5. 운영

- 즉시 반영: 강좌 공개, 수강 확정, 수강 취소 시 큐 작업(Horizon). 실패는 **동기화 기록**에서 보고 **강좌 연결 > 다시 동기화**로 재시도한다.
- 야간 대조: `lms:reconcile`(02:30). 플랫폼 명단과 학사 명단을 비교해 빠진 학생을 추가하고 남은 학생을 제거한다. 차이는 `reconcile` 로그의 `payload`(`missing`, `extra`)에 남는다. 대조 범위는 끝나지 않은 학기의 공개 강좌.
- 학기 시작 전 전체 밀어넣기: `lms:sync {platform} --term={학기}`.
- 인증 정보 교체: 플랫폼 수정 화면에서 새 값 입력(비워 두면 유지). 데이터 변경 기록에는 값이 남지 않는다(비밀 칼럼).
- 플랫폼 비활성화: 이벤트·대조가 멈춘다. 연결과 기록은 남는다.

## 6. 아직 없는 것

- 실제 커넥터(Moodle, Open edX), LTI 1.3 실행, 통합 로그인(IdP), 점수 자동 저장(초안 불러오기까지만), 콘텐츠·과제 연동(LMS 고유).
