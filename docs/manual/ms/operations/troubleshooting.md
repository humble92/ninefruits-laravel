# Penyelesaian masalah

- Pembaca: pengendali IT

| Gejala | Perkara yang perlu disemak |
|---|---|
| Dialihkan ke halaman keselamatan sejurus selepas log masuk | Dasar pengesahan dua faktor ialah "wajib" dan tempoh tangguh telah berlalu. Selesai setelah pengguna mendaftar. Dasar ini terdapat di bawah **Tetapan > Keselamatan** |
| 403 dalam panel pentadbiran | Pengguna tiada penetapan peranan yang sah dengan kebenaran `admin_panel.access`. Semak **Pengguna dan akses > Pengguna > Penetapan peranan** |
| Item menu tidak kelihatan | Peranan tiada kebenaran untuk skrin itu (contohnya `roles.view`) |
| Kod e-mel tidak diterima | Tetapan e-mel (`MAIL_*`), entri `Kod e-mel dihantar` dalam peristiwa keselamatan, had penghantaran (sekali seminit, lima kali sejam) |
| Skrin menunjukkan 0 baris data individu lain | Mungkin normal (keselamatan peringkat baris). Pastikan pelaku sesi pangkalan data ditetapkan dengan melihat lajur pengguna dalam **Perubahan data** |
| `permission denied for schema public` | Aplikasi cuba menjalankan DDL sebagai `app_runtime`. Jalankan migrasi hanya dengan `php artisan migrate` (yang menggunakan `app_migrator` secara automatik) |
| `Audit records are append-only` | Percubaan untuk mengemas kini atau memadam jadual audit. Sekatan ini memang disengajakan |
| Penjanaan PDF gagal | `php artisan pdf:sample`, status Gotenberg, `GOTENBERG_URL` |
| Tugas baris gilir tersekat | Semak tugas yang gagal dalam `/horizon`, `php artisan horizon:status` |

Pengesanan bagi setiap permintaan: dengan nilai pengepala respons `X-Request-Id` anda boleh mencari log, log aktiviti, perubahan data dan peristiwa keselamatan sekali gus.
