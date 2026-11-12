# Rejalashtirilgan vazifalar va navbatlar

- Kim uchun: IT operatorlari

## Uzoq ishlaydigan jarayonlar

| Jarayon | Buyruq | Tekshirish |
|---|---|---|
| Navbat ishlovchisi | `php artisan horizon` | `/horizon` (ruxsat `queue_monitor.view`) |
| Rejalashtiruvchi | cron `* * * * * php artisan schedule:run` | `php artisan schedule:list` |

## Rejalashtirilgan vazifalar

Har bir bosqich ushbu jadvalga qoʻshimcha kiritadi. Roʻyxatdan oʻtkazishlar `routes/console.php` da joylashgan. Vaqtlar server vaqt mintaqasida (UTC).

| Vazifa | Davriylik | Bosqich | Nosozlikni aniqlash |
|---|---|---|---|
| `admission:expire-drafts` | Har kuni 03:10 da | 3 | Har bir ishga tushirishda faoliyat jurnaliga (log `command`) yoziladi. Bir kundan ortiq yozuv boʻlmasa, rejalashtiruvchini tekshiring |
| `registration:release-reserved-seats` | Har 5 daqiqada | 5 | Audit yozuvi yoʻq. Boʻshatish vaqti oʻtgan oʻrin taqsimotlarida `release_processed_at` hali ham boʻsh boʻlsa, rejalashtiruvchini tekshiring |
| `waitlist:promote` | Har soatda | 5 | Har bir ishga tushirishda faoliyat jurnaliga (log `command`) yoziladi |

## Navbat vazifalari

| Vazifa | Nima ishga tushiradi | Izohlar |
|---|---|---|
| `ReencryptPiiRecords` | `pii:reencrypt` | Paket (batch). Muvaffaqiyatsiz vazifalarni Horizon dan qayta urinib koʻring |
| `PromoteWaitlistJob` | Fandan chiqish yoki voz kechish, oʻrin taqsimotini boʻshatish | Har bir fan guruhi uchun faqat bittasi ishlaydi (takrorlanishning oldi olinadi). Kutish roʻyxatidan oʻtkazishda barcha qoidalar qayta tekshiriladi |
| `CloseTermRecordsJob`, `FinishTermCloseJob` | `term:close`, boshqaruv panelida semestrni yopish | 100 talabadan iborat paketlar, ulardan keyin yakunlovchi vazifa (imtiyozlar reytingi, semestr holatini oʻzgartirish). Jarayonni Horizon dagi `term-close:{term code}` paketida kuzating. Muvaffaqiyatsiz vazifalarni Horizon dan qayta urinib koʻring (har bir talaba boʻyicha hisob-kitoblar qayta bajarilganda bir xil natija beradi) |
| `ImportCsv` (Filament) | Boshqaruv panelidagi CSV import, `reference:import` | Paket (batch). Tugallanganligi boshqaruv paneli bildirishnomasi sifatida xabar qilinadi (`notifications` jadvali). Muvaffaqiyatsiz qatorlar `failed_import_rows` ga tushadi |
| `audit:seal` | Har kuni 03:30 da | Audit xesh zanjirini muhrlaydi ([Audit yaxlitligi](audit-and-maintenance.md)) |
| Rejalashtiruvchi yurak urishi | Har daqiqada | Oxirgi ishga tushirish vaqtini keshga yozadi. `system:health-check` 5 daqiqadan ortiq yozuv boʻlmasligini nosozlik deb hisoblaydi |

Navbat vazifasi bajarilayotganda MB ijrochisi `system:job:{class}` sifatida qayd etiladi.
