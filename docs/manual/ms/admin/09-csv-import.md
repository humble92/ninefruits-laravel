# 9. Import CSV

Gunakan butang **Import CSV** di bahagian atas skrin unit organisasi, program, bangunan, bilik dan senarai kod (kebenaran `Import data rujukan daripada CSV`). Senarai rekod pelajar dan senarai pensyarah mempunyai butang yang sama (masing-masing memerlukan kebenaran `Cipta dan sunting rekod pelajar` dan `Urus pensyarah`). Fail yang sangat besar boleh diimport dengan cara yang sama oleh pengendali IT menggunakan perintah `reference:import`.

## Peraturan umum

- Baris pertama mengandungi nama lajur (lihat jadual di bawah). Pengekodan ialah UTF-8; dalam Excel, simpan sebagai "CSV UTF-8".
- Baris yang kodnya sudah wujud akan **mengemas kini** rekod tersebut. Mengimport fail yang sama sekali lagi tidak mencipta pendua.
- Lajur mengikut bahasa menggunakan bentuk `lajur_bahasa`: `name_en`, `name_ko`, `name_zh_CN`. Baris baharu memerlukan nama dalam bahasa Inggeris (`name_en`). Sel kosong tidak memadamkan terjemahan sedia ada.
- Tarikh ditulis sebagai `2026-03-01`; ya/tidak sebagai `1`/`0` atau `true`/`false`.
- Import dijalankan di latar belakang. Apabila selesai, keputusannya tiba sebagai pemberitahuan di penjuru kanan atas panel pentadbiran; jika ada baris yang gagal, **Muat turun maklumat tentang baris yang gagal** memberikan fail CSV yang mengandungi sebab bagi setiap baris. Betulkan fail itu dan import semula fail itu sahaja.
- Jika nama lajur pada skrin berbeza, anda boleh memadankan lajur secara manual dalam dialog import.

## Format lajur

| Jenis | Lajur wajib | Lajur pilihan |
|---|---|---|
| Unit organisasi | `code`, `type` (kod jenis unit organisasi), `name_en`, `effective_start_date` | `parent` (kod induk), `name_ko` dsb., `short_name_*`, `effective_end_date`, `sort_order` |
| Program | `code`, `org_unit` (kod unit organisasi), `degree_level`, `name_en`, `effective_start_date` | `degree_title_*`, `standard_duration_years`, `effective_end_date`, `allows_second_major`, `allows_minor` |
| Bangunan | `code`, `name_en` | `name_*`, `address`, `is_active` |
| Bilik | `building` (kod bangunan), `code` (nombor bilik) | `name_*`, `room_type` (kod jenis bilik), `org_unit`, `capacity`, `is_schedulable`, `is_active` |
| Nilai kod | `type` (kod senarai), `code`, `name_en` | `name_*`, `sort_order`, `is_active` |

Nilai `degree_level`: `associate`, `bachelor`, `master`, `doctorate`, `certificate`, `non_degree`

Bagi unit organisasi, unit induk mesti didahulukan (susun sebagai universiti → fakulti → jabatan).

## Contoh: unit organisasi

```csv
code,type,parent,name_en,name_ko,effective_start_date
UNI,university,,Sample University,샘플대학교,1990-03-01
ENG,college,UNI,College of Engineering,공과대학,1990-03-01
CSE,department,ENG,Computer Science,컴퓨터공학과,1995-03-01
```

Import bagi pelajar dan pensyarah (Fasa 2) serta kursus (Fasa 4) ditambah bersama ciri-ciri tersebut.

## Pelajar dan pensyarah

Rekod pelajar dipadankan dan dikemas kini mengikut `student_number`, manakala pensyarah mengikut `employee_number`. Kedua-dua jenis berkongsi lajur individu.

| Jenis | Lajur wajib bagi baris baharu | Lajur pilihan |
|---|---|---|
| Rekod pelajar | `student_number`, `program` (kod program), `admission_date`, `family_name` | `catalog_year` (tahun kemasukan jika kosong), `year_level`, `status`, `graduation_date`, `institutional_email` |
| Pensyarah | `employee_number`, `org_unit` (kod unit organisasi), `employment_type`, `family_name` | `academic_title_*`, `hire_date`, `end_date` |
| Lajur individu (dikongsi) | — | `given_name`, nama mengikut bahasa seperti `name_ko`, `birth_date`, `gender`, `email`, `phone`, `national_id` |

