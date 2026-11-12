# Integriti audit dan penyelenggaraan berkala

## Rantaian hash audit

Jadual audit (`audit.record_changes` untuk perubahan data, `audit.activity_log` untuk aktiviti urusan, `audit.security_events` untuk peristiwa keselamatan) hanya membenarkan penambahan baris (dikuatkuasakan oleh trigger). Selain itu, terdapat mekanisme yang **mendedahkan** sebarang pengubahan tanpa kebenaran.

- Setiap baris menerima SHA-256 bagi kandungannya (`row_hash`) semasa ia dimasukkan.
- `audit:seal` (dijadualkan setiap hari pada 03:30) memautkan baris baharu ke dalam rantaian mengikut susunan ID: `chain_hash = SHA-256(chain_hash sebelumnya || row_hash)`.
- `audit:verify-chain` mengira semula hash kandungan dan rantaian bagi setiap baris. Baris yang kandungannya berubah dilaporkan sebagai "row mismatch", baris yang hilang atau bertukar susunan sebagai "chain mismatch", dan arahan itu memulangkan kod keluar 2.

Perkara yang perlu diingat

- Baris yang diubah sebelum dimeterai (dalam hari yang sama) tidak dapat dikesan oleh rantaian. Oleh sebab itu pemeteraian dijalankan setiap hari dan pengesahan dijalankan sebelum sandaran serta semasa semakan keselamatan.
- Apabila pemangkasan (`audit:prune`) membuang baris yang paling lama, pautan baris pertama yang tinggal tidak disemak (julat yang dipangkas disimpan dalam rekod audit arahan pemangkasan).
- Hanya pentadbir pangkalan data (pemilik skema) boleh menetapkan `app.audit_maintenance = on`. Peranan aplikasi tidak mempunyai keistimewaan UPDATE atau DELETE.

## Tempoh simpanan dan pemangkasan

1. Tentukan tempoh simpanan (contohnya perubahan data 3 tahun, aktiviti urusan 5 tahun, peristiwa keselamatan 1 tahun).
2. Buat sandaran sebelum memangkas ([Sandaran dan pemulihan](backup-restore.md)) dan pastikan rantaian utuh dengan `audit:verify-chain`.
3. Semak bilangan baris dengan `audit:prune --before=YYYY-MM-DD --table=record_changes --dry-run`, kemudian jalankannya tanpa `--dry-run`. Baris yang belum dimeterai tidak sekali-kali dipadam, jadi jalankan `audit:seal` terlebih dahulu.
4. Jalankan `audit:verify-chain` sekali lagi selepas pemangkasan.

Pemangkasan itu sendiri direkodkan: setiap larian menulis ke log aktiviti (log `command`) tarikh potong, pilihan, pelaku dan pengguna OS, serta bagi setiap jadual **bilangan baris yang dipadam, julat ID yang dipadam dan chain_hash di sempadan**; pemadaman sebenar juga direkodkan sebagai peristiwa keselamatan `audit.pruned` (**Audit > Peristiwa keselamatan**). Baris rekod ini turut dipautkan ke dalam rantaian pada pemeteraian seterusnya. Sempadan inilah sebabnya `audit:verify-chain` tidak menyemak pautan baris pertama yang tinggal; dengan membandingkan hash sempadan dengan rekod pemangkasan, anda boleh mengesahkan bahawa julat yang dipadam sepadan dengan pemangkasan yang direkodkan.

## Pembersihan fail

`media:prune-orphans --dry-run` menyenaraikan baris media yang pemiliknya sudah tiada dan direktori tanpa baris; tanpa `--dry-run` ia memadamkannya. Ia meliputi foto individu, dokumen permohonan, PDF sijil dan bukti peperiksaan. Jalankannya setiap bulan atau selepas pemadaman pukal.

## Pemeriksaan kesihatan

`system:health-check` memeriksa konfigurasi dan kunci, peranan pangkalan data dan RLS, migrasi, katalog kebenaran, Redis dan baris gilir (Horizon), Gotenberg, storan dan denyutan penjadual (ditulis ke cache setiap minit), dan memulangkan kod keluar 2 apabila terdapat masalah. Gunakan `--json` untuk menyambungkannya ke sistem pemantauan.
