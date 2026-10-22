# 명명 규칙과 용어집

- 작성일: 2026-10-21, 확정: 2026-10-22 (Phase 0)
- 적용 대상: DB 스키마, PHP 코드, 라우트, 권한, 번역 키, 설정, 테스트
- 목적: 새로 합류한 IT 담당자가 이름만 보고 의미와 위치를 추측할 수 있게 한다.

---

## 1. 레거시 이름의 문제

레거시 YUSTIC 스키마와 소스에서 확인된 불일치 사례다. 신규 시스템은 이 사례를 반복하지 않는다.

| 유형 | 사례 |
|---|---|
| 같은 개념, 다른 이름 | 학기: `SFOP_SEMECNT`, `SSC_SEMECNT`, `TIMETABLE.SEMESTER`, `SUR_SEMESTER` |
| | 교과목: `SFOP_OBJID`, `SSC_OBJID`, `TIMETABLE.OPENSUBJECT_ID`, `LECTUREPLAN.SUBJECT_ID` |
| | 분반: `SFOP_COURSEID`, `TIMETABLE.COURSE`, `LECTUREPLAN.COURSE` (교과목이 아니라 분반인데 `COURSE`) |
| 접두어 규칙 혼재 | `SF*`(기준), `SR*`(재학), `SG*`(졸업), `SS*`(성적), `AF*`와 접두어 없는 `TIMETABLE`, `ROOM`, `BUILDING`, `LECTUREPLAN` 공존 |
| 컬럼에 테이블 약어 반복 | `SFOP_`, `SSC_`, `SRFAM_` 등. 같은 테이블 안에서도 `SFSEMESTER.AFS_CHECK`처럼 다른 접두어 |
| 언어 표기 혼재 | 접두 방식 `SFO_KNAME`/`SFO_CNAME`/`SFO_ENAME`와 접미 방식 `NAME_KR`/`NAME_CN`/`NAME_EN` |
| 약어 의미 불명 | `SFOP`, `SEMECNT`, `ORELA`, `AFS_CHECK`, `SRAD_ID` |
| 오타 | `SR_KPTREALATION`(relation), `SFO_ANCERTOR`(ancestor), `AODERTIFICATE_T`(certificate), `S_GRAMMER`(grammar) |
| 병음/음차 파일명 | `prt_chuxi`(출석), `prt_chengjidan`(성적표), `pingjia`(평가), `xueji`(학적), `zaixue`(재학), `biye`(졸업) |
| 의미가 바뀐 이름 | `LEADING_PROFESSOR`(실제 의미: 지도교수), `FNATION`(실제 의미: 민족) |
| 백업을 테이블로 | `SSCORE_PRE20170302`, `FACULTY_BAKCUP_20051018` |

---

## 2. 공통 원칙

1. 모든 식별자는 영어(미국식 철자). 한국어 로마자, 병음 금지.
2. 약어 금지. 허용 목록만 사용: `id`, `url`, `uri`, `api`, `pdf`, `csv`, `gpa`, `mfa`, `lms`, `lti`, `iso`, `ip`, `pii`(개인정보), `i18n`(다국어). 목록에 추가할 때는 이 문서를 먼저 고친다.
3. 한 개념에는 이름 하나. 5절 용어집의 영문 표준어를 DB, 코드, 화면 라벨 키에 똑같이 쓴다.
4. 의미가 없는 숫자 접미사(`view1`, `score2`), 날짜 접미사(`_20170302`), `_old`, `_new`, `_backup`, `_tmp` 금지.
5. 이름에 언어 코드를 넣지 않는다. 다국어 값은 `jsonb` 컬럼 하나에 담는다.

---

## 3. 데이터베이스

### 3.1 테이블

| 규칙 | 예 |
|---|---|
| snake_case 복수 명사 | `student_records`, `course_sections` |
| 컨텍스트 접두어 없음 (스키마로 구분) | `audit.record_changes`, `reporting.section_enrollment_counts` |
| 순수 연결 테이블(추가 컬럼 없음)은 Laravel 관례: 단수, 알파벳 순 | `course_tag` |
| 자체 속성이 있는 연결은 독립 엔티티로 복수 명사 | `section_instructors` (역할, 담당 비율 보유) |
| 정체성과 버전 분리 시 `{entity}_versions` | `courses`, `course_versions` |