- Nilai `status`: `enrolled`, `on_leave`, `withdrawn`, `dismissed`, `graduated` (`graduation_date` wajib bagi graduated). Status yang ditukar melalui import dianggap sebagai **pembetulan data** dan tidak mencipta sejarah perubahan status. Perubahan sebenar direkodkan pada skrin ([bab 13](13-status-changes-and-advisors.md)).
- Nilai `employment_type`: `full_time`, `part_time`, `adjunct`, `visiting`, `emeritus`. Nilai `gender`: `female`, `male`, `other`, `undisclosed`.
- Jika `national_id` bagi rekod pelajar baharu sepadan dengan individu sedia ada, rekod itu dilampirkan kepada individu tersebut (kemasukan semula). Jika tidak, individu baharu dicipta. Semasa kemas kini, sel kosong tidak memadamkan nilai sedia ada.
- Pelajar dan pensyarah yang diimport tidak mempunyai akaun. Hantar jemputan kepada mereka melalui [jemputan akaun](14-merge-and-invitations.md#jemputan-akaun).

```csv
student_number,program,admission_date,year_level,status,family_name,given_name,name_ko,birth_date
202600001,B-CSE,2026-03-02,1,enrolled,Kim,Minsu,김민수,2007-05-01
```

## Kursus

**Import CSV** di bahagian atas senarai kursus (kebenaran `Urus kursus, versi dan syarat kursus`) atau `reference:import courses`. Satu baris ialah satu versi kursus.

| Lajur wajib | Lajur pilihan |
|---|---|
| `code`, `effective_start_date`, `title_en`, `credits` (versi baharu) | `org_unit` (wajib bagi kursus baharu; kod unit organisasi pengurus), `effective_end_date`, `title_*`, `lecture_hours`, `lab_hours`, `category` (kod kategori kursus), `grading_basis` (`graded`, `pass_fail`), `counts_toward_credit_limit` |

- Kursus dipadankan mengikut `code`, versi pula mengikut `code` + `effective_start_date`. Mengimport fail yang sama sekali lagi akan mengemas kininya.
- Sejarah semakan boleh dimuatkan daripada satu fail: senaraikan baris bagi kod yang sama mengikut tempoh (letakkan `effective_end_date` pada versi yang lebih awal).
- Baris yang tempohnya bertindih dengan versi lain akan gagal. Jika baris bagi kursus baharu gagal, kursus itu juga tidak dicipta.
- Syarat kursus, kursus setara dan syarat bergraduat dimasukkan pada skrin ([bab 17](17-courses-and-curriculum.md)).

```csv
code,org_unit,effective_start_date,effective_end_date,title_en,title_ko,credits,category
CSE2010,CSE,2015-03-01,2026-02-28,Data Structures,자료구조,3,MAJ_REQ
CSE2010,,2026-03-01,,Data Structures,자료구조,4,MAJ_REQ
```

## Sekatan pendaftaran

**Import CSV** di bahagian atas senarai **Pendaftaran kursus > Sekatan** (kebenaran `Kenakan dan tarik balik sekatan, berikan kelulusan khas peraturan`) atau `reference:import registration_holds`. Digunakan untuk memuatkan data `SRDENY` sistem lama atau senarai pelajar yang belum membayar yuran daripada pejabat kewangan.

| Lajur wajib | Lajur pilihan |
|---|---|
| `student_number`, `hold_type` (`unregistered`, `financial`, `academic`, `administrative`, `other`), `reason` | `starts_at` (lalai: masa import), `ends_at`, `blocks_registration` (lalai 1), `blocks_grades`, `blocks_certificates` |

- Setiap baris mencipta sekatan baharu (sumber `import`). Mengimport fail yang sama dua kali akan mencipta sekatan dua kali, jadi semak senarai sebelum mengimport semula.
- Baris dengan nombor matrik yang tidak diketahui akan gagal. Sekatan ditarik balik pada skrin bersama sebab ([bab 20](20-registration.md)).

```csv
student_number,hold_type,reason,ends_at
202312345,financial,2027-1 등록금 미납,
202254321,academic,학사경고 상담 필요,2027-03-15
```
