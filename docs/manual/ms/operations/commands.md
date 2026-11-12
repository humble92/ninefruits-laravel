# Rujukan arahan Artisan

- Pembaca: pengendali IT
- Skop: setiap arahan aplikasi yang didaftarkan dalam repositori ini. Apabila arahan ditambah, ia juga mesti ditambah di sini, jika tidak CI gagal (`tests/Feature/Tooling/CommandDocumentationTest.php`).

## Konvensyen umum

Setiap arahan dijalankan sebagai `php artisan {command}`. Dalam persekitaran pembangunan, `./vendor/bin/sail artisan {command}`.

| Perkara | Peraturan |
|---|---|
| Rekod audit | Arahan yang mengubah data ("Audit: direkodkan" di bawah) menulis arahan, argumen, pilihan, pengguna OS, kod keluar dan hasil ke log aktiviti (log `command`) pada setiap larian. Semaknya di bawah **Audit > Log aktiviti** dalam panel pentadbiran |
| ID larian | Setiap larian menerima ID yang bermula dengan `cmd-` sebagai ganti ID permintaan. Log aktiviti, perubahan data yang dibuat oleh larian itu dan tugas baris gilir yang dihantarnya semuanya membawa ID yang sama, jadi soalan "arahan manakah yang membuat perubahan ini" dijawab oleh ID tersebut. `--actor` ialah nilai yang dibekalkan oleh pengendali, jadi ia tidak dimasukkan ke dalam lajur pengguna perubahan data (pelaku sebenar ialah pengguna OS) |
| `--actor=<email>` | Sama bagi semua arahan yang diaudit. Merekodkan akaun pentadbir yang bertanggungjawab. E-mel yang tidak dikenali menghentikan larian |
| `--dry-run` | Memaparkan hasil tanpa mengubah apa-apa. Tersedia pada operasi yang sukar dibatalkan |
| `--force` | Berjalan tanpa gesaan pengesahan (untuk jadual dan skrip pelaksanaan) |
| `--reason=` | Tersedia pada arahan yang memerlukan sebab |
| Kod keluar | 0 berjaya, 1 gagal atau dibatalkan, 2 arahan semakan menemui masalah |
| Konteks pangkalan data | Semasa arahan berjalan, pelaku sesi pangkalan data ditetapkan kepada `system:command:{command}` dan muncul dalam perubahan data (trigger) |

## Pemasangan dan demo

### `setup:install`

- Tujuan: pemasangan baharu dan kemas kini. Menjalankan migrasi, data penting, nilai lalai institusi, profil pemasangan dan pentadbir awal secara berturutan. Selamat dijalankan semula (data sedia ada dan nilai yang diubah oleh pentadbir dikekalkan).
- Bila: semasa pemasangan pertama dan pada setiap pelaksanaan.
- Pilihan: `--institution-name=`, `--locale=` (bahasa lalai), `--timezone=` (contohnya `Asia/Seoul`), `--profile=*` (kunci profil pemasangan), `--grade-scale=` (skala gred `4.5` atau `4.3`. Tidak boleh diubah setelah ditetapkan. Jika kosong, skala profil pertama digunakan), `--admin-email=`, `--admin-name=`, `--force` (berjalan tanpa soalan. Kata laluan pentadbir dibaca daripada input standard).
- Audit: direkodkan
- Contoh

  ```bash
  php artisan setup:install
  echo "$ADMIN_PASSWORD" | php artisan setup:install --force --locale=ko --timezone=Asia/Seoul \
      --institution-name="Demo University" --admin-email=admin@example.ac.kr --admin-name="Administrator"
  ```

- Membaca output: `DONE`/`FAIL` bagi setiap langkah. `Administrator exists` bermaksud penetapan pentadbir sistem sudah wujud, jadi penciptaan dilangkau.
- Jika gagal: jika ralat menyatakan kunci tiada, masukkan nilai yang dijana oleh `key:generate` dan `pii:generate-key` ke dalam konfigurasi persekitaran. Kegagalan migrasi biasanya bermaksud peranan pangkalan data belum disediakan (lihat manual pemasangan).

### `setup:demo`

