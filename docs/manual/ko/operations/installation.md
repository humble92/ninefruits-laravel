# 설치

- 대상: IT 운영자
- 관련: [명령 레퍼런스](commands.md), [키 관리](security-keys.md)

## 1. 요구 사항

| 구성 요소 | 버전 | 비고 |
|---|---|---|
| PHP | 8.5 | 확장: `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | `btree_gist`, `pg_trgm` 확장(마이그레이션이 설치), ICU collation |
| Redis | 8 | 캐시, 세션, 큐 |
| Gotenberg | 8 | PDF 생성. 내부망에만 노출 |
| 메일 서버 | SMTP | 비밀번호 재설정, 2단계 인증 이메일 코드 |
| 큐 워커 | Horizon | `php artisan horizon`을 프로세스 관리자(systemd, supervisor)로 상시 실행 |
| 스케줄러 | cron | `* * * * * php artisan schedule:run` |

## 2. 데이터베이스 준비

DB 슈퍼유저가 한 번 실행한다. 애플리케이션은 슈퍼유저를 쓰지 않는다.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| 역할 | 환경 변수 | 용도 |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | 애플리케이션 실행 |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | 마이그레이션만 |
| `app_reporting` | (BI 도구에 설정) | `reporting` 스키마 조회 |

## 3. 환경 설정

`.env.example`을 복사해 값을 채운다. 반드시 바꿀 값

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY`: `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY`: `php artisan pii:generate-key`. **분실하면 암호화된 개인정보를 복구할 수 없다.** [키 관리](security-keys.md)에 따라 별도 보관한다.
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE`: 최초 설치 시 기관 시간대 기본값

## 4. 설치

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install`은 질문에 답하면서 진행한다. 배포 스크립트에서는 `--force`와 옵션으로 실행한다([명령 레퍼런스](commands.md#setupinstall)).

설치 프로파일(`--profile=`): `korean-university` — 1학기/2학기/계절학기, 4.5 성적 척도, 교과 구분, 09:00부터 50분 교시, 강의실 용도. 적용하지 않으면 관리 화면에서 직접 입력한다. 나중에 다시 적용해도 관리자가 바꾼 값은 유지된다.

설치 후 업무 관리자는 [관리자 매뉴얼 5장 초기 세팅 순서](../admin/05-institution-setup.md)에 따라 기준정보를 입력한다.

## 5. 설치 확인

1. `/login`에서 최초 관리자로 로그인한다.
2. 관리 화면 **설정 > 보안**에서 2단계 인증 정책을 정한다.
3. 관리자 본인의 2단계 인증을 등록한다(포털 **보안**).
4. `php artisan pdf:sample`로 PDF 서비스를 점검한다.
5. 관리 화면 **감사 > 데이터 변경 기록**에 설치 작업이 `system:command:setup:install`로 남았는지 확인한다.

## 6. 업데이트

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # 마이그레이션, 필수 데이터, 권한 동기화
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # 워커가 새 코드로 다시 시작
php artisan up
```

## 7. 웹 서버(Nginx)

php-fpm 앞에 Nginx를 둔다. 아래는 예시이며 주소, 경로, 인증서, php-fpm 소켓은 서버에 맞게 바꾼다.

```nginx
log_format sis '$remote_addr - $remote_user [$time_local] "$request" $status $body_bytes_sent '
               '"$http_referer" "$http_user_agent" rt=$request_time rid=$request_id';

server {
    listen 443 ssl;
    http2 on;
    server_name sis.example.edu;
    root /var/www/sis/public;
    index index.php;

    ssl_certificate     /etc/ssl/sis/fullchain.pem;
    ssl_certificate_key /etc/ssl/sis/privkey.pem;

    access_log /var/log/nginx/sis.access.log sis;
    client_max_body_size 32m;     # same as post_max_size in php.ini
    server_tokens off;

    # Only when PHP does not answer. The application's own error pages pass through.
    error_page 502 503 504 /errors/unavailable.html;
    location = /errors/unavailable.html {
        internal;
        ssi on;                    # puts $request_id on the page
        add_header Cache-Control "no-store" always;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param HTTP_X_REQUEST_ID $request_id;   # replaces a client-sent X-Request-Id
        internal;
    }

    location ~ \.php$ { return 404; }
    location ~ /\.(?!well-known) { deny all; }
}
```

- **오류 페이지:** `public/errors/unavailable.html`은 PHP가 응답하지 않을 때(502, 503, 504) Nginx가 보여 주는 정적 페이지다. 빌드 파일이나 DB 없이 뜨고, 문구는 브라우저 언어에 맞춰 바뀐다(지원하지 않는 언어는 영어). 앱이 직접 보낸 오류 화면(500, `php artisan down` 중의 503 등)은 `fastcgi_intercept_errors`가 기본값(off)이므로 그대로 전달된다.
- **참조 번호:** `ssi on`이 Nginx의 `$request_id`를 페이지에 넣는다. 같은 값을 `X-Request-Id`로 앱에 넘기므로 Nginx 접근 로그(`rid=`), 앱 로그, 감사 기록을 한 번호로 찾을 수 있다([문제 해결](troubleshooting.md)). 클라이언트가 보낸 `X-Request-Id`는 이 값으로 대체된다.
- **Nginx가 다른 앱 서버로 프록시할 때:** `proxy_set_header X-Request-Id $request_id;`를 쓰고 `proxy_intercept_errors`는 기본값(off)으로 둔다. `error_page`와 그 `location`은 그대로 쓴다.
- **문구를 바꿀 때:** `lang/*/errors.php`의 `unavailable`과 `reference`를 고치면 이 파일 안의 JSON도 함께 고친다. 둘이 어긋나면 테스트(`ErrorPagesTest`)가 실패한다.
