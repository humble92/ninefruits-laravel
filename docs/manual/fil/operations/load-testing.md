# Load testing (rurok ng pagpapatala)

Ginagaya gamit ang k6 ang sitwasyon kung saan maraming mag-aaral ang sabay-sabay na dumadagsa sa parehong mga seksiyon, gaya sa unang umaga ng pagpapatala. Ang script ay `tests/load/registration.js`.

## Paghahanda

1. Bumuo ng malaking set ng demo data at lagyan ng mga account ng mag-aaral (isinusulat ang CSV ng listahan ng account sa `storage/app/private/load-accounts.csv`).

   ```bash
   ./vendor/bin/sail artisan setup:demo --size=large --seed=42 --student-accounts
   ```

2. Dapat bukas ang isang takdang panahon ng pagpapatala (binubuksan ng demo ang takdang panahon ng pagpapatala ng sesyon ng kasalukuyang termino).
3. Dapat tumatakbo ang queue worker (Horizon) at Redis. Dumadaan sa queue ang pagreserba ng upuan at promosyon mula sa waitlist.

## Pagpapatakbo

```bash
docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
    --vus 200 --duration 2m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/private/load-accounts.csv tests/load/registration.js
```

- Itaas ang `--vus` mula 100 hanggang 200 hanggang 500 at bantayan ang p95 ng `http_req_duration` at ang failure rate. Mga target: p95 ng screen ng pagpapatala < 800ms, p95 ng request sa pagpapatala < 1.5s, failure rate < 1%.
- Sa panig ng server, bantayan din ang bilang ng mga naghihintay na session (lock) sa `pg_stat_activity`, ang pagkaantala ng pagproseso sa Horizon at ang saturation ng mga php-fpm worker.
- Kapag tumatakbo laban sa Sail, itaas ang bilang ng worker ng built-in server (`PHP_CLI_SERVER_WORKERS` sa `.env`, default 4). Nililimitahan ng bilang ng worker ang throughput.

## Ano ang ginagarantiya ng disenyo

- Pinoprotektahan ang kapasidad ng mga row lock ng DB (`SeatAllocator::lock`), kaya kahit ang biglang dagsa ng sabay-sabay na request ay hindi makapag-o-over-enroll ng seksiyon (`ConcurrentRegistrationTest`).
- Tama ang pagreserba ng upuan sa DB lamang, kahit walang Redis o queue, ngunit ang promosyon mula sa waitlist at ang mga abiso ay hinahawakan ng queue.

## Pagpapasya tungkol sa Octane

Kung lumampas ang p95 sa mga target sa iisang php-fpm sa ilalim ng Sail, ayusin muna ang bilang ng mga php-fpm worker at ang `max_connections` ng PostgreSQL o PgBouncer. Kung hindi pa rin sapat, isaalang-alang ang Laravel Octane (FrankenPHP o Swoole). Bago lumipat sa Octane, suriin: ang state na dapat i-reset bawat request (ibinabalik ng `reset()` sa dulo ng request ang mga session variable ng `DatabaseSessionContext`, kaya ligtas din ang mga ito sa ilalim ng Octane), datos ng user na naiwan sa mga singleton, mga static cache. Isinusulat ang pasya sa ulat ng Phase 13 pagkatapos tingnan ang tunay na mga numero ng load.

## Mga resulta noong 2026-10-23

Development Sail container (built-in server, walang opcache), 100 VU sa loob ng isang minuto: 24.7 req/s sa 4 na worker, 34.5 req/s sa 16 na worker, 0 HTTP failure, 0 over-enrollment, 4 na koneksiyon sa PostgreSQL. Ang oras ng server bawat request ay humigit-kumulang 120 ms para sa pahina at 190 ms para sa isang pagpapatala; ang ilang segundong latency sa 100 VU ay dahil sa pila ng mga worker. Lumalaki ang throughput kasabay ng bilang ng mga PHP worker at malayo pa sa limitasyon nito ang database, kaya **hindi gagamitin ang Octane**: sapat na ang bilang ng php-fpm worker at ang horizontal scaling ng mga app server. Ulitin ang pagpapatakbo sa 200 at 500 VU sa isang staging environment na may php-fpm + opcache bago ang go-live. Buong mga numero: seksiyon 7 ng [ulat ng Phase 13](../../../../porting/phase-13-report.md).
