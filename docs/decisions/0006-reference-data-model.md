# 0006. 기준정보 데이터 모델과 시간 처리

- 상태: 채택 (2026-10-22, Phase 1)
- 관련: [이식 계획 10.2절, 10.4절](../porting-plan.md#102-학기-관리-유연화)

## 유효기간

조직 단위와 프로그램은 `effective_start_date`, `effective_end_date`(포함, 비면 무기한)와 PostgreSQL **생성 열** `effective_period daterange GENERATED ALWAYS AS (daterange(start, end, '[]')) STORED`를 함께 둔다. 학년도(`year_period`)와 학기(`instruction_period`)도 같은 방식이다.

- Eloquent와 관리 화면은 평범한 날짜 두 개를 다루고, DB는 생성 열로 `@>`(특정 날짜에 유효) 조회와 GiST 배제 제약(겹침 금지)을 처리한다.
- 겹침 금지: 학년도끼리, 같은 학기 종류의 학기끼리(`EXCLUDE USING gist`). 교과목 버전(Phase 4)도 같은 방식으로 만든다.
- 생성 열은 애플리케이션이 쓰지 않는다. 모델 조회는 `effectiveOn($date)` 스코프를 쓴다.

## 조직 계층과 개편

- `org_units.parent_id` 계층. `util.org_unit_descendant_ids()`, `util.org_unit_ancestor_ids()` 함수와, 자기 자신이나 하위 조직 아래로 옮기는 것을 막는 트리거(`org_units_before_write_prevent_cycle`)를 둔다.
- 조직 단위 범위 권한은 하위 조직을 포함한다. 애플리케이션(`AuthorizationService`)과 DB(`util.permission_org_unit_ids()`)가 같은 규칙을 쓴다.
- 개편은 이력을 고쳐 쓰지 않는다. 기존 조직을 개편일 전날 종료하고 `org_unit_successions`(이전 조직, 승계 조직, 변경 유형: 명칭 변경, 통합, 분리, 소속 변경)로 연결한다. 통합과 분리를 모두 표현하려고 컬럼(`successor_org_unit_id`) 대신 연결 테이블을 썼다.

## 학기와 업무 기간

- 전역 "현재 학기" 플래그가 없다. `TermResolver`가 질문별로 답한다: 수업 중인 학기(기관 날짜 기준), 업무별 열린 기간, 대상 조건에 맞는 학기, 다음에 열릴 기간.
- 학기 상태(계획, 공개, 성적 마감, 보관)는 `ChangeTermStatus`로만 바뀌고 순서가 정해져 있다. 계획/보관 학기의 업무 기간은 열리지 않는다.
- 업무 기간의 대상 조건은 공통 값 객체 `TargetCriteria`(jsonb)다. 대상은 `TargetSubject` 인터페이스로 판단하므로 Phase 2 학적, Phase 4 강좌 대상, Phase 5 수강신청 규칙이 같은 구조를 쓴다.

## 시간대: PostgreSQL 날짜 형식 수정

Laravel은 날짜를 PostgreSQL에 `Y-m-d H:i:s`(UTC 오프셋 없음)로 보낸다. 앱 시간대(UTC)가 아닌 Carbon, 예를 들어 기관 시간대(Asia/Seoul)로 만든 시각은 9시간 어긋난 순간으로 저장되고 쿼리 비교도 틀린다. Phase 1 테스트에서 발견했다.

- 수정: 쿼리 문법의 날짜 형식을 `Y-m-d H:i:sP`로 바꾼 연결 클래스(`App\Support\Database\PostgresConnection`)를 `pgsql` 드라이버에 등록했다. 모델 저장과 쿼리 바인딩 모두 정확한 순간을 보낸다. `date` 컬럼은 PostgreSQL이 날짜 부분만 취하므로 현지 날짜가 유지된다.
- 원칙: DB와 앱은 UTC, 표시와 학사 날짜 판단은 기관 시간대. 테스트: `tests/Database/Foundation/TimeZoneTest.php`.

## 다국어 속성 대입

spatie/laravel-translatable은 배열을 대입하면 기존 번역과 합친다. 그래서 화면에서 언어 칸을 비워도 번역이 지워지지 않는다. `HasTranslatableAttributes`는 배열 대입을 "전체 교체"로 바꾸고 빈 값은 버린다(남는 값이 없으면 NULL). CSV 가져오기는 의도적으로 언어별 `setTranslation`(합치기)을 써서 빈 칸이 기존 번역을 지우지 않는다.

## 기준정보 데이터 출처

- 국가(ISO 3166-1, 249개)와 언어(ISO 639-1)는 symfony/intl 데이터로 필수 시더가 만들고, 설치된 모든 언어로 이름을 채운다. 다시 실행하면 빠진 행과 빠진 언어만 추가한다.
- Laravel 기본 메시지(검증, 비밀번호, 페이지 등)의 ko, zh_CN 번역은 Laravel Lang(`laravel-lang/lang`)으로 `lang/`에 생성해 저장소에서 관리한다. 이 패키지는 의존 패키지가 관리 중단(abandoned) 상태라 `composer audit`을 실패시키므로 상시 의존성으로 두지 않는다. 갱신할 때만 임시로 설치한다:
  `composer require --dev laravel-lang/lang && php artisan lang:update && composer remove --dev laravel-lang/lang`
