# 25. Bilik Darjah Pensyarah dan Kehadiran

Menu: pensyarah menggunakan **Pengajaran saya > Senarai pelajar dan kehadiran** dalam portal, pelajar menggunakan **Kehadiran** dalam portal. Kakitangan menggunakan **Sesi kelas** di bahagian bawah skrin **Kurikulum dan seksyen > Seksyen > Sunting**.

| Siapa | Apa yang boleh dilakukan |
|---|---|
| Pensyarah yang ditetapkan | Senarai pelajar (dengan foto) dan CSV, PDF borang kehadiran, menjana, menambah dan membatalkan sesi kelas, memasukkan kehadiran |
| Kakitangan dengan `Rancang seksyen (pensyarah, waktu, bilik)` (dalam skop) | Sama seperti pensyarah + sesi kelas dalam panel pentadbiran |
| Kakitangan dengan `Lihat seksyen` (dalam skop) | Senarai pelajar, borang kehadiran, melihat kehadiran |
| Pelajar | Kehadiran mereka sendiri |

Ciri bilik darjah seperti pengumuman, tugasan dan bahan diserahkan kepada LMS (pelan pemindahan bahagian 12).

## Perbezaan daripada sistem lama

- **Sistem lama (YUSTIC) tidak merekod kehadiran.** "Borang kehadiran"nya ialah PDF yang mencetak kotak kosong (27 hingga 34 kotak, berbeza mengikut salinan) di sebelah senarai pelajar, tanpa tarikh dan tanpa jumlah. Sistem baharu mencipta **sesi kelas** daripada waktu kelas, merekod hadir, lewat, tidak hadir atau dikecualikan bagi setiap sesi kelas, dan mencetak rekod serta jumlah pada PDF borang kehadiran.
- **Senarai pelajar.** Skrin pensyarah sistem lama hanya memaparkan nombor matrik dan nama (serta mendedahkan nombor pendaftaran penduduk dan alamat dalam tetingkap timbul) dan tiada muat turun Excel. Sistem baharu hanya memaparkan nombor matrik, nama, foto, program, tahun pengajian dan e-mel hubungan, dan dimuat turun sebagai CSV. Muat turun direkodkan dalam log audit.
- **Akses pensyarah kepada pelajar mereka.** Dasar RLS ditambah supaya pensyarah dapat melihat rekod pelajar dan maklumat individu bagi pelajar dalam seksyen mereka (sebelum ini, mereka hanya dapat melihat pelajar seliaan mereka, menyebabkan nama kosong walaupun pada skrin kemasukan gred).

## Sesi kelas

- **Jana**: satu sesi kelas bagi setiap kejadian waktu kelas mingguan (hari, masa, slot masa). Pensyarah menggunakan butang **Jana daripada waktu kelas**; kakitangan menggunakan **Sesi kelas > Jana sesi kelas** dalam panel pentadbiran atau arahan `attendance:generate-sessions {term}` (seluruh semester). Sesi kelas dan kehadiran sedia ada tidak diusik, jadi ia boleh dijalankan semula selepas waktu kelas berubah.
- **Tambah sesi kelas**: kelas di luar waktu kelas, seperti kelas ganti. Hanya tarikh dalam tempoh sesi.
- **Batal**: membatalkan sesi kelas dengan sebab. Sesi kelas kekal tetapi dikecualikan daripada jumlah. **Pulihkan** mengembalikannya.
- Tiada kalendar cuti. Batalkan sesi kelas pada hari cuti umum (lihat bahagian 13.2; ini akan menjadi automatik setelah kalendar semester ditambah).

## Memasukkan kehadiran

Di bawah **Kehadiran** bagi sesi kelas, pilih hadir, lewat, tidak hadir atau dikecualikan bagi setiap pelajar dan tambah catatan (200 aksara). Cara terpantas ialah **Semua hadir** dan kemudian mengubah pengecualian.

- Kehadiran boleh dimasukkan mulai hari sesi kelas (bukan bagi sesi kelas akan datang).
- Menyimpan semula hanya merekod pelajar yang berubah, dan peristiwa audit (`attendance.recorded`) menyimpan nilai lama dan baharu.
- Mengosongkan status mengembalikan pelajar kepada "tidak direkodkan".

## Jumlah

| Item | Takrifan |
|---|---|
| Sesi kelas diadakan | Sesi kelas yang tidak dibatalkan dan jatuh pada atau sebelum hari ini |
| Kadar kehadiran | (hadir + lewat + dikecualikan) ÷ sesi kelas diadakan × 100 |
| Tidak direkodkan | Sesi kelas diadakan tanpa rekod |

Tiada peraturan yang menukar bilangan tanda lewat kepada tidak hadir atau memberikan F apabila ketidakhadiran melebihi satu pertiga. Ia berbeza antara universiti, jadi pensyarah sendiri mencerminkan kehadiran dalam gred (item semakan 13.5).

## PDF borang kehadiran dan CSV senarai pelajar

Kedua-dua muat turun direkodkan dalam log audit (`attendance.sheet_exported`; CSV senarai pelajar seperti sebelumnya). Nilai CSV yang bermula dengan `=`, `+`, `-` atau `@` dieksport dengan tanda petik tunggal di hadapan supaya ia tidak dijalankan sebagai formula.

- **Borang kehadiran (PDF)**: A4 landskap. Pelajar ke bawah, sesi kelas melintang (yang dibatalkan dikecualikan), tanda O/△/X/E dan jumlah bagi setiap pelajar. Sesi kelas yang akan datang dibiarkan kosong, jadi ia juga boleh digunakan untuk merekod secara manual.
- **Senarai pelajar (CSV)**: nombor matrik, nama, program, tahun pengajian, e-mel hubungan, status pendaftaran. UTF-8 dengan BOM, jadi ia terus dibuka dalam Excel. Setiap muat turun direkodkan dalam peristiwa audit `attendance.roster_exported`.

## Portal pelajar

**Kehadiran**: bagi setiap semester dan kursus, bilangan hadir, lewat, tidak hadir dan dikecualikan, kadar kehadiran, serta rekod bagi setiap sesi kelas (hanya yang dimasukkan oleh pensyarah).

## Prosedur operasi

1. Sebelum semester bermula, muktamadkan waktu kelas bagi seksyen ([bab 18](18-sections-and-timetables.md)).
2. Cipta sesi kelas dengan `php artisan attendance:generate-sessions {term} --actor=...` (pensyarah juga boleh menciptanya pada skrin).
3. Jalankan semula apabila waktu kelas berubah. Sesi kelas bagi waktu yang dialih keluar tidak dipadam secara automatik; batalkannya.