### 3.2 컬럼

| 규칙 | 예 |
|---|---|
| snake_case, 테이블 약어 접두어 없음 | `capacity` (레거시 `SFOP_MAX`) |
| 대리키 `id` (bigint identity) | |
| 외래키 `{단수 테이블명}_id` | `course_section_id`, `term_id` |
| 같은 테이블을 두 번 참조하면 역할을 앞에 | `replaced_by_enrollment_id`, `successor_org_unit_id` |
| 업무 코드(사람이 보는 코드)는 `code`, unique | `courses.code`, `terms.code` |
| 불리언은 `is_`, `has_`, `can_`, 또는 동사형 | `is_retake`, `counts_toward_credit_limit` |
| 날짜(`date`)는 `_date` | `birth_date`, `admission_date` |
| 시각(`timestamptz`)은 `_at` | `finalized_at`, `dropped_at` |
| 기간(`daterange`/`tstzrange`)은 `_period` | `effective_period` |
| 상태는 `status` (PHP backed enum, snake_case 문자열 값) | `status = 'withdrawn'` |
| 하나의 테이블에 상태가 둘 이상이면 `{대상}_status` | `grade_status` |
| 다국어 값은 단수 명사 `jsonb`, 언어 코드 없음 | `name`, `title`, `description` |
| 인명은 `family_name`, `given_name`, `name_variants` | |
| 암호화 컬럼은 `_encrypted`, 검색 인덱스는 `_hash` | `national_id_encrypted`, `national_id_hash` |
| 수량은 `_count`, 학점은 `credits` | `enrolled_count`, `credits_attempted` |
| 감사 컬럼 | `created_at`, `updated_at`, `created_by_id`, `updated_by_id` |

### 3.3 제약, 인덱스, 함수, 뷰

| 대상 | 규칙 | 예 |
|---|---|---|
| 인덱스/제약 | Laravel 기본 생성 이름 사용 (`{table}_{columns}_{type}`) | `enrollments_student_record_id_index` |
| 직접 작성하는 제약 | `{table}_{의미}_{type}` | `course_versions_no_overlap_excl` |
| 함수 | `util` 스키마, 동사 또는 명사구 snake_case | `util.i18n`, `util.can_access_student_record` |
| 트리거 | `{table}_{시점}_{목적}` | `enrollments_before_update_guard_finalized` |
| RLS 정책 | `{table}_{대상}_{동작}` | `enrollments_owner_select` |
| 보고용 뷰 | `reporting` 스키마, 내용을 설명하는 복수 명사 | `reporting.student_term_gpas` |
| SQL 파일 | `database/sql/{functions|triggers|policies|views}/{name}.sql` | `database/sql/functions/i18n.sql` |
| 감사 트리거 | `util.enable_audit()`가 `{table}_after_write_audit`, `{table}_after_truncate_audit` 생성 | `users_after_write_audit` |
| 세션 설정 | `app.` 접두어 snake_case | `app.user_id`, `app.actor`, `app.locale` |
| 시스템 수행자 | `system:{종류}:{이름}` | `system:job:App\Jobs\...`, `system:command:term:close` |

---

## 4. PHP와 Laravel

### 4.1 클래스