- Tujuan: mencipta data bagi sebuah universiti rekaan menggunakan factory. Memaparkan akaun demo tetap bagi setiap peranan (`registrar@demo.test` dan sebagainya).
- Bila: persekitaran pembangunan, latihan dan demonstrasi. **Ia tidak akan berjalan dalam produksi (`APP_ENV=production`), walaupun dengan `--force`.**
- Pilihan: `--size=tiny|small|medium|large` (kira-kira 20/100/2,000/20,000 pelajar. `tiny` untuk ujian dan semakan pantas), `--seed=` (nilai yang sama menghasilkan data yang sama), `--force`.
- `--student-accounts`: mencipta akaun bagi setiap pelajar aktif (`student{student number}@demo.test`, kata laluan demo) dan menulisnya ke `storage/app/private/load-accounts.csv`. Untuk ujian beban ([Ujian beban](load-testing.md)).
- Audit: direkodkan
- Contoh: `php artisan setup:demo --size=small --seed=42`
- Jika gagal: jika data penting tiada, akaun demo tidak mempunyai peranan dan dilangkau. Jalankan `setup:install` terlebih dahulu.

## Data rujukan dan semester

### `reference:import`

- Tujuan: mengimport data rujukan daripada CSV. Menggunakan peraturan import yang sama seperti **Import CSV** dalam panel pentadbiran (baris sedia ada dipadankan mengikut kod dan dikemas kini, sel terjemahan yang kosong mengekalkan nilai sedia ada, hanya baris yang gagal direkodkan secara berasingan).
- Argumen: `{type}` salah satu daripada `org_units`, `programs`, `buildings`, `rooms`, `lookup_values`, `students`, `instructors`, `courses`, `registration_holds` / `{file}` CSV UTF-8 dengan baris pengepala.
- Pilihan: `--actor=` (wajib. Rekod import dan pemberitahuan penyiapan dihantar ke akaun ini), `--chunk=100` (baris bagi setiap tugas baris gilir), `--dry-run` (semakan pengepala dan kiraan baris sahaja).
- Audit: direkodkan (jenis, nama fail, kiraan baris, kiraan baris yang gagal)
- Format lajur: [Manual pentadbir bab 9](../admin/09-csv-import.md).
- Kod keluar: jika baris gilir berjalan secara segerak, hasil dipaparkan serta-merta dan kod keluar ialah 2 apabila terdapat baris yang gagal. Jika tak segerak (Horizon), import hanya diterima dan kod keluar ialah 0. Hasilnya tiba sebagai CSV baris yang gagal dalam pemberitahuan panel pentadbiran bagi akaun `--actor`.
- Contoh: `php artisan reference:import org_units storage/app/org-units.csv --actor=admin@example.ac.kr --dry-run`

### `term:inspect`

- Tujuan: memaparkan semester yang sedang berjalan pada masa tertentu dan tempoh urusan yang dibuka (jenis, semester, kriteria sasaran, masa tamat). Gunakannya untuk menyemak konfigurasi semester dan tempoh urusan atau untuk menjawab soalan seperti "mengapa pendaftaran kursus tidak dibuka".
- Pilihan: `--at=` (lalai sekarang. Contohnya `"2027-02-15 09:00"`. Tanpa zon waktu ia ditafsirkan dalam zon waktu pelayan (UTC). Zon boleh diberikan seperti dalam `"2027-02-15 09:00 Asia/Seoul"`).
- Audit: tidak direkodkan
- Membaca output: keputusan tarikh menggunakan zon waktu institusi. Tempoh urusan bagi semester berstatus dirancang atau diarkibkan tidak sekali-kali dibuka.

## Keselamatan

### `security:create-administrator`

- Tujuan: mencipta akaun dengan peranan pentadbir sistem. Biasanya dipanggil oleh `setup:install`.
- Pilihan: `--email=`, `--name=`, `--password-stdin` (kata laluan daripada input standard. Wajib bagi larian bukan interaktif).
- Audit: direkodkan (ID pengguna yang dicipta)
- Contoh: `php artisan security:create-administrator --email=admin@example.ac.kr --name="Administrator"`
- Jika gagal: `system_administrator role is missing` bermaksud `setup:install` atau `db:seed` mesti dijalankan terlebih dahulu. Kata laluan mesti sekurang-kurangnya 12 aksara.

### `security:sync-permissions`

- Tujuan: menyelaraskan senarai kebenaran dalam pangkalan data dengan katalog kebenaran dalam kod. Kebenaran baharu dicipta; kebenaran yang tiada dalam katalog dipadam (dan dibuang daripada setiap peranan). Peranan sistem sentiasa menerima setiap kebenaran.
- Bila: selepas pelaksanaan (`setup:install` melakukannya sebagai sebahagian daripada langkah data penting).
- Pilihan: `--dry-run` (hanya memaparkan senarai cipta/padam), `--force` (melangkau pengesahan pemadaman).
- Audit: direkodkan (kebenaran yang dicipta/dipadam)
- Membaca output: baris `create` dan `delete`. Gesaan pengesahan muncul apabila terdapat pemadaman.
- Jika gagal: menjawab "tidak" pada pengesahan akan keluar dengan kod 1 dan tidak mengubah apa-apa.

