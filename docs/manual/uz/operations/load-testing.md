# Yuklama testi (fanlarga yozilish choʻqqisi)

Fanlarga yozilishning birinchi ertalabidagi kabi koʻplab talabalar bir vaqtda bir xil fan guruhlariga yopirilib kelgan vaziyatni k6 yordamida qayta yaratadi. Skript: `tests/load/registration.js`.

## Tayyorgarlik

1. Katta hajmdagi demo maʼlumotlar toʻplamini yarating va talabalar hisoblarini biriktiring (hisoblar roʻyxati CSV fayli `storage/app/private/load-accounts.csv` ga yoziladi).

   ```bash
   ./vendor/bin/sail artisan setup:demo --size=large --seed=42 --student-accounts
   ```

2. Fanlarga yozilish muddati ochiq boʻlishi kerak (demo joriy semestrdagi mashgʻulotlar davrining fanlarga yozilish muddatini ochadi).
3. Navbat ishlovchisi (Horizon) va Redis ishlab turishi kerak. Oʻrinlarni band qilish va kutish roʻyxatidan oʻtkazish navbat orqali bajariladi.

## Ishga tushirish

```bash
docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
    --vus 200 --duration 2m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/private/load-accounts.csv tests/load/registration.js
```

- `--vus` ni 100 dan 200 ga, soʻngra 500 ga oshiring va `http_req_duration` ning p95 qiymatini hamda xatolar ulushini kuzating. Maqsadlar: fanlarga yozilish ekrani p95 < 800ms, fanlarga yozilish soʻrovi p95 < 1.5s, xatolar ulushi < 1%.
- Server tomonida `pg_stat_activity` dagi kutayotgan (qulf) sessiyalar sonini, Horizon dagi qayta ishlash kechikishini va php-fpm ishlovchilarining toʻyinishini ham kuzating.
- Sail ga qarshi ishga tushirganda oʻrnatilgan serverning ishlovchilar sonini oshiring (`.env` dagi `PHP_CLI_SERVER_WORKERS`, standart qiymati 4). Ishlovchilar soni oʻtkazuvchanlikni cheklaydi.

## Loyiha nimani kafolatlaydi

- Sigʻim MB qator qulflari (`SeatAllocator::lock`) bilan himoyalangan, shuning uchun bir vaqtdagi soʻrovlar toʻlqini ham fan guruhiga ortiqcha yozilishga olib kela olmaydi (`ConcurrentRegistrationTest`).
- Oʻrinlarni band qilish Redis yoki navbatsiz, faqat MB ning oʻzida toʻgʻri ishlaydi, ammo kutish roʻyxatidan oʻtkazish va bildirishnomalar navbat orqali bajariladi.

## Octane boʻyicha qaror

Agar Sail ostidagi bitta php-fpm da p95 maqsadlardan oshsa, avval php-fpm ishlovchilari sonini va PostgreSQL `max_connections` yoki PgBouncer ni sozlang. Shunda ham yetarli boʻlmasa, Laravel Octane (FrankenPHP yoki Swoole) ni koʻrib chiqing. Octane ga oʻtishdan oldin tekshiring: har bir soʻrovda tiklanishi kerak boʻlgan holat (`DatabaseSessionContext` ning sessiya oʻzgaruvchilari soʻrov oxirida `reset()` orqali qaytariladi, shuning uchun Octane ostida ham xavfsiz), singletonlarda qolib ketgan foydalanuvchi maʼlumotlari, statik keshlar. Qaror haqiqiy yuklama koʻrsatkichlarini koʻrib chiqqandan soʻng Phase 13 hisobotiga yoziladi.

## 2026-10-23 natijalari

Ishlab chiqish uchun Sail konteyneri (oʻrnatilgan server, opcache yoʻq), bir daqiqa davomida 100 VU: 4 ishlovchi bilan 24.7 req/s, 16 ishlovchi bilan 34.5 req/s, 0 HTTP xato, 0 ortiqcha yozilish, 4 PostgreSQL ulanishi. Bitta soʻrovga ketadigan server vaqti sahifa uchun taxminan 120 ms, fanga yozilish uchun 190 ms; 100 VU dagi bir necha soniyalik kechikish ishlovchilar navbatidan kelib chiqadi. Oʻtkazuvchanlik PHP ishlovchilari soniga mutanosib oʻsadi va maʼlumotlar bazasi oʻz chegarasidan juda uzoq, shuning uchun **Octane qoʻllanilmaydi**: php-fpm ishlovchilari soni va ilova serverlarini gorizontal kengaytirish yetarli. Ishga tushirishdan oldin php-fpm + opcache staging muhitida testni 200 va 500 VU bilan takrorlang. Toʻliq raqamlar: [Phase 13 hisoboti](../../../../porting/phase-13-report.md), 7-boʻlim.
