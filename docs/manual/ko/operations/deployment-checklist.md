# 배포 체크리스트와 PHP 보안 설정

## PHP 버전

- **PHP ≥ 8.5.12**(또는 8.4.27, 8.3.36)를 쓴다. 2026-10-21 보안 릴리스가 인증 없는 원격 코드 실행 취약점 2건(CVE-2026-109618 OOM 상태 손상, CVE-2026-109619 요청 간 상태 잔존)을 고쳤고, php.net은 "완전한 완화책은 없으며 업데이트만이 안전하다"고 밝혔다. `composer.json`의 `"php": "^8.5.12"`가 이보다 낮은 PHP에서의 설치를 막는다.
- 개발 환경: `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d` 뒤 `./vendor/bin/sail php -v`로 확인한다.
- 컨테이너·서버 이미지를 만들 때 PHP 패키지를 고정하지 말고 최신 패치 릴리스를 받는다. `system:health-check`는 PHP 버전도 보여 준다.

## 운영 php.ini (php-fpm 풀 또는 서버 설정, `.user.ini`·`ini_set()`이 아님)

| 설정 | 값 | 이유 |
|---|---|---|
| `session.upload_progress.enabled` | `0` | CVE-2026-109619와 CVE-2026-115560의 경로를 막는다(php.net 2026-10-21·23 권고). 이 시스템은 업로드 진행률 표시를 쓰지 않는다 |
| `fatal_error_backtraces` | `0` (PHP 8.5) | 같은 권고의 치명적 오류 역추적 경로 차단 |
| `memory_limit` | `256M` 이상, `php_admin_value`로 고정 | 앱 코드가 바꾸지 못하게 |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | 입학 서류·시험 증빙·사진 업로드 상한(앱의 검증 5MB·2MB보다 여유) |
| `expose_php` | `Off` | 버전 노출 방지 |
| `display_errors` | `Off`, `log_errors=On` | 운영 |
| `session.cookie_secure`, `session.cookie_httponly`, `session.cookie_samesite` | `1`, `1`, `Lax` | Laravel 세션 설정(`SESSION_SECURE_COOKIE=true`)과 함께 |
| `opcache.enable`, `opcache.validate_timestamps` | `1`, `0` (배포 시 재시작) | 성능 |

## 애플리케이션 환경(.env)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`을 실제 주소로.
- `APP_KEY`, `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`는 비밀 저장소에서 주입하고 백업한다([보안 키](security-keys.md)). 키를 잃으면 개인정보를 복호화할 수 없다.
- DB: `DB_USERNAME=app_runtime`, `DB_MIGRATOR_USERNAME=app_migrator`(마이그레이션 때만 쓰는 비밀번호), `app_reporting`은 BI 도구에만.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`. Horizon(`php artisan horizon`)과 스케줄러(`* * * * * php artisan schedule:run`)를 서비스로 등록한다.
- `GOTENBERG_URL`: 내부망 주소. Gotenberg는 외부에 노출하지 않는다.
- 메일(`MAIL_*`): 초대, 2단계 인증 메일, 수강·성적 알림에 쓴다.

## 배포 절차

1. 백업([백업·복구](backup-restore.md)).
2. 코드 배포, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force`(자동으로 migrator 연결), `php artisan security:sync-permissions`, `php artisan setup:install --force`(필수 데이터 갱신, 관리자 수정 유지).
4. `php artisan optimize`, Horizon 재시작(`horizon:terminate`), php-fpm 재시작(새 php.ini 적용).
5. `php artisan system:health-check`로 확인. `audit:verify-chain`으로 감사 체인 확인.

## 정기 점검

| 주기 | 할 일 |
|---|---|
| 매일(자동) | `audit:seal`, `lms:reconcile`, 수강 예약 좌석 해제, 대기자 승격, 지원서 초안 만료 |
| 매주 | `system:health-check`, PHP·OS 보안 업데이트 확인, Horizon 실패 작업 확인 |
| 매월 | `media:prune-orphans --dry-run`, 백업 복구 연습, `audit:verify-chain` |
| 학기마다 | 역할 할당 점검(퇴직·이동자), 보존 기간 지난 감사 정리(`audit:prune`) |
