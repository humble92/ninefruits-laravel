# Tugas berjadual dan baris gilir

- Pembaca: pengendali IT

## Proses yang berjalan berterusan

| Proses | Arahan | Semakan |
|---|---|---|
| Pekerja baris gilir | `php artisan horizon` | `/horizon` (kebenaran `queue_monitor.view`) |
| Penjadual | cron `* * * * * php artisan schedule:run` | `php artisan schedule:list` |

## Tugas berjadual

Setiap fasa menambah ke jadual ini. Pendaftarannya terdapat dalam `routes/console.php`. Masa adalah dalam zon waktu pelayan (UTC).

| Tugas | Kekerapan | Fasa | Pengesanan kegagalan |
|---|---|---|---|
| `admission:expire-drafts` | Setiap hari pada 03:10 | 3 | Direkodkan dalam log aktiviti (log `command`) pada setiap larian. Jika tiada rekod lebih daripada sehari, semak penjadual |
| `registration:release-reserved-seats` | Setiap 5 minit | 5 | Tiada rekod audit. Jika peruntukan tempat duduk yang masa pelepasannya telah berlalu masih mempunyai `release_processed_at` yang kosong, semak penjadual |
| `waitlist:promote` | Setiap jam | 5 | Direkodkan dalam log aktiviti (log `command`) pada setiap larian |

## Tugas baris gilir

| Tugas | Dicetuskan oleh | Catatan |
|---|---|---|
| `ReencryptPiiRecords` | `pii:reencrypt` | Kelompok. Cuba semula tugas yang gagal daripada Horizon |
| `PromoteWaitlistJob` | Gugur atau tarik diri daripada pendaftaran, pelepasan peruntukan tempat duduk | Hanya satu berjalan bagi setiap seksyen (pendua dihalang). Kenaikan senarai menunggu menyemak semula setiap peraturan |
| `CloseTermRecordsJob`, `FinishTermCloseJob` | `term:close`, tutup semester dalam panel pentadbiran | Kelompok 100 pelajar, diikuti oleh tugas penyudah (kedudukan Senarai Dekan, perubahan status semester). Ikuti kemajuan dalam kelompok Horizon `term-close:{term code}`. Cuba semula tugas yang gagal daripada Horizon (pengiraan bagi setiap pelajar memberikan keputusan yang sama apabila dijalankan semula) |
| `ImportCsv` (Filament) | Import CSV dalam panel pentadbiran, `reference:import` | Kelompok. Penyiapan diumumkan sebagai pemberitahuan panel pentadbiran (jadual `notifications`). Baris yang gagal masuk ke `failed_import_rows` |
| `audit:seal` | Setiap hari pada 03:30 | Memeterai rantaian hash audit ([Integriti audit](audit-and-maintenance.md)) |
| Denyutan penjadual | Setiap minit | Menulis masa larian terakhir ke cache. `system:health-check` menganggap lebih daripada 5 minit tanpanya sebagai kegagalan |

Semasa tugas baris gilir berjalan, pelaku pangkalan data direkodkan sebagai `system:job:{class}`.
