# Ujian beban (puncak pendaftaran kursus)

Menghasilkan semula dengan k6 situasi apabila ramai pelajar berebut seksyen yang sama serentak, seperti pada pagi pertama pendaftaran kursus. Skripnya ialah `tests/load/registration.js`.

## Penyediaan

1. Jana set data demo yang besar dan lampirkan akaun pelajar (senarai akaun dalam CSV ditulis ke `storage/app/private/load-accounts.csv`).

   ```bash
   ./vendor/bin/sail artisan setup:demo --size=large --seed=42 --student-accounts
   ```

2. Tempoh urusan pendaftaran kursus mesti dibuka (demo membuka tempoh pendaftaran kursus bagi sesi semester semasa).
3. Pekerja baris gilir (Horizon) dan Redis mesti berjalan. Tempahan tempat duduk dan kenaikan senarai menunggu dijalankan melalui baris gilir.

## Menjalankan

```bash
docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
    --vus 200 --duration 2m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/private/load-accounts.csv tests/load/registration.js
```

- Naikkan `--vus` daripada 100 ke 200 hingga 500 dan perhatikan p95 `http_req_duration` serta kadar kegagalan. Sasaran: p95 skrin pendaftaran kursus < 800ms, p95 permintaan pendaftaran < 1.5s, kadar kegagalan < 1%.
- Di sebelah pelayan, perhatikan juga bilangan sesi yang menunggu (kunci) dalam `pg_stat_activity`, kelewatan pemprosesan Horizon dan ketepuan pekerja php-fpm.
- Apabila dijalankan terhadap Sail, naikkan bilangan pekerja pelayan terbina dalam (`PHP_CLI_SERVER_WORKERS` dalam `.env`, lalai 4). Bilangan pekerja mengehadkan daya pemprosesan.

## Perkara yang dijamin oleh reka bentuk

- Kapasiti dilindungi oleh kunci baris pangkalan data (`SeatAllocator::lock`), jadi lonjakan permintaan serentak sekalipun tidak dapat menyebabkan seksyen melebihi kapasiti (`ConcurrentRegistrationTest`).
- Tempahan tempat duduk adalah betul dalam pangkalan data sahaja, tanpa Redis atau baris gilir, tetapi kenaikan senarai menunggu dan pemberitahuan dikendalikan oleh baris gilir.

## Membuat keputusan tentang Octane

Jika p95 melebihi sasaran pada satu php-fpm di bawah Sail, laraskan dahulu bilangan pekerja php-fpm dan `max_connections` PostgreSQL atau PgBouncer. Jika masih tidak mencukupi, pertimbangkan Laravel Octane (FrankenPHP atau Swoole). Sebelum beralih ke Octane, semak: keadaan yang mesti ditetapkan semula bagi setiap permintaan (pemboleh ubah sesi `DatabaseSessionContext` dipulihkan oleh `reset()` pada akhir permintaan, jadi ia selamat di bawah Octane juga), data pengguna yang tertinggal dalam singleton, cache statik. Keputusan ditulis dalam laporan Fasa 13 selepas melihat angka beban sebenar.

## Keputusan pada 2026-10-23

Kontena Sail pembangunan (pelayan terbina dalam, tanpa opcache), 100 VU selama satu minit: 24.7 req/s dengan 4 pekerja, 34.5 req/s dengan 16 pekerja, 0 kegagalan HTTP, 0 pendaftaran melebihi kapasiti, 4 sambungan PostgreSQL. Masa pelayan bagi setiap permintaan ialah kira-kira 120 ms untuk halaman dan 190 ms untuk satu pendaftaran kursus; kependaman beberapa saat pada 100 VU ialah giliran menunggu pekerja. Daya pemprosesan meningkat mengikut bilangan pekerja PHP dan pangkalan data masih jauh daripada hadnya, jadi **Octane tidak diguna pakai**: bilangan pekerja php-fpm dan penskalaan mendatar pelayan aplikasi sudah mencukupi. Ulang larian dengan 200 dan 500 VU pada persekitaran staging php-fpm + opcache sebelum operasi sebenar bermula. Angka penuh: [laporan Fasa 13](../../../../porting/phase-13-report.md) bahagian 7.
