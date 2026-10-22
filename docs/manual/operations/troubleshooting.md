# 장애 대응

- 대상: IT 운영자

| 증상 | 점검 |
|---|---|
| 로그인 후 바로 보안 화면으로 이동 | 2단계 인증 정책이 "필수"이고 유예 기간이 지났다. 사용자가 등록하면 해결. 정책은 **설정 > 보안** |
| 관리 화면 403 | 사용자에게 `admin_panel.access` 권한이 있는 유효한 역할 할당이 없다. **사용자와 권한 > 사용자 > 역할 할당** 확인 |
| 메뉴가 보이지 않음 | 해당 화면 권한(예: `roles.view`)이 역할에 없다 |
| 이메일 코드가 오지 않음 | 메일 설정(`MAIL_*`), 보안 이벤트의 `이메일 코드 발송` 기록, 발송 제한(1분 1회, 1시간 5회) |
| 화면에 다른 사람 데이터가 0건 | 정상일 수 있다(행 단위 보안). DB 세션 수행자가 설정되었는지 **데이터 변경 기록**의 사용자 열로 확인 |
| `permission denied for schema public` | 애플리케이션이 `app_runtime`으로 DDL을 시도했다. 마이그레이션은 `php artisan migrate`(자동으로 `app_migrator` 사용)로만 실행 |
| `Audit records are append-only` | 감사 테이블 수정/삭제 시도. 정상적인 차단이다 |
| PDF 생성 실패 | `php artisan pdf:sample`, Gotenberg 상태, `GOTENBERG_URL` |
| 큐 작업이 멈춤 | `/horizon`에서 실패 작업 확인, `php artisan horizon:status` |

요청 단위 추적: 응답 헤더 `X-Request-Id` 값으로 로그, 업무 감사, 데이터 변경 기록, 보안 이벤트를 함께 검색할 수 있다.