### `security:reset-mfa`

- Tujuan: menetapkan semula pengesahan dua faktor pengguna (aplikasi pengesah, kod e-mel, kod pemulihan, peranti yang diingati). Pengguna mendaftar semula pada log masuk seterusnya. Gunakannya apabila tiada pentadbir yang dapat log masuk ke panel pentadbiran (contohnya satu-satunya pentadbir kehilangan telefon). Selain itu, gunakan **Pengguna > Set semula pengesahan dua faktor** dalam panel pentadbiran.
- Argumen/pilihan: `{user}` e-mel pengguna, `--reason=` (wajib), `--actor=` (wajib. Pentadbir yang memutuskan penetapan semula).
- Audit: direkodkan (dalam log aktiviti dan log keselamatan, bersama sebab)
- Contoh: `php artisan security:reset-mfa admin@example.ac.kr --reason="Phone lost, identity verified by phone call" --actor=it-lead@example.ac.kr`

### `security:unlock-user`

- Tujuan: membuka kunci akaun yang dikunci akibat kata laluan salah berulang kali sebelum tempoh kunci tamat. Sama seperti **Pengguna > Buka kunci log masuk** dalam panel pentadbiran.
- Argumen/pilihan: `{user}` e-mel pengguna, `--reason=` (wajib).
- Audit: direkodkan (juga dalam log keselamatan)
- Membaca output: `The account is not locked.` bermaksud tiada apa-apa yang diubah.
- Catatan: ambang kunci (percubaan gagal, tempoh kunci) ditetapkan di bawah **Tetapan > Kaedah log masuk** dalam panel pentadbiran.

## Individu dan rekod pelajar

### `people:find-duplicates`

- Tujuan: menyenaraikan individu yang kelihatan seperti orang yang sama. Kriterianya ialah nombor kad pengenalan yang sama (hash carian), atau nama rumi dan tarikh lahir yang sama. Individu yang telah digabungkan dikecualikan.
- Pilihan: `--limit=100` (bilangan kumpulan yang dipaparkan).
- Audit: tidak direkodkan (baca sahaja)
- Kod keluar: 2 apabila calon pendua wujud.
- Tindakan susulan: semak setiap kumpulan di bawah **Individu** dalam panel pentadbiran dan, jika mereka orang yang sama, jalankan **Gabungkan pendua** dari halaman individu yang hendak dikekalkan. Penggabungan tidak boleh dibatalkan ([Manual pentadbir bab 14](../admin/14-merge-and-invitations.md)).

## Kemasukan

### `admission:matriculate`

- Tujuan: mencipta rekod pelajar (nombor matrik baharu) bagi pemohon yang telah mengesahkan pendaftaran dalam sesuatu pengambilan. Sama seperti tindakan pukal **Cipta rekod pelajar** di bawah **Kemasukan > Permohonan** dalam panel pentadbiran. Setiap pemohon diproses secara berasingan, jadi satu kegagalan tidak menghentikan yang lain.
- Argumen/pilihan: `{cycle}` kod pengambilan, `--term=` (wajib. Kod semester kemasukan, contohnya `2027-SP`. Tarikh kemasukan ialah hari pertama semester), `--actor=` (wajib. Akaun dengan kebenaran `Cipta rekod pelajar bagi pemohon yang diterima masuk`. Hanya pemohon dalam skop kebenaran akaun itu diproses), `--dry-run` (senarai sasaran sahaja).
- Audit: direkodkan (pengambilan, semester, bilangan sasaran, bilangan yang dicipta, nombor permohonan yang gagal). Rekod aktiviti (`application.matriculated`) juga ditulis bagi setiap pemohon.
- Kod keluar: 2 apabila mana-mana pemohon gagal.
- Contoh: `php artisan admission:matriculate DEMO-2027-REG --term=2027-SP --actor=admissions@example.ac.kr --dry-run`
- Jika gagal: `not ready` bermaksud rekod pelajar sudah wujud atau pemohon tidak berstatus pendaftaran disahkan; `program is not in effect` bermaksud program tidak berkuat kuasa pada tarikh kemasukan (semak tempoh berkuat kuasa di bawah Data rujukan > Program).

### `admission:expire-drafts`

- Tujuan: menandakan sebagai **Luput** draf permohonan bagi pengambilan yang telah ditutup dan draf permohonan yang tidak disentuh dalam tempoh yang dikonfigurasi (**Tetapan > Kemasukan > Luputkan draf yang tidak disentuh selepas (hari)**, lalai 90 hari). Permohonan yang luput tidak menghalang permohonan baharu dalam pengambilan yang sama.
- Bila: berjadual (setiap hari pada 03:10, [Tugas berjadual](scheduled-jobs.md)).
- Pilihan: `--dry-run` (kiraan sahaja).
- Audit: direkodkan (bilangan yang diluputkan)

