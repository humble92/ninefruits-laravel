# 0003. 행 단위 보안(RLS) PoC 결과와 정책 작성 규칙

- 상태: 채택 (2026-10-22, Phase 0 PoC)
- 관련: [이식 계획 7.4절](../porting-plan.md#74-행-단위-보안-최소-권한-유지)

## PoC 범위

학적(`student_records`)과 수강(`enrollments`) 모양의 테이블 두 개로 Phase 2에서 쓸 정책 형태를 검증했다(`tests/Database/RowLevelSecurityTest.php`).

| 정책 | 조건 |
|---|---|
| 본인 | `user_id = (SELECT util.current_user_id())`, 하위 테이블은 `student_record_id IN (본인 학적)` |
| 전체 권한 보유 교직원 | `(SELECT util.has_permission('...'))` — 유효기간 내 전체 범위 할당만 인정 |
| 시스템 작업 | `(SELECT util.is_system_context())` — 큐 작업, 스케줄, Artisan 명령 |

## 검증 결과

| 항목 | 결과 |
|---|---|
| 학생 A가 학생 B의 행 조회 | 0건. ID를 알고 직접 조회/수정해도 0건 |
| 컨텍스트 없음(미들웨어 누락 등) | 0건 (실패 시 닫힘) |
| 권한 할당 종료 직후 | 다음 쿼리부터 0건 |
| 보이지 않는 행 쓰기 | `WITH CHECK` 위반으로 거부 |
| 큐 작업 | 시스템 컨텍스트로 전체 조회, 작업 종료 후 컨텍스트 비움 |
| 요청 종료 후 같은 연결 | 컨텍스트 비어 있음 (Octane 연결 재사용 대비) |

Octane은 아직 설치하지 않았다(Phase 13에서 결정). 연결 재사용 상황은 테스트에서 같은 프로세스, 같은 연결로 요청 후 상태를 확인하는 방식으로 검증했다.

## 성능 측정

데이터: 학적 50,000건, 수강 500,000건. 스크립트: `database/sql/benchmarks/rls_poc_setup.sql`, `rls_poc_queries.sql`. 개발 PC(Apple Silicon, Docker) 측정.

| 쿼리 (학생 본인) | 함수를 그대로 호출 | `(SELECT 함수())`로 감싼 정책 |
|---|---|---|
| 본인 수강 전체 (학적과 조인) | 1,156 ms (순차 스캔, 행마다 함수 실행) | 25 ms |
| 학적 ID로 수강 조회 (일반 Eloquent 쿼리) | 0.3 ms | 0.4 ms |
| 수강 전체 건수 | - | 12 ms |

함수를 스칼라 서브쿼리로 감싸면 쿼리당 한 번(InitPlan)만 실행된다. 이 차이가 운영 성능을 좌우하므로 정책 작성 규칙으로 고정한다.

## 정책 작성 규칙

1. 정책 안의 함수 호출은 항상 `(SELECT util.xxx())`로 감싼다.
2. 하위 테이블의 본인 판정은 `EXISTS`보다 `컬럼 IN (SELECT ...)` 형태로 쓴다.
3. 애플리케이션 계층(`visibleTo` 스코프)이 1차 방어선이다. 화면 쿼리는 항상 학적 ID 같은 조건을 붙여 인덱스를 타게 하고, RLS는 누락에 대비한 2차 방어선으로 둔다.
4. 이름은 `{table}_{대상}_{동작}` (예: `enrollments_owner_select`, `enrollments_staff_all`, `enrollments_system_all`).
5. 정책 SQL은 `database/sql/policies/{table}.sql`에 두고 해당 테이블 마이그레이션에서 적용한다.
6. 조직 단위 범위 정책은 `util.permission_scope_ids(permission, 'org_unit')`를 쓴다 (Phase 1 조직 단위 이후).

## 보안상 한계

시스템 컨텍스트는 `app.actor` 세션 설정으로 판정하므로 SQL Injection이 가능하면 우회될 수 있다. RLS는 애플리케이션 버그(스코프 누락)에 대한 방어이며 SQL Injection 방어는 바인딩 강제와 정적 검사(`NoDynamicRawSqlRule`)가 맡는다.

## 결정

Phase 2부터 계획의 대상 테이블(`people` 민감 컬럼, `student_records`, `enrollments`, `term_records`, `grade_change_requests`, `certificate_issues`, `evaluation_*`)에 위 규칙으로 RLS를 적용한다. 대상 테이블을 줄일 이유는 PoC에서 발견되지 않았다.
