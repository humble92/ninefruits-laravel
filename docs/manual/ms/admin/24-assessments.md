# 24. Peperiksaan

Menu: **Peperiksaan > Sesi peperiksaan**, **Keputusan peperiksaan**, **Jenis peperiksaan**. Pelajar mendaftar dan melihat keputusan mereka dalam portal di bawah **Peperiksaan**.

| Kebenaran | Apa yang dibenarkan |
|---|---|
| `Daftarkan pelajar, masukkan dan terbitkan keputusan, rekod keputusan luar` | Mendaftarkan, membatalkan dan menandakan pelajar tidak hadir, memasukkan keputusan, mengimport CSV, menerbitkan keputusan, merekod dan mengalih keluar keputusan luar, melihat daftar keputusan (dalam skop paparan rekod pelajar) |
| `Urus jenis peperiksaan, bahagian dan sesi` | Mencipta dan menyunting jenis, bahagian dan sesi |

Lalai peranan: Pegawai Pendaftar dan Pegawai Hal Ehwal Akademik mempunyai kedua-duanya.

## Perbezaan daripada sistem lama

- **Tiada lajur tetap bagi setiap jenis.** Sistem lama (YUSTIC) mempunyai empat lajur tatabahasa, menulis, mendengar dan membaca yang dikodkan secara tetap dalam `CET`, satu huruf P/N bagi setiap subjek untuk peperiksaan komprehensif (`SSCORE_ADD`), dan satu lagi jadual untuk peperiksaan komputer (`COMEXAM`). Sistem baharu mentakrifkan bahagian dan kaedah pemarkahan pada **jenis peperiksaan** dan mengendalikan setiap peperiksaan dengan struktur sesi, pendaftaran dan keputusan yang sama.
- **Keputusan lulus.** Dalam sistem lama, jumlah keempat-empat bahagian pada atau melebihi markah potong bermakna lulus, tanpa minimum bagi setiap bahagian. Sistem baharu memutuskan dengan markah lulus jenis (atau sesi) dan **minimum bagi setiap bahagian**. Jenis lulus/gagal diputuskan oleh kakitangan.
- **Tempoh pendaftaran dan kapasiti.** Sistem lama tiada kedua-duanya. Sesuatu sesi mempunyai masa pembukaan dan penutupan pendaftaran serta kapasiti.
- **Penerbitan keputusan.** Dalam sistem lama, pelajar melihat keputusan sebaik sahaja ia dimasukkan, dan hanya bagi sesi terkini. Dalam sistem baharu, pelajar melihat keputusan selepas **Terbitkan keputusan** bagi sesi tersebut, dan keputusan setiap sesi disimpan.
- **Keputusan luar.** Sistem lama tiada tempat untuk merekod ujian luar seperti TOEFL atau TOPIK (kotak HSK pada helaian semakan bergraduat hanyalah label kosong). Keputusan luar direkodkan bersama fail bukti.
- **Pautan kepada syarat bergraduat.** Dalam sistem lama, seseorang membaca helaian semakan bergraduat dan membuat keputusan. Jenis syarat **Lulus peperiksaan** memasukkannya ke dalam kurikulum ([bab 17](17-courses-and-curriculum.md)).

## Jenis peperiksaan dan bahagian

**Jenis peperiksaan**: kod, nama (3 bahasa), kategori (Bahasa, Komprehensif, Tesis, Ujian luar), pemarkahan, markah maksimum, markah lulus, keputusan luar dibenarkan, aktif.

| Pemarkahan | Input | Keputusan |
|---|---|---|
| Markah | Markah bagi setiap bahagian (jumlah apabila tiada bahagian) | Jumlah ≥ markah lulus dan setiap bahagian ≥ minimumnya. Tanpa markah lulus, kakitangan memilih |
| Lulus/gagal | Lulus atau tidak | Kakitangan memilih |

**Bahagian** (di bahagian bawah skrin suntingan jenis): kod (nama lajur CSV), nama, markah maksimum, minimum untuk lulus (pilihan). Dengan bahagian, jumlah ialah hasil tambah markah bahagian.