## Seksyen kursus

### `section:copy`

- Tujuan: memulakan perancangan semester baharu daripada seksyen semester sebelumnya. Sama seperti **Seksyen > Salin daripada semester lain** dalam panel pentadbiran. Menyalin seksyen yang tidak dibatalkan sebagai draf, bersama pensyarah (jika masih berkhidmat), waktu kelas dan bilik, peruntukan tempat duduk (masa pelepasan dianjakkan mengikut perbezaan tarikh mula semester) dan rangka kursus (sebagai draf peribadi). Versi kursus digantikan dengan versi yang berkuat kuasa pada permulaan sesi baharu.
- Argumen/pilihan: `{from-term}`, `{to-term}` kod semester (contohnya `2026-FA`), `--org-unit=*` (hanya seksyen bagi kod unit organisasi ini), `--dry-run` (hasil sahaja, tiada yang disimpan), `--actor=` (wajib. Hanya seksyen dalam skop kebenaran `Rancang seksyen` akaun itu disalin).
- Audit: direkodkan (kiraan disalin, dilangkau dan perlu disemak)
- Membaca output: `skipped` bermaksud semester sasaran sudah mempunyai kod seksyen yang sama atau tiada versi kursus yang berkuat kuasa; `note` bermaksud seksyen disalin tetapi ada sesuatu yang ditinggalkan (disalin tanpa bilik kerana bilik sudah digunakan, pensyarah yang telah bersara, dan sebagainya). Sesi dipadankan mengikut kod; jika tiada, seksyen dimasukkan ke dalam sesi semester penuh.
- Contoh: `php artisan section:copy 2026-FA 2027-FA --actor=academic@example.ac.kr --dry-run`

### `schedule:check-conflicts`

- Tujuan: menyemak seksyen sesuatu semester sebelum menerbitkan jadual waktu. Tempahan berganda bilik dan pensyarah sudah dihalang oleh pangkalan data; arahan ini mencari masalah yang dibenarkan oleh pangkalan data: kapasiti melebihi kapasiti bilik, versi kursus yang tidak berkuat kuasa pada tarikh mula sesi, pensyarah yang telah bersara, peruntukan tempat duduk melebihi kapasiti, tarikh kelas di luar sesi. Ia juga menyemak pertembungan bilik dalam data yang masuk tanpa kekangan, seperti pemulihan sandaran.
- Argumen: `{term}` kod semester.
- Audit: tidak direkodkan (baca sahaja)
- Kod keluar: 2 apabila terdapat masalah. Seksyen yang belum mempunyai pensyarah atau bilik dipaparkan sebagai maklumat sahaja.

## Pendaftaran kursus

### `registration:preflight`

- Tujuan: menyemak semester sebelum membuka pendaftaran kursus. Memaparkan tempoh pendaftaran kursus dan tambah/gugur, set peraturan, bilangan seksyen yang diterbitkan, seksyen dengan kapasiti 0, peruntukan tempat duduk melebihi kapasiti, versi kursus yang tidak berkuat kuasa pada tarikh mula sesi, bilangan pelajar aktif yang tidak diliputi oleh had kredit, dan bilangan pelajar yang dikenakan sekatan pendaftaran.
- Argumen: `{term}` kod semester (contohnya `2027-SP`).
- Audit: tidak direkodkan (baca sahaja)
- Kod keluar: 2 apabila terdapat masalah. Set peraturan yang tiada dipaparkan sebagai maklumat, bukan sebagai masalah (nilai lalai peraturan digunakan).
- Contoh: `php artisan registration:preflight 2027-SP`

### `registration:simulate`

- Tujuan: memaparkan, peraturan demi peraturan, apa yang akan berlaku jika pelajar mendaftar seksyen itu sekarang (atau pada masa tertentu). Tiada apa-apa yang disimpan dan tiada percubaan direkodkan. Gunakannya untuk soalan "mengapa saya tidak boleh mendaftar?".
- Argumen: `{student-number}` nombor matrik, `{section}` `kod kursus-kod seksyen` (contohnya `CSE2010-01`).
- Pilihan: `--term=` (kod semester. Lalai ialah semester terkini yang mempunyai seksyen itu), `--at=` (masa penilaian. Contohnya `"2027-02-10 10:00 Asia/Seoul"`).
- Audit: tidak direkodkan (baca sahaja)
- Membaca output: bagi setiap peraturan, lulus/gagal, sekat/amaran dan mesejnya. Peraturan yang diliputi oleh kelulusan khas dipaparkan sebagai gagal tetapi tidak menyekat. Kapasiti dipaparkan secara berasingan di bawah jadual peraturan.
- Kod keluar: 2 apabila pendaftaran akan ditolak.

