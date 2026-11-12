# 23. Sijil

Menu: **Sijil > Sijil dikeluarkan**, **Jenis sijil**, **Tetapan > Tetapan sijil**. Sijil dikeluarkan dengan butang **Keluarkan sijil** pada skrin rekod pelajar (**Pelajar dan kakitangan > Rekod pelajar > Paparan**). Pelajar memuat turun PDF sijil mereka sendiri dalam portal di bawah **Sijil**. Sesiapa sahaja boleh mengesahkan sijil pada halaman `/verify/{code}`.

| Kebenaran | Apa yang dibenarkan |
|---|---|
| `Keluarkan, jana semula dan batalkan sijil` | Mengeluarkan, menjana semula, membatalkan, melihat sijil yang dikeluarkan (dalam skop paparan rekod pelajar) |
| `Urus jenis sijil, templat dan tetapan` | Jenis dan templatnya bagi setiap bahasa, corak nombor siri, penandatangan |

Lalai peranan: Pegawai Sijil mengeluarkan, Pegawai Pendaftar mempunyai kedua-duanya, Pegawai Hal Ehwal Akademik mengurus jenis dan templat.

## Perbezaan daripada sistem lama

- **Daftar sijil yang dikeluarkan.** Sistem lama (YUSTIC) mencetak terus daripada skrin (`aca3/cti_*`), dan daftarnya (`SRCERT`) wujud hanya untuk menjana nombor, jadi ia tidak menunjukkan siapa yang mengeluarkan sijil atau sama ada ia telah dibatalkan. Sistem baharu merekodkan nombor siri, kod pengesahan, kakitangan, tujuan, bilangan salinan, fail PDF dan checksum bagi setiap pengeluaran, dan sijil yang dibatalkan mengekalkan barisnya.
- **Pengesahan.** Sistem lama tiada pengesahan. Setiap sijil membawa kod pengesahan dan kod QR, dan halaman awam menunjukkan bahawa ia telah dikeluarkan, jenisnya, tarikh pengeluaran dan pemegang yang disamarkan (huruf pertama dan terakhir nama keluarga dan nama diri, 4 digit pertama nombor matrik).
- **Templat.** Sistem lama mempunyai skrip PDF berasingan bagi setiap jenis dan bahasa. Sistem baharu menyunting templat bagi setiap bahasa (tajuk, kandungan, pengaki, penandatangan) untuk setiap jenis pada skrin.
- **Sekatan.** Sistem lama mengeluarkan sijil tanpa mengira senarai penafian pendaftaran. Sistem baharu tidak mengeluarkan sijil kepada pelajar yang mempunyai sekatan sijil ([bab 20](20-registration.md)).
- Yuran tidak direkodkan (sistem lama hanya merekodkan jumlah).
- Tiada permohonan dalam talian oleh pelajar (pelan pemindahan 17.5). Kakitangan mengeluarkan sijil.

## Jenis dan templat

**Jenis sijil**: kod (digunakan dalam nombor siri), nama (3 bahasa), sasaran (semua rekod pelajar / pelajar / graduan), termasuk gred, termasuk foto, aktif, susunan. Profil universiti Korea menyemai tiga jenis.

| Kod | Jenis | Sasaran | Catatan |
|---|---|---|---|
| `ENR` | Surat Pengesahan Pelajar | Pelajar (belum bergraduat) | |
| `GRD` | Surat Pengesahan Tamat Pengajian | Graduan | Tarikh bergraduat, nombor ijazah |
| `TRN` | Transkrip Akademik | Semua rekod pelajar | Jadual gred muktamad mengikut semester, PNGK dan kredit diperoleh |

**Templat** (di bahagian bawah skrin suntingan jenis): satu bagi setiap bahasa. Tajuk, kandungan (perenggan dipisahkan oleh baris kosong), pengaki, penandatangan. Kandungan menggunakan pemegang tempat dalam tanda kurung kerinting.

| Pemegang tempat | Nilai |
|---|---|
| `{institution}` | Nama institusi (dalam bahasa tersebut) |
| `{student_name}`, `{student_number}` | Nama (dalam bentuk bahasa tersebut, jika tidak ejaan Rumi), nombor matrik |
| `{program}`, `{org_unit}` | Program, unit organisasi |
| `{admission_date}`, `{graduation_date}`, `{degree_number}` | Tarikh kemasukan, tarikh bergraduat, nombor ijazah |
| `{status}`, `{year_level}` | Status pelajar, tahun pengajian |
| `{birth_date}`, `{gender}`, `{nationality}` | Tarikh lahir, jantina, kewarganegaraan |
| `{issue_date}`, `{serial_number}`, `{purpose}`, `{copies}` | Tarikh pengeluaran, nombor siri, tujuan, bilangan salinan |

Tarikh dicetak dalam format bahasa tersebut. Nilai yang tiada (cth. tarikh bergraduat) dicetak sebagai kosong. Jadual maklumat peribadi (nama, nombor matrik, tarikh lahir, jantina, kewarganegaraan, program, tarikh kemasukan, kemudian tarikh bergraduat bagi graduan atau status dan tahun pengajian bagi yang lain) ditambah secara automatik di atas kandungan.