Jenis yang disemai oleh profil universiti Korea:

| Kod | Jenis | Keputusan | Catatan |
|---|---|---|---|
| `ENG_COMP` | Peperiksaan komprehensif bahasa Inggeris | Markah, lulus pada 60 | Mendengar, membaca, menulis, tatabahasa, 25 mata setiap satu |
| `COMP_EXAM` | Peperiksaan komprehensif komputer | Lulus/gagal | |
| `THESIS` | Tesis tamat pengajian | Lulus/gagal | |
| `TOPIK` | Ujian Kecekapan Bahasa Korea | Lulus/gagal, luar | Tahap dan nombor sijil direkodkan |

## Sesi

**Sesi peperiksaan**: jenis, tarikh (unik bagi setiap jenis), tempat, kapasiti, pendaftaran dibuka dan ditutup, markah lulus (kosong: nilai jenis). Senarai **Pendaftar** berada di bahagian bawah skrin suntingan sesi.

- **Daftarkan pelajar** (masukkan nombor matrik): kakitangan boleh mendaftarkan pelajar tanpa mengira tempoh pendaftaran, sehingga tarikh peperiksaan. Kapasiti terpakai. Tiada sesiapa boleh didaftarkan bagi sesi yang tarikhnya telah berlalu. Keputusan hanya dimasukkan bagi pelajar yang berdaftar; baris CSV bagi pelajar yang tidak berdaftar dilaporkan sebagai ralat dan dilangkau.
- Pelajar mendaftar dan membatalkan dalam portal semasa tempoh pendaftaran. Hanya pelajar yang sedang belajar dan tangguh pengajian boleh berbuat demikian.
- **Masukkan keputusan**: markah bagi setiap bahagian (atau jumlah), lulus atau tidak jika perlu, catatan. Sebelum penerbitan, memasukkan sekali lagi akan menulis gantinya (peristiwa audit `assessment.result_recorded`). **Selepas penerbitan ia dikunci** (lihat permohonan pembetulan di bawah).
- **Tidak hadir**: berdaftar tetapi tidak menduduki peperiksaan. Memasukkan keputusan mengembalikan pendaftaran kepada berdaftar.
- **Batalkan pendaftaran**: pendaftaran yang mempunyai keputusan tidak boleh dibatalkan.
- **Terbitkan keputusan**: mulai saat ini pelajar melihat keputusan sesi ini. Selepas penerbitan, keputusan tidak boleh disunting atau dipadam secara terus; ia berubah melalui **permohonan pembetulan** (keputusan 2026-10-23). Sama seperti arahan `assessment:publish-results`.
- **Markah lulus** sesi (mengatasi nilai jenis): digunakan apabila mesyuarat menurunkan markah potong kerana peperiksaan sukar. Ia boleh diubah **sebelum penerbitan**, dan sebaik sahaja ia berubah, setiap keputusan yang telah dimasukkan bagi sesi tersebut **dinilai semula** daripada markah yang disimpan (jumlah berbanding ambang baharu, minimum bahagian seperti yang ditetapkan pada jenis). Dengan sebab, peristiwa audit `assessment.pass_score_changed` menyimpan ambang lama dan baharu bersama nombor matrik yang keputusannya berubah. **Selepas penerbitan** ia dikunci (permohonan pembetulan sahaja). Minimum bagi setiap bahagian tidak boleh diubah pada sesi (tetapan jenis).
- **Import keputusan (CSV)**: format di bawah. Baris bagi pelajar yang tidak berdaftar dilangkau sebagai ralat. Sama seperti arahan `assessment:import-results`.

Format CSV (baris pengepala, UTF-8):

| Lajur | Kandungan |
|---|---|
| `student_number` | Nombor matrik (wajib) |
| Kod bahagian (`LISTENING`, …) | Markah bahagian. Tidak sensitif huruf besar/kecil |
| `total` | Jumlah bagi jenis tanpa bahagian |
| `passed` | `Y`/`N`. Wajib bagi jenis lulus/gagal atau jenis tanpa markah lulus |
| `absent` | `Y` menandakan pelajar tidak hadir |
| `note` | Catatan |