### `waitlist:promote`

- Tujuan: mendaftarkan pelajar dalam senarai menunggu mengikut giliran bagi seksyen yang mempunyai tempat kosong. Setiap peraturan disemak semula semasa kenaikan; entri senarai menunggu yang gagal sesuatu peraturan dibuang daripada senarai menunggu (dengan sebab direkodkan). Senarai menunggu bagi seksyen yang tempoh pendaftaran kursus dan tambah/gugurnya kedua-duanya telah ditutup akan luput. Pelajar menerima hasilnya melalui e-mel.
- Argumen/pilihan: `{term?}` kod semester (lalai: setiap semester yang mempunyai entri senarai menunggu), `--actor=`.
- Audit: direkodkan (kiraan seksyen, dinaikkan, dibuang, luput)
- Bila: apabila berlaku gugur, tarik diri atau pelepasan peruntukan tempat duduk, seksyen yang terjejas diproses serta-merta oleh tugas baris gilir. Arahan ini ialah jaringan keselamatan berjadual setiap jam yang mengendalikan apa-apa yang terlepas.
- Contoh: `php artisan waitlist:promote 2027-SP --actor=registrar@example.ac.kr`

### `registration:release-reserved-seats`

- Tujuan: menandakan peruntukan tempat duduk yang masa pelepasannya telah berlalu (contohnya tempat yang ditempah untuk pelajar major) sebagai telah diproses dan memasukkan tugas kenaikan senarai menunggu bagi seksyen tersebut ke dalam baris gilir. Tempat yang ditempah menjadi tempat umum dalam pengiraan sebaik sahaja masa pelepasan berlalu, jadi arahan ini hanya menyerahkan tempat itu kepada senarai menunggu.
- Audit: tidak direkodkan (berjalan setiap 5 minit. Hasil kenaikan terdapat dalam rekod pendaftaran kursus)
- Bila: berjadual (setiap 5 minit).

## Gred

### `term:close`

- Tujuan: menutup semester. Setiap gred dalam semester mesti dimuktamadkan. Bagi setiap pelajar ia mengira rekod semester (kredit, PNG dan PNGK), menentukan kedudukan akademik (kedudukan baik, amaran akademik, semakan pemberhentian) dan kelayakan Senarai Dekan (kelompok baris gilir), kemudian menyusun kedudukan Senarai Dekan bagi setiap program, menutup rekod semester sebagai rekod rasmi dan menetapkan semester kepada **Gred ditutup**. Sama seperti **Gred > Rekod semester > Tutup semester** dalam panel pentadbiran.
- Argumen/pilihan: `{term}` kod semester, `--dry-run` (hanya menyemak sama ada semester boleh ditutup), `--reason=` (sebab yang disimpan dalam rekod), `--actor=`.
- Audit: direkodkan (bilangan pelajar, bilangan seksyen dengan gred yang belum dimuktamadkan). Apabila penutupan selesai, rekod audit `term.closed` menyimpan kiraan amaran akademik, semakan pemberhentian dan Senarai Dekan.
- Kod keluar: 2 apabila sesetengah seksyen mempunyai gred yang belum dimuktamadkan (kiraan yang belum dimuktamadkan bagi setiap seksyen dipaparkan). 1 apabila semester tidak berstatus diterbitkan.
- Bila: selepas kemasukan gred dan pemuktamadan selesai. Ikuti kemajuan kelompok dalam Horizon. Tanpa pekerja baris gilir, status semester tidak berubah.
- Contoh: `php artisan term:close 2026-FA --dry-run`, `php artisan term:close 2026-FA --reason="Fall 2026 grades closed" --actor=registrar@example.ac.kr`

### `grades:recalculate`

- Tujuan: mengira semula rekod semester (kredit, PNG dan PNGK) daripada gred yang telah dimuktamadkan. Gunakannya untuk menerapkan dasar penggredan yang telah diubah (tempat perpuluhan PNG, pengendalian ulang kursus) pada semester lalu. Kedudukan akademik dan Senarai Dekan mengekalkan nilai yang diputuskan semasa tutup semester. Pindaan gred yang diluluskan mengira semula pelajar itu secara automatik, jadi arahan ini tidak diperlukan untuknya.
- Pilihan: `--term=` (pelajar yang mempunyai gred dalam semester itu, dari semester itu dan seterusnya), `--student-number=` (seorang pelajar), `--reason=` (wajib), `--actor=`.
- Audit: direkodkan (bilangan pelajar, sebab)
- Contoh: `php artisan grades:recalculate --term=2026-SP --reason="GPA changed to three decimal places"`

