# 28. Integrasi LMS

Menu: **Integrasi LMS > Platform LMS**, **Pautan kursus**, **Log penyegerakan** (kebenaran `Urus platform LMS, pautan dan penyegerakan`; lalai: Pegawai Hal Ehwal Akademik, selain Pentadbir Sistem dan Pegawai Pendaftar). Pensyarah menggunakan pautan LMS di bawah **Pengajaran saya** dan dalam butiran seksyen, serta **Muatkan markah daripada LMS** pada skrin kemasukan gred. Pemuatan mengikut syarat yang sama seperti kemasukan gred (pensyarah dalam tempoh kemasukan gred, kakitangan dalam skop pengurusan `Gred` mereka).

Butiran untuk pembangun terdapat dalam [panduan integrasi LMS](../../../lms-integration-guide.md).

## Apa yang pergi ke mana

| SIS → LMS | Bila |
|---|---|
| Seksyen (penciptaan kursus) + pensyarah | Apabila seksyen **diterbitkan**, dan dengan `lms:sync` |
| Pendaftaran yang disahkan → pendaftaran dalam LMS | Serta-merta semasa pendaftaran kursus, kenaikan senarai menunggu atau pendaftaran oleh kakitangan |
| Gugur atau tarik diri → nyahdaftar | Serta-merta |
| Penyelarasan penuh | Setiap hari pada 02:30 (`lms:reconcile`) |

| LMS → SIS | Bila |
|---|---|
| Markah akhir (draf) | Apabila pensyarah mengklik **Muatkan markah daripada LMS** pada skrin kemasukan gred. Penyimpanan dan penghantaran dilakukan oleh pensyarah |

Gred muktamad tidak dihantar ke LMS. Bahan kursus, tugasan dan kuiz berada dalam LMS.

## Platform

**Platform LMS**: kod, nama, pemacu, URL asas, kelayakan akses (kunci/nilai, disimpan dalam bentuk tersulit, dikekalkan apabila dibiarkan kosong semasa suntingan), aktif, segerakkan senarai pelajar, tarik markah akhir.

- Pemacu **Tiada (rekod sahaja)**: tidak memanggil apa-apa dan merekod apa yang akan dihantar. Gunakannya untuk menyemak aliran atau untuk demonstrasi sebelum integrasi. Platform `DEMO` dalam data demo menggunakannya.
- `Moodle`, `Open edX`: belum ada penyambung (ditandakan belum dilaksanakan). Apabila dipilih, penyegerakan direkodkan sebagai gagal.
- Menghidupkan **Aktif** menjadikan penerbitan seksyen dan perubahan pendaftaran mengalir serta-merta mulai saat itu dan memasukkan platform ke dalam penyelarasan setiap malam.

## Pautan kursus dan log penyegerakan

- **Pautan kursus**: seksyen mana dipautkan kepada kursus platform mana (id, URL), status (Menunggu/Dipautkan/Gagal), penyegerakan terakhir dan ralat. **Segerakkan semula** menolak seksyen, pensyarah dan senarai pelajar sekali lagi.
- **Log penyegerakan**: hasil dan mesej bagi setiap langkah (kursus, tambah dan buang ahli, tarik markah, penyelarasan). Lihat di sini untuk punca kegagalan.
- Arahan: `lms:sync {platform} --term={term}` (tolakan penuh, `--dry-run`), `lms:reconcile` (penyelarasan).

## Portal

- Pautan kursus platform dipaparkan dalam butiran kursus bagi pelajar dan pensyarah serta dalam senarai **Pengajaran saya** pensyarah (seksyen yang dipautkan sahaja).
- Kemasukan gred: apabila **Tarik markah akhir** dihidupkan bagi platform dan seksyen telah dipautkan, butang **Muatkan markah daripada LMS** muncul. Ia hanya mengisi lajur markah dan tidak menyimpan.

## Soalan lazim

- **Seorang pelajar tiada dalam LMS.** Pelajar memerlukan akaun (pengguna) untuk disegerakkan. Jika tiada, jemput mereka ([bab 14](14-merge-and-invitations.md)); penyelarasan seterusnya akan menambah mereka.
- **Penyegerakan kekal gagal.** Baca mesej dalam log penyegerakan. Kelayakan akses yang telah luput adalah perkara biasa. Ubah nilai pada skrin suntingan platform dan **Segerakkan semula**.
- **Kami telah menukar platform.** Cipta platform baharu dan matikan Aktif bagi platform lama. Pautan dicipta secara berasingan bagi setiap platform.