Baris ralat dilangkau dan dilaporkan bersama nombor barisnya dalam pemberitahuan hasil (skrin) atau output (arahan).

## Pembetulan keputusan

Prosedur untuk mengubah keputusan sesi yang telah diterbitkan. Ia berfungsi seperti pindaan gred.

1. **Mohon pembetulan** daripada senarai pendaftar sesi: markah baharu (atau lulus atau tidak) dan sebab. Satu permohonan menunggu bagi setiap keputusan. Peristiwa audit `assessment.correction_requested`.
2. Di bawah **Peperiksaan > Pembetulan keputusan**, pemegang kebenaran `Urus jenis peperiksaan, bahagian dan sesi` mengklik **Luluskan** (catatan pilihan) atau **Tolak** (catatan diwajibkan). **Tiada sesiapa boleh memutuskan permohonannya sendiri.**
3. Setelah diluluskan, keputusan berubah kepada nilai baharu serta-merta dan pelajar dapat melihatnya. Peristiwa audit `assessment.correction_approved`, `assessment.result_corrected`. Pemohon boleh membatalkan permohonan sehingga ia diputuskan.

Senarai memaparkan permohonan yang menunggu secara lalai, dengan nilai semasa dan nilai dicadangkan bersebelahan.

## Daftar keputusan

**Keputusan peperiksaan**: setiap keputusan (sesi dan luar). Tapis mengikut jenis, sumber dan lulus. Skop paparan adalah sama seperti bagi rekod pelajar.

- **Rekod keputusan luar**: nombor matrik, jenis (hanya jenis yang membenarkan keputusan luar), tarikh diduduki (bukan pada masa hadapan), jumlah, tahap, lulus atau tidak, nombor sijil, fail bukti (PDF, JPG, PNG, 5 MB), catatan. Peristiwa audit `assessment.external_recorded`.
- **Bukti**: membuka fail. Pelajar boleh membuka fail mereka sendiri.
- **Alih keluar**: sebab diwajibkan, peristiwa audit `assessment.result_removed`. Untuk memadam dan merekod semula keputusan luar yang dimasukkan dengan salah atau keputusan sebelum penerbitan. Keputusan sesi yang telah diterbitkan tidak boleh dialih keluar (permohonan pembetulan).

## Syarat bergraduat

Dalam editor **Syarat bergraduat**, pilih jenis syarat **Lulus peperiksaan** dan namakan jenis peperiksaan. Pelajar memenuhinya dengan sekurang-kurangnya satu keputusan lulus (sesi atau luar) bagi jenis tersebut. Bahagian atas halaman **Peperiksaan** portal memaparkan jenis yang diwajibkan oleh kurikulum pelajar dan sama ada ia telah lulus. Semakan bergraduat (Fasa 11) membaca syarat ini.

## Portal pelajar

**Peperiksaan**: syarat kurikulum dan sama ada ia telah lulus, sesi yang dibuka untuk pendaftaran (daftar, batal), pendaftaran saya, keputusan saya (sesi yang diterbitkan dan keputusan luar, markah bahagian, bukti).

## Soalan lazim

- **Saya perlu mendaftarkan pelajar di luar tempoh pendaftaran.** Kakitangan mendaftarkan mereka di bawah **Pendaftar > Daftarkan pelajar** pada sesi tersebut.
- **Saya mahu menukar markah lulus bagi sesi ini sahaja.** Masukkan markah lulus sesi tersebut sebelum keputusan diterbitkan. Semua keputusan yang telah dimasukkan dinilai semula serta-merta berdasarkan markah yang disimpan (markah minimum setiap komponen kekal mengikut jenis peperiksaan). Selepas penerbitan, markah lulus dikunci; gunakan permohonan pembetulan.
- **Saya tersalah memasukkan markah.** Sebelum penerbitan, **Masukkan keputusan** sekali lagi untuk menulis gantinya. Selepas penerbitan, buat **permohonan pembetulan** dan minta kakitangan lain meluluskannya.
- **Statistik pelajar tidak hadir.** Tapis senarai pendaftar mengikut status.