### `grades:remind-missing`

- Tujuan: menghantar e-mel kepada pensyarah seksyen yang grednya belum dihantar (belum dimasukkan atau disimpan sebagai draf). Seksyen dikumpulkan mengikut pensyarah menjadi satu mesej bagi setiap seorang.
- Argumen/pilihan: `{term}` kod semester, `--dry-run` (memaparkan sasaran tanpa menghantar), `--actor=`.
- Audit: direkodkan (seksyen, pensyarah, mesej yang dihantar)
- Bila: beberapa hari sebelum tempoh kemasukan gred ditutup. Tambahkannya ke jadual jika perlu.

## Penilaian kursus

### `evaluation:remind`

- Tujuan: menghantar e-mel kepada pelajar yang masih mempunyai kursus yang belum dinilai semasa tempoh penilaian kursus dibuka. Kursus yang berbaki dikumpulkan bagi setiap pelajar menjadi satu mesej.
- Argumen/pilihan: `{term}` kod semester, `--dry-run` (bilangan pelajar sasaran sahaja, tiada yang dihantar), `--actor=`.
- Audit: direkodkan (pelajar sasaran, mesej yang dihantar)
- Bila: sekali atau dua kali semasa tempoh penilaian. Tambahkannya ke jadual jika perlu.

### `evaluation:publish-results`

- Tujuan: menerbitkan keputusan penilaian kursus semester kepada pensyarah. Sama seperti **Penilaian kursus > Penilaian semester > Terbitkan keputusan** dalam panel pentadbiran. Walaupun selepas diterbitkan, keputusan hanya kelihatan bagi seksyen yang mencapai bilangan respons dan kadar respons minimum.
- Argumen/pilihan: `{term}` kod semester, `--actor=`.
- Audit: direkodkan (bilangan respons, sama ada sudah diterbitkan)
- Bila: selepas tempoh penilaian dan pemprosesan gred selesai. Semester yang sudah diterbitkan tidak diubah.

## Sijil

### `certificate:verify`

- Tujuan: menyemak kod pengesahan sijil dengan kriteria yang sama seperti halaman pengesahan awam. Memaparkan jenis, nombor pengeluaran, tarikh dikeluarkan dan pemegang (bertopeng).
- Argumen/pilihan: `{code}` kod pengesahan.
- Audit: tiada (baca sahaja)
- Kod keluar: 0 apabila sah, 2 bagi kod yang tidak dikenali, sijil yang dibatalkan atau tempoh sah yang telah tamat.

### `certificate:render`

- Tujuan: menjana semula PDF sijil yang telah dikeluarkan dengan data dan templat semasa, menggantikan fail yang diarkibkan. Sama seperti **Sijil dikeluarkan > Jana semula** dalam panel pentadbiran. Nombor pengeluaran dan kod pengesahan kekal sama; checksum berubah.
- Argumen/pilihan: `{issue}` nombor pengeluaran, `--reason=` (wajib), `--actor=`.
- Audit: direkodkan (nombor pengeluaran, checksum baharu)
- Bila: untuk menjana semula sijil tertentu selepas membetulkan templat atau ejaan nama.

## Peperiksaan

### `assessment:import-results`

- Tujuan: memasukkan keputusan sesi peperiksaan daripada CSV. Peraturan yang sama seperti **Sesi peperiksaan > Import keputusan (CSV)** dalam panel pentadbiran (pelajar berdaftar sahaja, lulus/gagal ditentukan daripada markah, memasukkan semula sebelum penerbitan menggantikan keputusan).
- Argumen/pilihan: `{type}` kod jenis peperiksaan / `{held_date}` tarikh peperiksaan `YYYY-MM-DD` / `{file}` CSV UTF-8 dengan baris pengepala, `--actor=` (wajib), `--dry-run` (pengesahan baris sahaja, tiada yang direkodkan).
- Format lajur: [Manual pentadbir bab 24](../admin/24-assessments.md).
- Audit: direkodkan (sesi, nama fail, kiraan baris, kiraan dimasukkan/tidak hadir/ralat)
- Kod keluar: 2 apabila terdapat baris ralat (baris lain tetap direkodkan).
- Contoh: `php artisan assessment:import-results ENG_COMP 2026-05-15 storage/app/eng.csv --actor=registrar@example.ac.kr --dry-run`

### `assessment:publish-results`

- Tujuan: menerbitkan keputusan sesuatu sesi kepada pelajar. Sama seperti **Sesi peperiksaan > Terbitkan keputusan** dalam panel pentadbiran.
- Argumen/pilihan: `{type}` kod jenis peperiksaan / `{held_date}` tarikh peperiksaan, `--actor=` (wajib).
- Audit: direkodkan (sesi, bilangan keputusan)
- Bila: selepas kemasukan keputusan selesai. Sesi yang sudah diterbitkan akan gagal.

