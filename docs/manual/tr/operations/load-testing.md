# Yük testi (ders kaydı yoğunluğu)

Ders kaydının ilk sabahında olduğu gibi çok sayıda öğrencinin aynı anda aynı şubelere yüklendiği durumu k6 ile yeniden üretir. Betik `tests/load/registration.js` dosyasıdır.

## Hazırlık

1. Büyük bir demo veri kümesi oluşturun ve öğrenci hesaplarını ekleyin (hesap listesi CSV dosyası `storage/app/private/load-accounts.csv` konumuna yazılır).

   ```bash
   ./vendor/bin/sail artisan setup:demo --size=large --seed=42 --student-accounts
   ```

2. Bir ders kaydı işlem aralığı açık olmalıdır (demo, geçerli dönemin alt dönemi için ders kaydı işlem aralığını açar).
3. Kuyruk çalışanı (Horizon) ve Redis çalışıyor olmalıdır. Kontenjan ayırma ve bekleme listesinden kayıt kuyruk üzerinden yürütülür.

## Çalıştırma

```bash
docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
    --vus 200 --duration 2m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/private/load-accounts.csv tests/load/registration.js
```

- `--vus` değerini 100'den 200'e, oradan 500'e yükseltin ve `http_req_duration` p95 değerini ve hata oranını izleyin. Hedefler: ders kaydı ekranı p95 < 800ms, ders kaydı isteği p95 < 1.5s, hata oranı < %1.
- Sunucu tarafında ayrıca `pg_stat_activity` içindeki bekleyen (kilit) oturum sayısını, Horizon işleme gecikmesini ve php-fpm çalışanlarının doygunluğunu izleyin.
- Sail üzerinde çalıştırırken yerleşik sunucunun çalışan sayısını artırın (`.env` içinde `PHP_CLI_SERVER_WORKERS`, varsayılan 4). Çalışan sayısı işlem hacmini sınırlar.

## Tasarımın garanti ettikleri

- Kontenjan DB satır kilitleriyle korunur (`SeatAllocator::lock`); bu nedenle eşzamanlı istek patlaması bile bir şubede kontenjan aşımına yol açamaz (`ConcurrentRegistrationTest`).
- Kontenjan ayırma, Redis veya kuyruk olmadan yalnızca DB ile doğru çalışır; ancak bekleme listesinden kayıt ve bildirimler kuyruk tarafından işlenir.

## Octane kararı

Sail altında tek bir php-fpm üzerinde p95 hedefleri aşarsa, önce php-fpm çalışan sayısını ve PostgreSQL `max_connections` ayarını veya PgBouncer'ı ayarlayın. Bu yine de yetmezse Laravel Octane'i (FrankenPHP veya Swoole) değerlendirin. Octane'e geçmeden önce şunları kontrol edin: istek başına sıfırlanması gereken durum (`DatabaseSessionContext` oturum değişkenleri istek sonunda `reset()` ile geri alınır, bu nedenle Octane altında da güvenlidir), singleton'larda kalan kullanıcı verileri, statik önbellekler. Karar, gerçek yük rakamlarına bakıldıktan sonra Phase 13 raporuna yazılır.

## 2026-10-23 sonuçları

Geliştirme Sail konteyneri (yerleşik sunucu, opcache yok), bir dakika boyunca 100 VU: 4 çalışanla 24.7 req/s, 16 çalışanla 34.5 req/s, 0 HTTP hatası, 0 kontenjan aşımı, 4 PostgreSQL bağlantısı. İstek başına sunucu süresi sayfa için yaklaşık 120 ms, ders kaydı için 190 ms'dir; 100 VU'daki birkaç saniyelik gecikme çalışan kuyruğundan kaynaklanır. İşlem hacmi PHP çalışan sayısıyla ölçeklenir ve veritabanı sınırından çok uzaktadır; bu nedenle **Octane benimsenmemiştir**: php-fpm çalışan sayısı ve uygulama sunucularının yatay ölçeklenmesi yeterlidir. Canlıya geçişten önce php-fpm + opcache içeren bir hazırlık (staging) ortamında 200 ve 500 VU ile testi tekrarlayın. Tüm rakamlar: [Phase 13 raporu](../../../../porting/phase-13-report.md) 7. bölüm.
