# Mga naka-iskedyul na job at queue

- Para kanino: mga IT operator

## Mga pangmatagalang proseso

| Proseso | Command | Pagsuri |
|---|---|---|
| Queue worker | `php artisan horizon` | `/horizon` (pahintulot na `queue_monitor.view`) |
| Scheduler | cron `* * * * * php artisan schedule:run` | `php artisan schedule:list` |

## Mga naka-iskedyul na job

Nagdaragdag ang bawat phase sa table na ito. Ang mga pagpaparehistro ay nasa `routes/console.php`. Ang mga oras ay nasa time zone ng server (UTC).

| Job | Dalas | Phase | Pagtukoy ng pagkabigo |
|---|---|---|---|
| `admission:expire-drafts` | Araw-araw nang 03:10 | 3 | Itinatala sa log ng aktibidad (log na `command`) sa bawat pagpapatakbo. Kung walang rekord nang higit sa isang araw, suriin ang scheduler |
| `registration:release-reserved-seats` | Bawat 5 minuto | 5 | Walang rekord ng audit. Kung ang mga nakareserbang upuan na lumipas na ang oras ng pagpapalaya ay may bakante pa ring `release_processed_at`, suriin ang scheduler |
| `waitlist:promote` | Bawat oras | 5 | Itinatala sa log ng aktibidad (log na `command`) sa bawat pagpapatakbo |

## Mga queue job

| Job | Pinapagana ng | Mga tala |
|---|---|---|
| `ReencryptPiiRecords` | `pii:reencrypt` | Batch. Ulitin ang mga bigong job mula sa Horizon |
| `PromoteWaitlistJob` | Pag-drop o pag-withdraw sa pagpapatala, pagpapalaya ng nakareserbang upuan | Isa lamang ang tumatakbo bawat seksiyon (pinipigilan ang mga duplicate). Sinusuri muli ng promosyon mula sa waitlist ang bawat panuntunan |
| `CloseTermRecordsJob`, `FinishTermCloseJob` | `term:close`, pagsasara ng termino sa panel ng administrasyon | Mga batch ng 100 mag-aaral, sinusundan ng job na nagtatapos (pagraranggo ng karangalan, pagbabago ng katayuan ng termino). Subaybayan ang progreso sa Horizon batch na `term-close:{term code}`. Ulitin ang mga bigong job mula sa Horizon (pareho ang resulta ng mga kalkulasyon bawat mag-aaral kapag pinatakbong muli) |
| `ImportCsv` (Filament) | Pag-import ng CSV sa panel ng administrasyon, `reference:import` | Batch. Inaanunsiyo ang pagkumpleto bilang abiso sa panel ng administrasyon (table na `notifications`). Napupunta ang mga bigong row sa `failed_import_rows` |
| `audit:seal` | Araw-araw nang 03:30 | Sine-seal ang hash chain ng audit ([Integridad ng audit](audit-and-maintenance.md)) |
| Heartbeat ng scheduler | Bawat minuto | Isinusulat sa cache ang huling oras ng pagpapatakbo. Itinuturing ng `system:health-check` na pagkabigo ang higit sa 5 minutong wala nito |

Habang tumatakbo ang isang queue job, itinatala ang actor ng DB bilang `system:job:{class}`.