## Kehadiran

### `attendance:generate-sessions`

- Tujuan: mencipta sesi kelas bagi setiap seksyen sesuatu semester daripada waktu kelasnya (hari, masa, slot masa). Sama seperti **Jana sesi kelas** dalam portal pensyarah dan panel pentadbiran. Sesi kelas sedia ada dan kehadirannya dikekalkan (selamat dijalankan semula).
- Argumen/pilihan: `{term}` kod semester, `--section=` (kod kursus-kod seksyen, contohnya `CSE2010-01`, satu sahaja), `--dry-run` (bilangan seksyen sasaran sahaja), `--actor=`.
- Audit: direkodkan (semester, seksyen, sesi kelas yang dicipta)
- Bila: sebelum semester bermula, selepas waktu kelas disahkan. Jalankan semula apabila waktu kelas berubah.

## Pengijazahan

### `degree-audit:run`

- Tujuan: menyimpan semakan syarat bergraduat bagi setiap pelajar aktif. Sama seperti **Semakan bergraduat > Jalankan semakan** dalam panel pentadbiran. Semakan terkini yang disimpan menjadi asas pemprosesan pengijazahan.
- Pilihan: `--term=` (kod semester tempat semakan itu dimiliki), `--student-number=` (seorang pelajar), `--min-year-level=` (hanya tahun pengajian ini dan ke atas), `--actor=`.
- Audit: direkodkan (bilangan pelajar, bilangan yang memenuhi syarat)
- Bila: sebelum semakan pengijazahan (akhir semester), dan selepas mengubah set syarat atau kursus setara.

## Integrasi LMS

### `lms:sync`

- Tujuan: menolak seksyen yang diterbitkan ke platform (penciptaan dan pemautan kursus, pensyarah, pelajar berdaftar). Melakukan bagi seluruh semester apa yang dilakukan oleh **Pautan kursus > Segerakkan semula** dalam panel pentadbiran. Seksyen yang sudah dipautkan hanya dipastikan semula (idempoten).
- Argumen/pilihan: `{platform}` kod platform, `--term=` (kod semester, lalai setiap semester yang belum tamat), `--section=` (satu kod kursus-kod seksyen), `--dry-run` (hanya merekodkan apa yang akan dihantar sebagai "skipped"), `--actor=`.
- Audit: direkodkan (platform, seksyen, ahli, kegagalan)
- Kod keluar: 2 apabila mana-mana seksyen gagal.

### `lms:reconcile`

- Tujuan: membandingkan senarai pelajar kursus di platform dengan senarai pendaftaran dalam sistem maklumat pelajar, menambah ahli yang tiada dan membuang ahli yang berlebihan. Perbezaan disimpan dalam log penyegerakan (`reconcile`).
- Argumen/pilihan: `{platform?}` (setiap platform aktif jika tidak dinyatakan), `--term=`, `--dry-run` (merekodkan perbezaan sahaja), `--actor=`.
- Audit: direkodkan (seksyen bagi setiap platform, kiraan ditambah/dibuang/gagal)
- Bila: berjadual setiap hari pada 02:30. Jalankan secara manual selepas gangguan platform.

## Pemeriksaan operasi dan pembersihan

### `system:health-check`

- Tujuan: memeriksa konfigurasi dan kunci (APP_KEY, kunci PII, APP_DEBUG), sambungan dan peranan pangkalan data (runtime, migrator, reporting), RLS pada jadual data peribadi, migrasi yang belum dijalankan, penyegerakan katalog kebenaran, Redis, baris gilir (Horizon), Gotenberg, penulisan storan media dan denyutan penjadual.
- Pilihan: `--json` (output untuk pemantauan).
- Audit: tiada (baca sahaja)
- Kod keluar: 2 apabila mana-mana pemeriksaan gagal.
- Bila: sejurus selepas pelaksanaan, apabila gangguan disyaki, secara berkala daripada sistem pemantauan.

### `audit:seal`

- Tujuan: memautkan baris baharu jadual audit ke dalam rantaian hash ([Integriti audit](audit-and-maintenance.md)).
- Pilihan: `--table=` (salah satu daripada record_changes, activity_log, security_events; boleh diulang), `--actor=`.
- Audit: direkodkan (baris yang dimeterai bagi setiap jadual)
- Bila: berjadual setiap hari pada 03:30.

### `audit:verify-chain`