| 대상 | 규칙 | 예 |
|---|---|---|
| 모델 | 단수 PascalCase, `app/Models/{Context}/` | `CourseSection`, `StudentRecord` |
| Action | 동사 + 목적어, `handle()` 하나 | `EnrollStudentInSection`, `FinalizeSectionGrades`, `MatriculateApplicant` |
| Job | 동사 + 목적어 | `PromoteWaitlistEntries`, `RecalculateTermRecord` |
| Event | 과거형 | `EnrollmentConfirmed`, `GradesFinalized` |
| Listener | 동사 + 목적어 | `SyncEnrollmentToLms` |
| Notification | 상황 + `Notification` | `WaitlistPromotedNotification` |
| Policy | `{Model}Policy` | `EnrollmentPolicy` |
| Form Request | `{동작}{Model}Request` | `StoreCourseSectionRequest` |
| Enum | `{Model}{속성}` | `EnrollmentStatus`, `GradeStatus`, `TermWindowType` |
| 수강신청 규칙 | `{조건}Rule`, `app/Domain/Registration/Rules/` | `CreditLimitRule`, `TimeConflictRule` |
| Filament Resource | `{Model}Resource` | `CourseSectionResource` |
| Livewire 컴포넌트 | 화면 의미 PascalCase, 뷰는 kebab-case | `RegistrationCart` / `registration-cart.blade.php` |
| Artisan 명령 | `{context}:{동작}` kebab-case, 클래스는 `app/Console/Commands/{Context}/` | `term:close`, `waitlist:promote` |
| Factory | `{Model}Factory`, `database/factories/{Context}/` (모델 네임스페이스와 같게) | `database/factories/Registration/EnrollmentFactory.php` |
| Seeder | 내용 + `Seeder`, 종류별 폴더 `Essential`, `Profiles`, `Demo` 아래 (`database/seeders/`) | `Essential/RoleTemplateSeeder`, `Profiles/KoreanUniversityProfileSeeder`, `Demo/DemoEnrollmentSeeder` |
| 테스트 | `tests/{Unit|Feature|Database}/{Context}/{대상}Test.php`. `Database`는 함수, 트리거, RLS 정책 등 DB 계층 테스트 | `tests/Feature/Registration/CreditLimitRuleTest.php`, `tests/Database/Security/RowLevelSecurityTest.php` |

### 4.2 메서드와 변수

- 메서드와 변수는 camelCase. 불리언 반환 메서드는 `is`, `has`, `can`으로 시작.
- 쿼리 스코프는 의미 이름: `scopeVisibleTo`, `scopeActive`, `scopeForTerm`.
- 관계 메서드는 대상 이름: 단수(`belongsTo`, `hasOne`), 복수(`hasMany`, `belongsToMany`).

---

## 5. 라우트, 권한, 설정, 번역

| 대상 | 규칙 | 예 |
|---|---|---|
| URI | kebab-case 복수 리소스 | `/portal/course-sections/{courseSection}` |
| 라우트 이름 | 점 구분, 영역.리소스.동작 | `portal.enrollments.index` |
| 권한 | `{resource}.{action}`, 리소스는 snake_case 복수 | `course_sections.create`, `grades.finalize`, `student_records.view_sensitive` |
| 역할 | 표시명은 다국어, 내부 이름은 snake_case | `registrar`, `department_assistant` |
| 설정 키 | 점 구분 snake_case | `security.mfa.enforcement`, `registration.waitlist.auto_enroll` |
| 기능 토글(Pennant) | kebab-case | `registration-cart`, `waitlist` |
| 업무 감사 이벤트 | 점 구분, 리소스 단수 + 과거형 동사 | `role.assigned`, `mfa.reset`, `settings.updated` |
| 보안 이벤트 | `SecurityEventType` enum 값, 같은 형식 | `login.failed`, `sensitive_data.revealed` |
| 번역 키 | `{group}.{영역}.{의미}` 영문 snake_case | `registration.errors.credit_limit_exceeded` |
| 환경 변수 | UPPER_SNAKE_CASE | `PII_ENCRYPTION_KEY` |

번역 키는 문장이 아니라 의미를 나타낸다. `__('Credit limit exceeded')`처럼 영어 문장을 키로 쓰지 않는다.

---

## 6. 용어집

화면 라벨은 번역 파일에서 언어별로 바꾸되, 코드와 DB는 아래 영문 표준어만 쓴다.

