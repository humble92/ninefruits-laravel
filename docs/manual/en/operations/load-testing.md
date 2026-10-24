# Load testing (registration peak)

Reproduces with k6 the situation where many students rush the same sections at once, as on the first morning of registration. The script is `tests/load/registration.js`.

## Preparation

1. Generate a large demo data set and attach student accounts (the account list CSV is written to `storage/app/private/load-accounts.csv`).

   ```bash
   ./vendor/bin/sail artisan setup:demo --size=large --seed=42 --student-accounts
   ```

2. A registration window must be open (the demo opens the registration window of the current term's session).
3. The queue worker (Horizon) and Redis must be running. Seat reservation and waitlist promotion run through the queue.

## Running

```bash
docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
    --vus 200 --duration 2m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/private/load-accounts.csv tests/load/registration.js
```

- Raise `--vus` from 100 to 200 to 500 and watch the `http_req_duration` p95 and the failure rate. Targets: registration screen p95 < 800ms, registration request p95 < 1.5s, failure rate < 1%.
- On the server side, also watch the number of waiting (lock) sessions in `pg_stat_activity`, Horizon processing delay and php-fpm worker saturation.
- When running against Sail, raise the built-in server's worker count (`PHP_CLI_SERVER_WORKERS` in `.env`, default 4). The worker count caps the throughput.

## What the design guarantees

- Capacity is protected by DB row locks (`SeatAllocator::lock`), so even a burst of concurrent requests cannot over-enroll a section (`RegistrationConcurrencyTest`).
- Seat reservation is correct in the DB alone, without Redis or the queue, but waitlist promotion and notifications are handled by the queue.

## Deciding on Octane

If the p95 exceeds the targets on a single php-fpm under Sail, first tune the number of php-fpm workers and PostgreSQL `max_connections` or PgBouncer. If that is still not enough, consider Laravel Octane (FrankenPHP or Swoole). Before switching to Octane, check: state that must be reset per request (the session variables of `DatabaseSessionContext` are reverted by `reset()` at the end of the request, so they are safe under Octane too), user data left in singletons, static caches. The decision is written into the Phase 13 report after looking at real load figures.

## Results of 2026-10-23

Development Sail container (built-in server, no opcache), 100 VUs for one minute: 24.7 req/s with 4 workers, 34.5 req/s with 16 workers, 0 HTTP failures, 0 over-enrollments, 4 PostgreSQL connections. Server time per request is about 120 ms for the page and 190 ms for a registration; the multi-second latency at 100 VUs is worker queueing. Throughput scales with the number of PHP workers and the database is far from its limit, so **Octane is not adopted**: php-fpm worker count and horizontal scaling of app servers are enough. Repeat the run with 200 and 500 VUs on a php-fpm + opcache staging environment before go-live. Full numbers: [Phase 13 report](../../../porting/phase-13-report.md) section 7.
