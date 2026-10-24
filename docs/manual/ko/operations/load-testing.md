# 부하 테스트 (수강신청 피크)

수강신청 첫날 아침처럼 많은 학생이 같은 강좌에 동시에 몰리는 상황을 k6로 재현한다. 스크립트는 `tests/load/registration.js`.

## 준비

1. 데모 데이터를 크게 만들고 학생 계정을 붙인다(계정 목록 CSV가 `storage/app/private/load-accounts.csv`에 생긴다).

   ```bash
   ./vendor/bin/sail artisan setup:demo --size=large --seed=42 --student-accounts
   ```

2. 수강신청 기간이 열려 있어야 한다(데모는 이번 학기 세션의 수강신청 기간을 연다).
3. 큐 워커(Horizon)와 Redis가 떠 있어야 한다. 좌석 예약·대기자 승격이 큐로 돈다.

## 실행

```bash
docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
    --vus 200 --duration 2m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/private/load-accounts.csv tests/load/registration.js
```

- `--vus`를 100 → 200 → 500으로 올리며 `http_req_duration` p95와 실패율을 본다. 기준: 수강신청 화면 p95 < 800ms, 등록 p95 < 1.5s, 실패율 < 1%.
- 서버 쪽에서는 `pg_stat_activity`의 대기(lock) 수, Horizon 처리 지연, php-fpm 워커 포화를 함께 본다.
- Sail에서 돌릴 때는 내장 서버 워커 수를 올린다(`.env`의 `PHP_CLI_SERVER_WORKERS`, 기본 4). 워커 수가 처리량 상한이다.

## 설계상 보장되는 것

- 정원은 DB 행 잠금(`SeatAllocator::lock`)으로 지켜지므로 동시 요청이 몰려도 초과 등록은 나지 않는다(`RegistrationConcurrencyTest`).
- 좌석 예약은 Redis·큐 없이도 DB에서 맞지만, 대기자 승격과 알림은 큐가 처리한다.

## Octane 판단

Sail 단일 php-fpm 기준으로 p95가 기준을 넘으면 먼저 php-fpm 워커 수와 PostgreSQL `max_connections`·PgBouncer를 조정한다. 그래도 부족하면 Laravel Octane(FrankenPHP 또는 Swoole)을 검토한다. Octane 전환 전 확인할 것: 요청마다 초기화되어야 하는 상태(`DatabaseSessionContext`의 세션 변수는 요청 종료 시 `reset()`으로 돌아가므로 Octane에서도 안전), 싱글턴에 남는 사용자 데이터, 정적 캐시. 결정은 실제 부하 수치를 본 뒤 Phase 13 보고서에 적는다.

## 2026-10-23 측정 결과

개발용 Sail 컨테이너(내장 서버, opcache 없음)에서 100 VU로 1분: 워커 4개 24.7 req/s, 워커 16개 34.5 req/s, HTTP 실패 0, 초과 등록 0, PostgreSQL 연결 4개. 요청당 서버 처리 시간은 화면 약 120ms, 등록 약 190ms이고 100 VU에서의 지연은 워커 대기열이다. 처리량이 PHP 워커 수에 비례하고 DB가 병목이 아니므로 **Octane은 도입하지 않고** php-fpm 워커 수와 앱 서버 수평 확장으로 대응한다. 운영 전 php-fpm + opcache 스테이징에서 200·500 VU로 다시 측정한다. 자세한 수치는 [Phase 13 보고서](../../../porting/phase-13-report.md) 7절.