## Tetapan

**Tetapan > Tetapan sijil**

- **Corak nombor siri**: lalai `{TYPE}-{YYYY}-{SEQ:6}` → `ENR-2026-000001`. `{SEQ:n}` diwajibkan. Nombor urutan dikira secara berasingan bagi setiap awalan yang berbeza. `{TYPE}-{SEQ:6}` dikira mengikut jenis merentas tahun, `{YYYY}{SEQ:5}` mengikut tahun merentas jenis. Pengeluaran serentak tidak pernah bertembung (pembilang ditambah secara atomik dalam pangkalan data).
- **Pengesahan sah selama (hari)**: 0 bermaksud sentiasa. Selepas tempoh tersebut, halaman pengesahan menjawab "telah luput". Pengeluaran itu sendiri kekal dalam daftar.
- **Penandatangan mengikut bahasa**: nama yang dicetak di atas cop mohor ("Naib Canselor, Universiti Ninefruits"). Penandatangan dalam templat diutamakan.

## Pengeluaran

**Keluarkan sijil** pada skrin rekod pelajar: jenis, bahasa, tujuan, salinan (1 hingga 20). Semasa disimpan, PDF dibuka dalam tab baharu.

Pengeluaran ditolak apabila:

- sasaran jenis tidak sepadan (surat pengesahan pelajar bagi graduan, surat pengesahan tamat pengajian bagi pelajar semasa)
- jenis tidak aktif
- tiada templat bagi bahasa tersebut
- **sekatan sijil** sedang berkuat kuasa bagi pelajar (sekatan, [bab 20](20-registration.md)); tarik balik sekatan itu terlebih dahulu
- rekod pelajar berada di luar skop paparan

Pengeluaran direkodkan dalam log aktiviti (`certificate.issued`: nombor matrik, jenis, bahasa, nombor siri, salinan, tujuan).

## Sijil dikeluarkan

**Sijil > Sijil dikeluarkan**: tarikh pengeluaran, nombor siri, jenis dan bahasa, nombor matrik, nama, tujuan, salinan, status, kakitangan. Tapis mengikut jenis dan status. Skop paparan adalah sama seperti bagi rekod pelajar.

- **PDF**: membuka fail yang disimpan. Ia tidak dijana semula, jadi ia tepat seperti yang dikeluarkan.
- **Halaman pengesahan**: membuka halaman awam.
- **Jana semula**: menjana PDF baharu daripada data semasa, cth. selepas membetulkan templat, dan menggantikan fail. Nombor siri dan kod pengesahan kekal; checksum berubah. Peristiwa audit `certificate.rerendered`. Sama seperti arahan `certificate:render`.
- **Batalkan**: sebab diwajibkan. Baris dan fail kekal, status menjadi dibatalkan, dan halaman pengesahan menjawab "dibatalkan". Peristiwa audit `certificate.revoked`. Sijil yang dibatalkan tidak dipulihkan; sijil baharu dikeluarkan.

Baris daftar tidak boleh disunting atau dipadam pada skrin.

## Pengesahan

- URL pengesahan (`/verify/{code}`) dan kod QR dicetak di bahagian bawah sijil. Kod tersebut ialah rentetan rawak 20 aksara.
- Halaman dibuka tanpa log masuk dan dihadkan kepada 30 permintaan seminit. Ia menunjukkan sama ada sijil telah dikeluarkan, jenisnya, nombor siri, tarikh pengeluaran, serta nama dan nombor matrik yang disamarkan. Kod yang dibatalkan, telah luput dan tidak dikenali masing-masing mendapat jawapan yang berbeza.
- Untuk menyemak fail PDF yang diserahkan itu sendiri, bandingkan checksum (SHA-256) dalam daftar dengan hash fail tersebut. Arahan `certificate:verify {code}` juga melakukan perkara ini.

## Portal pelajar

**Sijil**: sijil yang dikeluarkan pada rekod pelajar itu sendiri, beserta PDF. Sijil yang dibatalkan disenaraikan bersama statusnya, tetapi pelajar tidak boleh memuat turun PDF sijil yang dibatalkan (kakitangan yang mengeluarkan boleh membukanya untuk menyemak rekod). Fail pelajar lain tidak boleh dibuka (dikuatkuasakan dalam aplikasi dan pangkalan data).

## Soalan lazim

- **Nama dieja dengan salah.** Betulkan varian nama individu tersebut (dalam bahasa berkenaan) dan **Jana semula**. Jika sijil sudah diserahkan, batalkannya dan keluarkan sijil baharu.
- **Saya telah mengubah templat; bagaimana dengan sijil yang sudah dikeluarkan?** Ia tidak berubah. **Jana semula** jika perlu.
- **Satu nombor siri telah dilangkau.** Apabila ralat berlaku semasa pengeluaran (cth. perkhidmatan PDF tidak berfungsi), nombor tersebut telah digunakan dan mungkin tiada baris daftar yang tinggal. Nombor dijamin unik, bukan berturutan.
