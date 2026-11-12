# Zamanlanmış işler ve kuyruklar

- Hedef kitle: BT operatörleri

## Sürekli çalışan süreçler

| Süreç | Komut | Kontrol |
|---|---|---|
| Kuyruk çalışanı | `php artisan horizon` | `/horizon` (yetki `queue_monitor.view`) |
| Zamanlayıcı | cron `* * * * * php artisan schedule:run` | `php artisan schedule:list` |

## Zamanlanmış işler

Her aşama bu tabloya ekleme yapar. Kayıtlar `routes/console.php` dosyasındadır. Saatler sunucu saat dilimindedir (UTC).

| İş | Sıklık | Aşama | Hata tespiti |
|---|---|---|---|
| `admission:expire-drafts` | Her gün 03:10'da | 3 | Her çalıştırmada işlem günlüğüne (günlük `command`) kaydedilir. Bir günden uzun süre kayıt yoksa zamanlayıcıyı kontrol edin |
| `registration:release-reserved-seats` | 5 dakikada bir | 5 | Denetim kaydı yoktur. Serbest bırakma zamanı geçmiş ayrılmış kontenjanların `release_processed_at` alanı hâlâ boşsa zamanlayıcıyı kontrol edin |
| `waitlist:promote` | Saatte bir | 5 | Her çalıştırmada işlem günlüğüne (günlük `command`) kaydedilir |

## Kuyruk işleri

| İş | Tetikleyen | Notlar |
|---|---|---|
| `ReencryptPiiRecords` | `pii:reencrypt` | Toplu iş. Başarısız işleri Horizon'dan yeniden deneyin |
| `PromoteWaitlistJob` | Dersin bırakılması veya dersten çekilme, ayrılmış kontenjanın serbest bırakılması | Şube başına yalnızca bir tane çalışır (yinelenenler engellenir). Bekleme listesinden kayıt tüm kuralları yeniden kontrol eder |
| `CloseTermRecordsJob`, `FinishTermCloseJob` | `term:close`, yönetim panelinde dönemin kapatılması | 100 öğrencilik toplu işler, ardından tamamlama işi (onur derecesi sıralaması, dönem durumunun değişmesi). İlerlemeyi Horizon'daki `term-close:{dönem kodu}` toplu işinden izleyin. Başarısız işleri Horizon'dan yeniden deneyin (öğrenci bazındaki hesaplamalar yeniden çalıştırıldığında aynı sonucu verir) |
| `ImportCsv` (Filament) | Yönetim panelinde CSV içe aktarma, `reference:import` | Toplu iş. Tamamlanma, yönetim paneli bildirimi olarak duyurulur (`notifications` tablosu). Başarısız satırlar `failed_import_rows` tablosuna yazılır |
| `audit:seal` | Her gün 03:30'da | Denetim karma zincirini mühürler ([Denetim bütünlüğü](audit-and-maintenance.md)) |
| Zamanlayıcı nabzı | Her dakika | Son çalışma zamanını önbelleğe yazar. `system:health-check`, 5 dakikadan uzun süre kayıt olmamasını hata olarak değerlendirir |

Bir kuyruk işi çalışırken DB'deki işlemi yapan kişi `system:job:{class}` olarak kaydedilir.
