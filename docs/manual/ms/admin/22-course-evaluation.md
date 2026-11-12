# 22. Penilaian Kursus

Menu: **Penilaian kursus > Penilaian semester**, **Borang penilaian**, **Respons mengikut seksyen**, **Tetapan > Penilaian kursus**. Pelajar menggunakan **Penilaian kursus** dalam portal; pensyarah menggunakan **Pengajaran saya > Keputusan penilaian kursus** dalam portal.

| Kebenaran | Apa yang dibenarkan |
|---|---|
| `Lihat kadar respons dan keputusan penilaian kursus` | Bilangan respons dan kadar respons bagi setiap seksyen, keputusan yang diterbitkan. Apabila ditetapkan dengan skop unit organisasi, hanya seksyen yang ditawarkan oleh unit tersebut |
| `Urus borang penilaian, penilaian semester dan penerbitan` | Borang dan soalan, tetapan penilaian semester, penerbitan keputusan, tetapan penilaian kursus |

Lalai peranan: Pegawai Hal Ehwal Akademik mempunyai kedua-duanya, Pembantu Jabatan mempunyai kebenaran melihat (skop jabatan).

## Perbezaan daripada sistem lama

- **Keanoniman.** Sistem lama (YUSTIC) menyimpan nombor matrik bersama setiap respons, dan akaun pensyarah boleh membaca jadual mentah. Sistem baharu menyimpan **siapa yang menilai (rekod penyelesaian)** dan **apa yang dijawab (respons)** secara berasingan. Respons tidak membawa pelajar, nombor urutan atau masa. Respons juga tidak ditulis ke dalam rekod perubahan data (audit), kerana rekod audit akan menyimpan pelaku.
- **Ambang untuk memaparkan keputusan.** Sistem lama tiada ambang respons minimum pada skrin pensyarah, dan pensyarah boleh melihat keputusan seksyen lain. Sistem baharu memaparkan keputusan dan ulasan, selepas keputusan semester **diterbitkan**, **hanya bagi seksyen yang mencapai bilangan respons minimum dan kadar respons minimum**. Pensyarah hanya melihat seksyen mereka sendiri.
- **Sekatan paparan gred.** Sistem lama hanya menyekat pautan menu, dan kunci tidak ditarik balik apabila tempoh penilaian tamat. Sistem baharu menyekat pada pelayan dan menarik balik sekatan apabila tempoh ditutup.
- **Pengurusan soalan.** Sistem lama memasukkan soalan melalui SQL setiap semester. Sistem baharu mengurus borang dan soalan pada skrin.
- Peraturan yang menolak respons dengan semua 1 atau semua 5 kini menjadi tetapan (mati secara lalai).

## Menyediakan semester

1. **Borang**: pilih borang di bawah **Borang penilaian**. Profil universiti Korea menyemai borang `STANDARD` (3 soalan setiap satu mengenai persediaan, kaedah pengajaran, kandungan, penilaian, serta kemudahan dan peralatan, pada skala 5 mata, ditambah 1 soalan ulasan).
2. **Sediakan semester**: semester, borang, respons minimum, kadar respons minimum (%). Nilai lalai datang daripada **Tetapan > Penilaian kursus** (5 respons, 25%). Kedua-dua ambang mesti dipenuhi untuk keputusan dipaparkan. Bilangan respons melindungi keanoniman (dengan hanya beberapa respons, pelajar boleh diteka); kadar respons memastikan keputusan adalah mewakili.
3. **Tempoh penilaian**: tambah tempoh **Penilaian kursus** bagi setiap sesi antara **tempoh urusan** pada skrin suntingan semester ([bab 10](10-terms.md)). Kriteria sasaran membenarkan tempoh berasingan mengikut tahun pengajian.
4. Semasa tempoh tersebut, `php artisan evaluation:remind {term}` menghantar e-mel peringatan kepada pelajar yang belum selesai. Lihat kadar respons di bawah **Respons mengikut seksyen**.
5. Apabila pemprosesan gred selesai, **Terbitkan keputusan** (atau `evaluation:publish-results {term}`).

## Borang dan soalan

| Medan | Keterangan |
|---|---|
| Kod, Nama, Keterangan | Keterangan dipaparkan di atas skrin penilaian pelajar |
| Mata skala | 2 hingga 10, biasanya 5 |
| Label bagi 1, Label bagi mata tertinggi | cth. Sangat tidak setuju, Sangat setuju |

Tab **Soalan**: bidang (soalan dalam bidang yang sama dikumpulkan), soalan, jenis (Skala, Ulasan), wajib, **Terbalik** (soalan berbentuk negatif; jawapan diterbalikkan dalam purata, cth. 5 bagi "Sukar untuk mengikuti rentak kelas" dikira sebagai 1), susunan.

Borang yang mempunyai sekurang-kurangnya satu respons tidak lagi boleh diubah soalan atau skalanya (supaya keputusan tidak sekali-kali menjadi jawapan kepada soalan yang tidak ditanya). Untuk mengubahnya, buat borang baharu dengan **Salin borang** dan gunakannya pada semester berikutnya.

## Apa yang dinilai

- Kursus yang didaftarkan atau diselesaikan dalam semester yang mempunyai penilaian semester yang telah disediakan. Kursus yang ditarik diri dan digugurkan dikecualikan.
- Kursus yang tetapan **Penilaian kursus** dimatikan dikecualikan (praktikum, latihan industri dan seumpamanya; [bab 17](17-courses-and-curriculum.md)).
- Sesuatu kursus hanya boleh dinilai semasa tempoh penilaian kursus bagi sesinya dibuka untuk pelajar.

## Skrin pelajar

**Penilaian kursus** portal: dalam senarai kursus untuk dinilai, klik **Nilai**, jawab soalan dan hantar. Setiap soalan wajib mesti dijawab, dan penghantaran tidak boleh diubah. Setiap kursus dinilai sekali.

**Sekatan paparan gred** (satu tetapan, hidup secara lalai): semasa tempoh tersebut, pelajar yang mempunyai kursus yang belum dinilai dalam semester itu akan mendapati gred semester tersebut disembunyikan pada skrin gred portal dan laporan gred, beserta notis. Gred dipaparkan sebaik sahaja penilaian selesai atau tempoh tamat. Gred semester lain kekal kelihatan.

## Keputusan

- Pensyarah: **Keputusan penilaian kursus** di bawah **Pengajaran saya** dalam portal. Hanya dibuka selepas keputusan semester diterbitkan.
- Kakitangan: **Penilaian kursus > Respons mengikut seksyen** memaparkan bilangan dan kadar respons bagi setiap seksyen; **Keputusan** membuka skrin keputusan. Keputusan terperinci juga hanya dipaparkan kepada kakitangan selepas penerbitan (semasa tempoh tersebut, memadankan perubahan dalam keputusan dengan masa penyelesaian boleh mengenal pasti pelajar).
- Skrin keputusan: respons/dijangka (kadar respons), min bagi setiap soalan dan bilangan jawapan bagi setiap mata (diterbalikkan bagi soalan terbalik), min keseluruhan, ulasan (dalam susunan rawak).
- Seksyen dengan respons kurang daripada minimum atau kadar respons di bawah minimum hanya memaparkan bilangan respons, bukan keputusan dan ulasan.

## Tetapan

**Tetapan > Penilaian kursus**: sembunyikan gred semester sehingga penilaiannya selesai (lalai hidup), tolak jawapan yang sama bagi semua soalan (lalai mati), respons minimum (lalai 5) dan kadar respons minimum (lalai 25%, ambang pada skrin pejabat akademik sistem lama) bagi penilaian semester baharu.