| 한국어 | 표준 영문 | 비고 |
|---|---|---|
| 인물 | person (`people`) | 사람 1명당 하나, 평생 유지. 학사 데이터의 주체 |
| 로그인 계정 | user (`users`) | 인증 수단. 인물과 1:1, 평생 유지 |
| 외부 로그인 연결 | user identity (`user_identities`) | 학교 계정 SSO 등 |
| 학적 | student record | 입학 1건 단위. 학생(사람)과 구분 |
| 학번 | student number | |
| 지원자 / 지원서 | applicant / application | |
| 입학 회차 | admission cycle | |
| 입학 확정(학생 전환) | matriculation | |
| 학년도 | academic year | |
| 학기 | term | `semester` 쓰지 않음 (쿼터제 등 범용성) |
| 업무 기간 | term window | 수강신청, 성적 입력 등 |
| 교과목 | course | |
| 교과목 버전 | course version | 개편 이력 |
| 교과 구분 | course category | 교양필수, 전공선택 등 |
| 개설강좌 | course section | |
| 분반 번호 | section code | 레거시 `COURSEID` |
| 강의 시간 | section meeting | 레거시 `TIMETABLE` 행 |
| 교시 | time slot | |
| 강의계획서 | syllabus | |
| 수강신청 | registration | 행위 |
| 수강 | enrollment | 결과 행 |
| 장바구니 | registration cart | |
| 대기자 | waitlist entry | |
| 수강 제한 | registration hold | 레거시 `SRDENY` |
| 수강 예외 | registration override | 레거시 `SRALLOW` |
| 선수과목 | prerequisite (requisite) | |
| 대체과목 | equivalent course | |
| 재수강 | retake | |
| 학점 | credits | |
| 성적 | grade | |
| 성적 척도 | grade scale | |
| 평점 | grade points / GPA | |
| 학기 기록 | term record | 학기별 학점, 평점 스냅샷 |
| 학사 경고 | academic probation | |
| 학업 상태 | academic standing | |
| 학적 변동 | status change | 휴학 leave of absence, 복학 return, 자퇴 withdrawal, 제적 dismissal |
| 졸업 요건 | graduation requirement | |
| 교육과정 연도 | catalog year | |
| 졸업 사정 | degree audit | |
| 학점 인정 | credit transfer | |
| 지도교수 | advisor | 레거시 `LEADING_PROFESSOR` |
| 교원 | instructor | |
| 조교 | department assistant | |
| 조직 단위 | org unit | 대학, 학부, 학과, 전공 |
| 프로그램 | program | 학위 과정, 전공 과정 |
| 강의평가 | course evaluation | |
| 증명서 | certificate | |
| 어학/종합시험 | assessment | 시험 종류는 assessment type |
| 출석 | attendance | |
| 감사 기록 | audit record | |
| 업무 감사 | activity | `audit.activity_log` |
| 데이터 변경 기록 | record change | `audit.record_changes` (트리거) |
| 보안 이벤트 | security event | `audit.security_events` |
| 권한 | permission | `{resource}.{action}` |
| 역할 | role | 권한 묶음 |
| 역할 할당 | role assignment | 사용자 + 역할 + 범위 + 유효기간 |
| 범위 | scope | global, org unit, program |
| 신분형 역할 | persona | 지원자, 학생, 졸업생, 교원, 지도교수. 데이터에서 자동 판정 |
| 2단계 인증 | two-factor authentication (코드에서는 `mfa`) | Fortify 용어는 two factor |
| 개인정보 | personal data (코드에서는 `pii`) | 암호화 대상 |
| 표시 해제 | reveal | 마스킹된 값 보기, 기록 남김 |

---

## 7. 레거시 이름 대응표

### 7.1 테이블

