# 17. Kursus dan Syarat Bergraduat

Menu: **Kurikulum dan seksyen > Kursus**, **Syarat bergraduat** (kebenaran `Urus kursus, versi dan syarat kursus`, `Urus syarat bergraduat`)

## Kursus dan versi

Sesuatu kursus terdiri daripada **kod** yang tidak berubah dan **versi** yang mempunyai tempoh berkuat kuasa. Nama kursus, kredit, jam, kategori kursus, asas penggredan dan syarat kursus adalah milik versi. Sistem lama (YUSTIC) membezakan kursus mengikut kod kursus dan tarikh mula, tetapi gred hanya merujuk kepada kod kursus, jadi tidak jelas versi mana yang telah diambil oleh pelajar. Dalam sistem baharu, seksyen dan gred merujuk kepada versi.

- Tempoh versi bagi kursus yang sama tidak boleh bertindih (kekangan pangkalan data). Pada sebarang tarikh, tepat satu versi berkuat kuasa.
- Seksyen ditawarkan dengan versi yang berkuat kuasa pada tarikh mula sesi.

| Medan | Keterangan |
|---|---|
| Kod kursus | cth. `CSE2010`. Kod tidak membawa makna perniagaan (peraturan seperti semakan bank kredit `009*` dalam sistem lama digantikan oleh medan di bawah) |
| Unit pengurus | Unit organisasi yang mengurus kursus. Unit penawar lalai bagi seksyennya |
| Nama kursus, Keterangan | Mengikut bahasa |
| Kredit, jam kuliah dan amali | Dalam langkah 0.5 |
| Kategori kursus | Data rujukan > Kategori kursus |
| Penggredan | Gred huruf atau Lulus/gagal |
| Dikira dalam had kredit | Apabila mati, kursus dikecualikan daripada had kredit pendaftaran (kursus bank kredit dan seumpamanya) |
| Penilaian kursus | Apabila mati, kursus tidak dinilai (praktikum, latihan industri dan seumpamanya; [bab 22](22-course-evaluation.md)) |
| Boleh diambil semula untuk kredit | Mengambilnya sekali lagi memperoleh kredit semula dan bukannya dikira sebagai ulang kursus (seminar dengan topik yang berubah dan seumpamanya) |

## Menyemak semula kursus

Apabila kredit atau nama kursus berubah, jangan sunting versi tersebut; sebaliknya **semak semula** versi itu. Pada skrin **Sunting** kursus, dalam tab **Versi**, klik **Versi baharu mulai tarikh** pada versi semasa dan masukkan tarikh mula versi baharu, nilai yang berubah dan sebabnya.

- Versi semasa tamat sehari sebelum tarikh mula; versi baharu bermula dengan salinan versi semasa beserta syarat kursusnya.
- Seksyen yang telah ditawarkan dan gred lepas terus merujuk kepada versi terdahulu.
- Sunting versi secara terus hanya untuk pembetulan yang tidak mengubah maknanya, seperti kesilapan ejaan (ia muncul dalam rekod perubahan data).

## Prasyarat dan kosyarat

Ia dimasukkan di bawah **Syarat kursus** di bahagian bawah skrin suntingan versi.

| Medan | Keterangan |
|---|---|
| Jenis | Prasyarat (mesti diselesaikan terlebih dahulu) atau Kosyarat (diambil bersama dalam semester yang sama; dipenuhi jika sudah diselesaikan) |
| Kumpulan | Hanya satu kursus dalam kumpulan yang sama perlu diselesaikan (atau). Setiap kumpulan mesti dipenuhi (dan) |
| Gred minimum | Kod gred daripada skala gred lalai (cth. `C0`). Kosong: gred lulus |
| Boleh diambil dalam semester yang sama | Apabila prasyarat boleh diambil dalam semester yang sama |

Contoh: kumpulan 1 mengandungi `CSE1001`, kumpulan 2 mengandungi `MAT1001` dan `MAT1002` → `CSE1001` dan (`MAT1001` atau `MAT1002`). Peraturan prasyarat menyemak perkara ini semasa pendaftaran kursus ([bab 20](20-registration.md)).

## Kursus setara

Apabila pembaharuan kurikulum menggantikan sesuatu kursus, masukkan kursus pengganti beserta tempohnya dalam tab **Kursus setara** pada skrin **Sunting** kursus. Dengan **Dua hala** dihidupkan, setiap kursus dikira bagi pihak yang satu lagi; apabila mati, hanya kursus setara dikira bagi kursus ini. Digunakan oleh semakan ulang kursus ([bab 20](20-registration.md)) dan semakan bergraduat (Fasa 8).

## Syarat bergraduat

Satu set syarat dicipta bagi setiap program, **Peranan program** (program utama, major kedua, minor) dan julat **tahun kurikulum**. Pelajar tertakluk kepada set yang sepadan dengan tahun kurikulum rekod pelajarnya.

- Tahun kurikulum bagi program dan peranan program yang sama tidak boleh bertindih. Biarkan tahun akhir kosong supaya set terus berkuat kuasa sehingga set seterusnya bermula.
- Jenis syarat

| Jenis | Input | Contoh |
|---|---|---|
| Kredit dalam kategori | Kategori kursus, kredit minimum | 9 kredit pendidikan umum (wajib) |
| Kursus wajib | Senarai kursus | Kesemua 4 kursus major wajib |
| Pilihan daripada kursus | Senarai kursus, kredit minimum atau bilangan kursus minimum | 12 kredit daripada kursus elektif major |
| Lulus peperiksaan | Jenis peperiksaan | Lulus peperiksaan komprehensif Bahasa Inggeris ([bab 24](24-assessments.md)) |

Major kedua dan minor mendapat set tersendiri yang lebih kecil daripada program utama (cth. 36 kredit bagi major kedua, 21 bagi minor). Syarat disemak oleh semakan bergraduat (Fasa 8).
