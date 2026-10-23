# 코드 리뷰 체크리스트

- 작성일: 2026-10-22
- 관련: [명명 규칙과 용어집](naming.md), [이식 계획 2절](../porting-plan.md#2-기본-원칙)

PR 설명에 해당 없는 항목은 "해당 없음"으로 표시한다. 자동 검사로 확인되는 항목은 (CI)로 표시했다.

## 1. 자동 검사

- [ ] `composer check` 통과: Pint, Larastan(레벨 6), 원시 SQL 검사, 명명 검사, 번역 누락 검사, 테스트 (CI)
- [ ] `composer audit` 경고 없음 (CI)

## 2. 명명

- [ ] 새 용어는 [용어집](naming.md#6-용어집)에 먼저 추가했다
- [ ] 테이블/컬럼/권한/라우트/번역 키가 [명명 규칙](naming.md)을 따른다 (테이블과 컬럼 일부는 `php artisan naming:check`로 확인)
- [ ] 레거시 이름(`SFOP_`, `SEMECNT`, 병음 파일명 등)을 그대로 옮기지 않았다

## 3. 데이터베이스

- [ ] 스키마 변경은 마이그레이션으로, 함수/트리거/정책/뷰는 `database/sql/` 파일로 관리한다
- [ ] 시각은 `timestampTz`, 날짜는 `date`, 기간은 `daterange`/`tstzrange`다. 문자열 날짜가 없다
- [ ] 다국어 값은 `jsonb` 한 컬럼 + `CHECK (jsonb_typeof(col) = 'object')`, 모델은 `HasTranslatableAttributes`
- [ ] 학적/성적 등 이력이 중요한 테이블은 `util.enable_audit()`를 적용했고, 비밀 컬럼은 마스킹 목록에 넣었다
- [ ] RLS 대상 테이블은 정책을 추가했고 [정책 작성 규칙](../decisions/0003-row-level-security.md#정책-작성-규칙)(함수는 `(SELECT ...)`로 감싸기)을 따랐다
- [ ] 백업용 테이블이나 `_old`, 날짜 접미사 테이블을 만들지 않았다

## 4. 보안

- [ ] 원시 SQL에 값을 문자열로 넣지 않았다. 바인딩을 쓰고, 정렬/필터 컬럼은 허용 목록으로 받는다
- [ ] 권한은 카탈로그(`Permission` enum)에 추가하고 번역(`lang/*/permissions.php`)을 넣었다
- [ ] 화면(Filament Resource `getEloquentQuery()`, Livewire 조회)은 `visibleTo` 스코프나 Policy를 거친다
- [ ] 역할별 접근 테스트가 있다 (다른 학생의 URL을 직접 호출하면 403 등)
- [ ] 개인정보는 `HasEncryptedPii` + `*_encrypted`/`*_hash` 컬럼, 화면은 마스킹, 표시 해제는 `RevealSensitiveValue`
- [ ] 직무 분리 규칙(요청자는 승인 불가 등)을 Policy에 반영했다
- [ ] 대량 내보내기는 권한, 사유, 보안 로그(`data.exported`)를 남긴다

## 5. 구조

- [ ] 업무 로직은 Action(`app/Domain/{Context}/Actions`)에 있고 Filament와 Livewire는 Action을 호출만 한다
- [ ] 검증은 Form Request 또는 Filament/Livewire 검증 규칙, 권한은 Policy/Gate
- [ ] 상태값은 backed enum + cast, 설정은 `config()`/settings 클래스로만 읽는다 (`env()`는 config 파일 안에서만)
- [ ] 학적/성적 변경, 권한 변경 등 업무 감사 대상은 `AuditLogger`로 사유와 함께 기록한다
- [ ] 큐 작업은 멱등이고, 중복 실행 방지가 필요하면 `ShouldBeUnique`/`WithoutOverlapping`을 쓴다
- [ ] 지연 로딩이 없다 (strict mode에서 예외), 목록은 `with()`로 미리 불러온다

## 6. 다국어

- [ ] 화면 문구는 번역 키(`{group}.{영역}.{의미}`)를 쓰고 영어 문장을 키로 쓰지 않았다
- [ ] `en`, `ko`, `zh_CN` 번역을 모두 넣었다 (`php artisan i18n:missing --strict`)
- [ ] 날짜와 숫자는 사용자 로케일로 표시한다 (`isoFormat`, `Number`)

## 7. 테스트

- [ ] 경로: `tests/{Unit|Feature|Database}/{Context}/{대상}Test.php`
- [ ] 데이터는 팩토리로 만든다. 레거시 실데이터나 실제 개인정보를 쓰지 않는다
- [ ] DB 오류를 기대하는 테스트는 `databaseError()` 헬퍼로 세이브포인트 안에서 실행한다