| 레거시 | 신규 |
|---|---|
| `SFCOLLEGE`, `SFDEPARTMENT` | `org_units`, `org_unit_types` |
| `SFSEMESTER` | `academic_years`, `terms`, `term_types` |
| `SSOBJDATE` | `term_windows` |
| `TTTIME` | `time_slots` |
| `BUILDING`, `ROOM` | `buildings`, `rooms` |
| `SFOBJECT` | `courses`, `course_versions` |
| `SFOBJDIST` | `course_categories` |
| `SPCOURSE` | `course_requisites` |
| `SFOBJECT_SUB` | `course_equivalencies` |
| `SFOPOBJ` | `course_sections` |
| `SFOPOBJ.SFOP_PROFID` | `section_instructors` |
| `TIMETABLE` | `section_meetings` |
| `TARGET_ST`, `TARGET_ST_DEPART`, `TARGET_ST_GRADE` | `section_eligibility_rules` |
| `LECTUREPLAN`, `LECTUREPLAN_LCONTENTS` | `syllabi`, `syllabus_weeks` |
| `SRREGISTRY`, `SGREGISTRY` | `people`, `student_records` |
| `FACULTY` | `people`, `instructors` |
| `SRFAMILY` | `guardians` |
| `LEADING_PROFESSOR` | `advisor_assignments` |
| `SRALTER`, `SRALTERDEF` | `student_status_changes`, `status_change_types` |
| `SRDENY` | `registration_holds` |
| `SRALLOW` | `registration_overrides` |
| `SSCORE`, `SGSCORE`, `SSCORE_CLOSE` | `enrollments` |
| `SSCOREDEF` | `grade_scales`, `grade_scale_items` |
| `FSCORESTS` | `GradeStatus` enum |
| `SRRECORD` | `term_records` |
| `SURVEY`, `SURCATEGORY`, `SURITEM`, `SURRECORD` | `evaluation_forms`, `evaluation_questions`, `evaluation_completions`, `evaluation_answers` |
| `SRCERT`, `SOCERTIFICATE`, `SOCERTIFICLASS`, `AOCERTIFICLASS` | `certificate_types`, `certificate_templates`, `certificate_issues` |
| `CET`, `CET_TYPE`, `CET_HISTORY`, `GCET`, `COMEXAM`, `COMEXAMTYPE`, `COMEXAMDETAIL` | `assessment_types`, `assessment_components`, `assessment_sessions`, `assessment_registrations`, `assessment_results`, `assessment_component_scores` |
| `FNATION` | `lookup_values` (유형 `ethnicity`) |
| `FCOUNTRY`, `FLANGUAGE` | `countries`, `languages` (ISO 3166, ISO 639 기준) |
| `FADMDIST`, `SFADMDIST` | `lookup_values` (유형 `admission_type`) |
| `S_USER`, `S_ROLE`, `USERID`, `ROLE_DESC`, `G_AUTH`, Oracle Role | `users`, `roles`, `permissions`, `role_assignments`, 2단계 인증 컬럼 |
| `MENU` | 없음 (메뉴는 코드에서 권한 기준으로 구성) |
| `SV_*`, `AV_*` 조회 뷰 | Policy/스코프 + `reporting` 뷰 |
| `*_BACKUP_*`, `*_PRE*`, `*_OLD` | 없음 (감사 기록으로 대체) |

### 7.2 컬럼 예시: `SFOPOBJ` → `course_sections`

| 레거시 | 신규 |
|---|---|
| `SFOP_SEMECNT` | `term_id` |
| `SFOP_OBJID` | `course_version_id` |
| `SFOP_COURSEID` | `section_code` |
| `SFOP_PROFID` | `section_instructors` 테이블 |
| `SFOP_DIST` | `course_category_id` (과목 기본값 재지정 시) |
| `SFOP_GRADE` | `section_eligibility_rules` 테이블 |
| `SFOP_ANCERTOR` | `course_equivalencies` 테이블 |
| `SFOP_MAX` | `capacity` |
| `SFOP_STS` | `status` |

### 7.3 컬럼 예시: `SSCORE` → `enrollments`

| 레거시 | 신규 |
|---|---|
| `SSC_STUID` | `student_record_id` |
| `SSC_SEMECNT`, `SSC_OBJID`, `SSC_COURSEID` | `course_section_id` (+ 스냅샷 `course_version_id`) |
| `SSC_DIST` | `course_category_id` (스냅샷) |
| `SSC_CREDIT` | `credits_attempted`, `credits_earned` |
| `SSC_SCORE` | `grade_scale_item_id`, `grade_points` |
| `SSC_RAPPLY` | `is_retake`, `replaced_by_enrollment_id` |
| 성적 NULL 여부 | `status`, `grade_status` |
| `SSC_BOOK` | 없음 (교재 범위 제외) |
| `SSC_CREATED_BY`, `SSC_UPDATED_ON` 등 | `created_by_id`, `updated_at` 등 + 감사 기록 |

---

## 8. 적용 방법

- PR 리뷰 체크리스트: [review-checklist.md](review-checklist.md)
- CI에서 Pint(코드 스타일), Larastan, 원시 SQL 정적 검사(`tools/phpstan/Rules/NoDynamicRawSqlRule.php`), 마이그레이션 이름 검사(`php artisan naming:check`: snake_case, 금지 접미사, 언어 코드 컬럼, 시간대 없는 시각 컬럼, 불리언/날짜 컬럼 이름), 번역 누락 검사(`php artisan i18n:missing --strict`) 실행.
- 패키지가 정한 테이블(`settings`, `jobs`, `cache`, `media`, `features`, spatie 권한 테이블)은 패키지 이름을 따른다. `naming:check`의 `UPSTREAM_MIGRATIONS`에 등록한다.
- 새 용어가 필요하면 6절 용어집에 먼저 추가한 뒤 사용한다.
