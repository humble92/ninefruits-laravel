# 2. Pengguna dan peranan

## Konsep

| Istilah | Maksud |
|---|---|
| Kebenaran | Hak untuk menggunakan satu ciri. Contoh: `Lihat pengguna`, `Set semula pengesahan dua faktor` |
| Peranan | Himpunan kebenaran. Contoh: Pejabat Pendaftar, pembantu jabatan |
| Penetapan peranan | Memberikan peranan kepada pengguna bersama skop dan tempoh |
| Skop | Tempat peranan itu berkuat kuasa: **Semua**, **Unit organisasi** (fakulti, jabatan, dsb.) atau **Program** |
| Peranan persona | Pemohon, pelajar, alumni, pensyarah, penasihat akademik. Diberikan secara automatik berdasarkan data akademik; tidak boleh ditetapkan secara manual |

Apabila tanggungjawab berubah, hanya konfigurasi peranan atau penetapannya yang berubah; program itu sendiri tidak berubah. Setiap perubahan direkodkan dalam log audit.

## Mencipta akaun pengguna

1. Pergi ke **Pengguna dan akses > Pengguna** dan tekan **Cipta**.
2. Masukkan nama, e-mel dan bahasa, kemudian simpan.
3. Pengguna menerima pautan melalui e-mel untuk menetapkan kata laluan. Pentadbir tidak pernah melihat kata laluan itu.

Akaun tidak dipadamkan. Untuk menghentikan akaun, contohnya apabila seseorang meninggalkan institusi, buka halaman **Sunting** pengguna itu dan matikan **Aktif**. Akaun yang tidak aktif tidak boleh log masuk dan kehilangan semua kebenaran. Skrin yang sudah log masuk akan dilog keluar pada permintaan seterusnya.

Perlindungan semasa menyunting akaun

- Anda tidak boleh menyahaktifkan akaun anda sendiri.
- Anda tidak boleh menyunting akaun yang memegang kebenaran yang tidak anda miliki (pentadbir yang mempunyai keistimewaan lebih tinggi).
- Menukar e-mel akan menetapkan semula pengesahan e-mel, menamatkan setiap sesi log masuk pengguna itu dan merekodkan peristiwa keselamatan `E-mel ditukar`.
- Apabila pengguna menukar kata laluannya, sesi pada peranti lain dan log masuk automatik (kekalkan log masuk) ditamatkan, dan peristiwa keselamatan `Kata laluan ditukar` direkodkan.

## Mencipta dan menyunting peranan

1. Pergi ke **Pengguna dan akses > Peranan** dan tekan **Cipta**, atau **Sunting** pada sesuatu peranan.
2. **Nama dalaman** menggunakan huruf kecil dan garis bawah (contohnya `exam_office`). Sebaiknya jangan menukarnya selepas peranan dicipta.
3. Masukkan **Nama paparan** mengikut bahasa. Nama dalam bahasa Inggeris wajib diisi. Mengosongkan medan sesuatu bahasa akan membuang nama dalam bahasa itu.
4. Di bawah **Kebenaran**, tandakan kebenaran mengikut ciri. Semasa menyimpan, kebenaran yang ditambah dan dibuang ditulis ke log audit.

| Jenis peranan | Penyuntingan | Pemadaman |
|---|---|---|
| Peranan sistem (pentadbir sistem) | Nama sahaja. Sentiasa mempunyai semua kebenaran | Tidak boleh |
| Peranan persona | Nama paparan dan kebenaran. Bukan nama dalaman | Tidak boleh |
| Peranan biasa | Semuanya | Hanya apabila tiada penetapan yang aktif |

## Menetapkan peranan

1. Pergi ke **Pengguna dan akses > Pengguna**, buka pengguna itu dan tatal ke senarai **Penetapan peranan** di bawah.
2. Tekan **Tetapkan peranan** dan masukkan peranan, skop, mula/tamat (pilihan) dan **Sebab**.
   - Jika skopnya unit organisasi atau program, pilih sasarannya di bawah **Sasaran skop**. Memilih unit organisasi turut merangkumi setiap unit di bawahnya.
   - Biarkan tarikh mula kosong untuk berkuat kuasa serta-merta; biarkan tarikh tamat kosong untuk penetapan tanpa had masa.
3. Penetapan tidak pernah disunting. Untuk menukarnya, gunakan **Tamatkan penetapan** pada penetapan sedia ada (sebab wajib diisi) dan cipta penetapan baharu. Sejarahnya kekal utuh.

Anda tidak boleh menamatkan penetapan anda sendiri (pengasingan tugas). Minta bantuan pentadbir lain.

Peraturan yang menghalang peningkatan keistimewaan

- Anda tidak boleh menetapkan peranan kepada diri sendiri.
- Anda tidak boleh menetapkan peranan yang mengandungi kebenaran yang tidak anda miliki.
- Skop hanya boleh dipilih dalam lingkungan skop kebenaran `Tetapkan dan tamatkan peranan` anda sendiri. Contohnya, pentadbir yang memegangnya untuk Fakulti Kejuruteraan tidak boleh menetapkan skop Semua atau skop fakulti lain.
- Perintah `security:create-administrator` yang mencipta pentadbir pertama dijalankan oleh pengendali pelayan, jadi peraturan ini tidak terpakai kepadanya.
