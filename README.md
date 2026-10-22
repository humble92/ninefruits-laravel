# ninefruits-laravel

Student Information System running on PHP Laravel framework.

여러 대학에 설치할 수 있는 학사 행정 시스템이다. 레거시 YUSTIC(PHP + Oracle)의 기능을 참고해 Laravel로 새로 구현한다.

- 실행 계획: [docs/porting-plan.md](docs/porting-plan.md)
- Phase 0 결과: [docs/phase-0-report.md](docs/phase-0-report.md)
- 명명 규칙: [docs/conventions/naming.md](docs/conventions/naming.md), 리뷰 체크리스트: [docs/conventions/review-checklist.md](docs/conventions/review-checklist.md)
- 설계 결정: [docs/decisions/](docs/decisions/)
- 매뉴얼: [운영](docs/manual/operations/commands.md), [관리자](docs/manual/admin/README.md)

## 기술 스택

PHP 8.5, Laravel 13, Filament 5 (교직원 관리 화면), Livewire 4 (포털), PostgreSQL 18, Redis, Fortify (공용 로그인과 2단계 인증), Pest 5, Larastan, Pint. 개발 환경은 Docker(Laravel Sail).

## 개발 환경 준비

호스트에는 Docker만 있으면 된다. PHP와 Composer는 컨테이너 안에서 실행한다.

```bash
# 1. 의존성 설치 (호스트에 PHP가 없으므로 Composer 이미지 사용)
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:latest \
    composer install --ignore-platform-req='ext-*'

# 2. 환경 파일
cp .env.example .env
printf '\nWWWUSER=%s\nWWWGROUP=%s\n' "$(id -u)" "$(id -g)" >> .env

# 3. 컨테이너 기동 (최초 기동 시 DB 역할 app_migrator/app_runtime/app_reporting 생성)
./vendor/bin/sail up -d

# 4. 키 생성
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan pii:generate-key   # 출력된 두 값을 .env에 넣는다

# 5. 설치: 마이그레이션, 필수 데이터, 기관 설정, 첫 관리자
./vendor/bin/sail artisan setup:install

# 6. (선택) 데모 데이터와 역할별 데모 계정
./vendor/bin/sail artisan setup:demo --size=small

# 7. 화면 자산
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

- 포털: http://localhost/portal, 관리 화면: http://localhost/admin (같은 로그인)
- 메일 확인(Mailpit): http://localhost:8025
- 큐 모니터(Horizon): http://localhost/horizon (`queue_monitor.view` 권한)

## 자주 쓰는 명령

| 명령 | 내용 |
|---|---|
| `sail composer check` | Pint, Larastan, 명명 검사, 번역 누락 검사, 테스트 (CI와 같음) |
| `sail pest` | 테스트 |
| `sail artisan migrate` | 마이그레이션. 자동으로 스키마 소유자(`app_migrator`) 연결 사용 |
| `sail artisan setup:install`, `setup:demo` | 설치/업데이트, 데모 데이터 |
| `sail artisan security:sync-permissions` | 코드의 권한 카탈로그를 DB에 반영 |
| `sail artisan i18n:missing` | 언어별 번역 누락 확인. `i18n:export`, `i18n:import`로 번역가와 주고받기 |
| `sail artisan naming:check` | 마이그레이션 테이블/컬럼 이름 검사 |
| `sail artisan pdf:sample` | 다국어 PDF 렌더링 확인 |
| `sail artisan pii:reencrypt {Model}` | 개인정보 키 교체 후 재암호화 (큐 배치) |

## 운영 DB 준비

슈퍼유저가 한 번 실행한다.

```bash
psql -v migrator_password=... -v runtime_password=... -v reporting_password=... \
     -f database/sql/roles/create_roles.sql
psql -d <database> -f database/sql/roles/prepare_database.sql
```

애플리케이션은 `DB_USERNAME=app_runtime`, 마이그레이션은 `DB_MIGRATOR_USERNAME=app_migrator`로 접속한다. 자세한 내용은 [docs/decisions/0002-database-roles-schemas-audit.md](docs/decisions/0002-database-roles-schemas-audit.md).