- Tujuan: mengira semula hash kandungan dan rantaian baris audit untuk mengesan pengubahan tanpa kebenaran, pemadaman dan penyusunan semula.
- Pilihan: `--table=`.
- Audit: tiada (baca sahaja)
- Kod keluar: 2 apabila terdapat ketidakpadanan.
- Bila: sebelum sandaran, semasa semakan keselamatan, selepas pemangkasan.

### `audit:prune`

- Tujuan: memadam baris audit yang **telah dimeterai** dan melepasi tempoh simpanan. Berjalan pada sambungan pemilik skema.
- Pilihan: `--before=YYYY-MM-DD` (wajib), `--table=`, `--dry-run` (kiraan sahaja), `--actor=`.
- Audit: direkodkan (tarikh potong, baris yang dipadam bagi setiap jadual)
- Bila: mengikut dasar tempoh simpanan, selepas sandaran.

### `media:prune-orphans`

- Tujuan: memadam baris media yang pemiliknya sudah tiada dan direktori tanpa baris (foto individu, dokumen permohonan, PDF sijil, bukti peperiksaan).
- Pilihan: `--dry-run` (senarai sahaja), `--actor=`.
- Audit: direkodkan (baris, direktori)
- Bila: setiap bulan atau selepas pemadaman pukal.

## Kunci penyulitan data peribadi

### `pii:generate-key`

- Tujuan: memaparkan kunci baharu untuk `PII_ENCRYPTION_KEY` dan `PII_HASH_KEY`. Tidak menyimpan apa-apa.
- Audit: tidak direkodkan
- Amaran: mengubah `PII_HASH_KEY` menjadikan setiap hash carian tidak sah. Prosedur penggiliran kunci terdapat dalam [security-keys.md](security-keys.md).

### `pii:reencrypt`

- Tujuan: selepas penggiliran kunci, menyulitkan semula data peribadi yang disulitkan dengan kunci sebelumnya menggunakan kunci semasa. Berjalan sebagai kelompok baris gilir.
- Argumen: kelas model (pada masa ini `App\\Models\\Person\\Person`).
- Pilihan: `--chunk=500`, `--dry-run` (hanya mengira rekod yang menggunakan kunci sebelumnya), `--force`.
- Audit: direkodkan (ID kelompok, bilangan tugas / kiraan pratonton)
- Pengesahan: ikuti kemajuan kelompok dalam Horizon (`/horizon`). Apabila selesai dan `--dry-run` melaporkan 0, buang kunci sebelumnya daripada `PII_PREVIOUS_KEYS`.

## Penyetempatan

### `i18n:missing`

- Tujuan: memaparkan, bagi setiap bahasa, kunci yang tiada berbanding bahasa asas (en) dan kunci yang tidak lagi digunakan.
- Argumen/pilihan: `{locale?}` (satu bahasa sahaja), `--strict` (kod keluar 2 apabila terdapat kunci yang tiada. Digunakan dalam CI).
- Audit: tidak direkodkan

### `i18n:export`

- Tujuan: mencipta fail JSON untuk diserahkan kepada penterjemah. Format: `{"key": {"source": "English text", "translation": "translation"}}`.
- Argumen/pilihan: `{locale} {path}`, `--missing` (kunci yang tiada sahaja).
- Audit: tidak direkodkan
- Contoh: `php artisan i18n:export ko storage/app/ko.json --missing`

### `i18n:import`

- Tujuan: menerapkan JSON yang diisi oleh penterjemah ke `lang/{locale}`. Hanya kunci yang wujud dalam bahasa asas diimport; terjemahan yang kosong diabaikan.
- Argumen/pilihan: `{locale} {path}`, `--dry-run` (hanya memaparkan senarai kunci yang akan ditulis).
- Audit: direkodkan
- Amaran: fail terjemahan diurus dalam repositori. Commit perubahan selepas mengimport.

## Semakan

### `naming:check`

- Tujuan: menyemak nama jadual dan lajur dalam migrasi berdasarkan [konvensyen penamaan](../../../development/naming.md).
- Pilihan: `--path=*` (direktori yang disemak, lalai `database/migrations`).
- Audit: tidak direkodkan
- Kod keluar: 2 apabila terdapat masalah.

### `pdf:sample`

- Tujuan: menjana jadual yang mencampurkan tulisan Hangul, CJK, Lao, Thai, Devanagari, Cyrillic dan Latin ke PDF untuk menyemak fon dan sambungan ke perkhidmatan PDF (Gotenberg).
- Argumen: `{path=pdf-sample.pdf}` (relatif kepada cakera tempatan `storage/app/private`).
- Audit: tidak direkodkan
- Jika gagal: bagi ralat sambungan, semak `GOTENBERG_URL` dan keadaan kontena Gotenberg.
