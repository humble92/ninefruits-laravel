# 0005. 권한 모델 구현: 권한 카탈로그, 범위 할당, persona

- 상태: 채택 (2026-10-22, Phase 0)
- 관련: [이식 계획 7.2절](../porting-plan.md#72-권한-구조-기능과-역할의-분리)

## 구성

| 층 | 구현 |
|---|---|
| 권한 | `App\Domain\Security\Enums\Permission` (코드가 원본). `php artisan security:sync-permissions`로 `permissions` 테이블과 맞춘다. 시스템 역할은 항상 모든 권한을 가진다 |
| 역할 | `roles` 테이블(spatie/laravel-permission) + 다국어 표시 이름(`display_name` jsonb), `persona`, `is_system`. 관리 화면에서 생성/수정 |
| 범위 할당 | `role_assignments`: 사용자 + 역할 + 범위(`global`/`org_unit`/`program`, 범위 ID) + 유효기간 + 사유 + 할당자. 수정하지 않고 종료 후 새로 할당해 이력을 남긴다 |
| persona | `PersonaDetector` 구현체가 데이터로 판정. persona 역할(`roles.persona`)의 권한이 "본인 범위"로 부여된다. 수동 할당 불가 |

## spatie/laravel-permission 사용 방식

- 역할, 권한, 역할-권한 관계 저장과 모델만 사용한다. 사용자-역할 연결(`model_has_roles`)은 쓰지 않는다. 유효기간과 범위를 표현할 수 없기 때문이다.
- 패키지의 Gate 등록(`register_permission_check_method`)은 끄고, `Gate::before`에서 카탈로그 권한 이름에 한해 `AuthorizationService`가 답한다. 그 외 능력(`viewAny`, `update` 등)은 Policy가 판단하고, Policy는 다시 카탈로그 권한을 확인한다.
- `AuthorizationService`와 `PersonaResolver`는 scoped 인스턴스다. 요청과 큐 작업마다 한 번 계산하고, 역할/할당 변경은 다음 요청부터 반영된다.

## 데이터 범위

`AuthorizationService::scopeFor($user, Permission::X)`가 `PermissionScope`(전체 여부, 조직 단위 ID 목록, 프로그램 ID 목록, 본인 포함 여부)를 돌려준다. Phase 1 이후 각 모델의 `scopeVisibleTo(User $user)`가 이 값을 쿼리 조건으로 바꾼다. DB 계층은 같은 판정을 `util.has_permission()`, `util.permission_scope_ids()`로 한다([0003](0003-row-level-security.md)).

## 직무 분리

Policy에서 차단한다. Phase 0 규칙: 사용자는 자기 역할 할당을 종료할 수 없고, 자기 2단계 인증을 관리자 기능으로 초기화할 수 없다. 성적 정정 요청자/승인자 분리는 Phase 6에서 같은 방식으로 추가한다.

## 레거시 역할 기본 템플릿

`RoleTemplateSeeder`가 만든다. 다시 실행해도 관리자가 바꾼 권한은 유지된다.

| 내부 이름 | 레거시 | Phase 0 권한 |
|---|---|---|
| `system_administrator` | DBA, 관리자 | 전체 (시스템 역할) |
| `registrar` | `ACAREG` | 관리 화면, 사용자 조회 |
| `academic_affairs` | `ACASCORE` | 관리 화면, 사용자 조회 |
| `certificate_officer` | `ACACERT` | 관리 화면, 사용자 조회 |
| `department_assistant` | `ASSISTANT` | 관리 화면, 사용자 조회 |
| `admissions_officer` | 없음 (신규) | 관리 화면, 사용자 조회 |
| `applicant`, `student`, `alumni`, `instructor`, `advisor` | `STUDENT`, `PROFESSOR` | persona 역할, 권한 없음 |

각 Phase가 업무 권한을 카탈로그에 추가하면서 템플릿 권한도 함께 정한다.
