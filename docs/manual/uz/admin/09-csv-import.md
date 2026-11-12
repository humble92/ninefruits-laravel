# 9. CSV import

Tashkiliy birliklar, taʼlim dasturlari, binolar, auditoriyalar va kodlar roʻyxati ekranlarining yuqorisidagi **CSV import qilish** tugmasidan foydalaning (`Asosiy maʼlumotlarni CSV dan import qilish` ruxsati). Talaba yozuvlari va oʻqituvchilar roʻyxatlarida ham xuddi shunday tugma bor (mos ravishda `Talaba yozuvlarini yaratish va tahrirlash` va `Oʻqituvchilarni boshqarish` ruxsatlari). Juda katta fayllarni IT operatori `reference:import` buyrugʻi bilan xuddi shu tarzda import qilishi mumkin.

## Umumiy qoidalar

- Birinchi qatorda ustun nomlari boʻladi (quyidagi jadvallarga qarang). Kodlash UTF-8; Excel da "CSV UTF-8" formatida saqlang.
- Kodi allaqachon mavjud boʻlgan qator shu yozuvni **yangilaydi**. Bir xil faylni qayta import qilish takroriy yozuvlar yaratmaydi.
- Tillarga bogʻliq ustunlar `ustun_til` koʻrinishida yoziladi: `name_en`, `name_ko`, `name_zh_CN`. Yangi qatorlar uchun inglizcha nom (`name_en`) majburiy. Boʻsh katakchalar mavjud tarjimalarni oʻchirmaydi.
- Sanalar `2026-03-01` koʻrinishida yoziladi; ha/yoʻq qiymatlari `1`/`0` yoki `true`/`false`.
- Import fon rejimida bajariladi. Tugagach, natija boshqaruv panelining yuqori oʻng burchagida bildirishnoma sifatida keladi; agar qatorlar muvaffaqiyatsiz boʻlsa, **Muvaffaqiyatsiz qatorlar haqida ma'lumotni yuklab oling** orqali har bir qator uchun sabab koʻrsatilgan CSV olinadi. Uni tuzating va faqat shu faylni qayta import qiling.
- Agar ustun nomlari ekrandagidan farq qilsa, import oynasida ustunlarni qoʻlda moslashtirishingiz mumkin.

## Ustun formatlari

| Tur | Majburiy ustunlar | Ixtiyoriy ustunlar |
|---|---|---|
| Tashkiliy birliklar | `code`, `type` (tashkiliy birlik turi kodi), `name_en`, `effective_start_date` | `parent` (yuqori birlik kodi), `name_ko` va h.k., `short_name_*`, `effective_end_date`, `sort_order` |
| Taʼlim dasturlari | `code`, `org_unit` (tashkiliy birlik kodi), `degree_level`, `name_en`, `effective_start_date` | `degree_title_*`, `standard_duration_years`, `effective_end_date`, `allows_second_major`, `allows_minor` |
| Binolar | `code`, `name_en` | `name_*`, `address`, `is_active` |
| Auditoriyalar | `building` (bino kodi), `code` (auditoriya raqami) | `name_*`, `room_type` (auditoriya turi kodi), `org_unit`, `capacity`, `is_schedulable`, `is_active` |
| Kod qiymatlari | `type` (roʻyxat kodi), `code`, `name_en` | `name_*`, `sort_order`, `is_active` |

`degree_level` qiymatlari: `associate`, `bachelor`, `master`, `doctorate`, `certificate`, `non_degree`

Tashkiliy birliklarda yuqori birliklar oldin kelishi kerak (universitet → fakultet → kafedra tartibida saralang).

## Misol: tashkiliy birliklar

```csv
code,type,parent,name_en,name_ko,effective_start_date
UNI,university,,Sample University,샘플대학교,1990-03-01
ENG,college,UNI,College of Engineering,공과대학,1990-03-01
CSE,department,ENG,Computer Science,컴퓨터공학과,1995-03-01
```

Talabalar va oʻqituvchilar (2-bosqich) hamda fanlar (4-bosqich) uchun import shu funksiyalar bilan birga qoʻshiladi.

## Talabalar va oʻqituvchilar

Talaba yozuvlari `student_number` boʻyicha, oʻqituvchilar `employee_number` boʻyicha moslashtiriladi va yangilanadi. Ikkala tur shaxs ustunlarini birgalikda ishlatadi.

| Tur | Yangi qatorlar uchun majburiy ustunlar | Ixtiyoriy ustunlar |
|---|---|---|
| Talaba yozuvlari | `student_number`, `program` (taʼlim dasturi kodi), `admission_date`, `family_name` | `catalog_year` (boʻsh boʻlsa, qabul yili), `year_level`, `status`, `graduation_date`, `institutional_email` |
| Oʻqituvchilar | `employee_number`, `org_unit` (tashkiliy birlik kodi), `employment_type`, `family_name` | `academic_title_*`, `hire_date`, `end_date` |
| Shaxs ustunlari (umumiy) | — | `given_name`, `name_ko` kabi tillar boʻyicha ismlar, `birth_date`, `gender`, `email`, `phone`, `national_id` |

- `status` qiymatlari: `enrolled`, `on_leave`, `withdrawn`, `dismissed`, `graduated` (graduated uchun `graduation_date` majburiy). Import orqali oʻzgartirilgan maqom **maʼlumotni tuzatish** hisoblanadi va maqom oʻzgarishi tarixini yaratmaydi. Haqiqiy oʻzgarishlar ekranda qayd etiladi ([13-bob](13-status-changes-and-advisors.md)).
- `employment_type` qiymatlari: `full_time`, `part_time`, `adjunct`, `visiting`, `emeritus`. `gender` qiymatlari: `female`, `male`, `other`, `undisclosed`.
- Agar yangi talaba yozuvining `national_id` qiymati mavjud shaxsnikiga mos kelsa, yozuv shu shaxsga biriktiriladi (oʻqishga qayta tiklash). Aks holda yangi shaxs yaratiladi. Yangilashda boʻsh katakchalar mavjud qiymatlarni oʻchirmaydi.
- Import qilingan talabalar va oʻqituvchilarning hisobi boʻlmaydi. Ularga [hisobga taklif qilish](14-merge-and-invitations.md#hisobga-taklif-qilish) orqali taklifnoma yuboring.

```csv
student_number,program,admission_date,year_level,status,family_name,given_name,name_ko,birth_date
202600001,B-CSE,2026-03-02,1,enrolled,Kim,Minsu,김민수,2007-05-01
```

## Fanlar

Fanlar roʻyxati yuqorisidagi **CSV import qilish** (`Fanlar, versiyalar va rekvizitlarni boshqarish` ruxsati) yoki `reference:import courses`. Bitta qator bitta fan versiyasiga mos keladi.

| Majburiy ustunlar | Ixtiyoriy ustunlar |
|---|---|
| `code`, `effective_start_date`, `title_en`, `credits` (yangi versiya) | `org_unit` (yangi fan uchun majburiy; boshqaruvchi tashkiliy birlik kodi), `effective_end_date`, `title_*`, `lecture_hours`, `lab_hours`, `category` (fan toifasi kodi), `grading_basis` (`graded`, `pass_fail`), `counts_toward_credit_limit` |

- Fanlar `code` boʻyicha, versiyalar `code` + `effective_start_date` boʻyicha moslashtiriladi. Bir xil faylni qayta import qilish ularni yangilaydi.
- Tahrirlar tarixini bitta fayldan yuklash mumkin: bir xil kodli qatorlarni davrlar boʻyicha ketma-ket yozing (oldingi versiyaga `effective_end_date` qoʻying).
- Davri boshqa versiya bilan ustma-ust tushadigan qator muvaffaqiyatsiz boʻladi. Yangi fan qatori muvaffaqiyatsiz boʻlsa, fan ham yaratilmaydi.
- Prerekvizitlar, muqobil fanlar va bitiruv talablari ekranda kiritiladi ([17-bob](17-courses-and-curriculum.md)).

```csv
code,org_unit,effective_start_date,effective_end_date,title_en,title_ko,credits,category
CSE2010,CSE,2015-03-01,2026-02-28,Data Structures,자료구조,3,MAJ_REQ
CSE2010,,2026-03-01,,Data Structures,자료구조,4,MAJ_REQ
```

## Yozilish cheklovlari

**Fanlarga yozilish > Cheklovlar** roʻyxati yuqorisidagi **CSV import qilish** (`Cheklov qoʻyish va olib tashlash, qoidalardan istisno berish` ruxsati) yoki `reference:import registration_holds`. Eski tizimdagi `SRDENY` maʼlumotlarini yoki moliya boʻlimining toʻlov qilmagan talabalar roʻyxatini yuklash uchun ishlatiladi.

| Majburiy ustunlar | Ixtiyoriy ustunlar |
|---|---|
| `student_number`, `hold_type` (`unregistered`, `financial`, `academic`, `administrative`, `other`), `reason` | `starts_at` (standart: import vaqti), `ends_at`, `blocks_registration` (standart 1), `blocks_grades`, `blocks_certificates` |

- Har bir qator yangi cheklov yaratadi (manba `import`). Bir xil faylni ikki marta import qilish cheklovlarni ikki marta yaratadi, shuning uchun qayta import qilishdan oldin roʻyxatni tekshiring.
- Nomaʼlum talaba raqamli qatorlar muvaffaqiyatsiz boʻladi. Cheklovlar ekranda sabab koʻrsatilgan holda olib tashlanadi ([20-bob](20-registration.md)).

```csv
student_number,hold_type,reason,ends_at
202312345,financial,2027-1 등록금 미납,
202254321,academic,학사경고 상담 필요,2027-03-15
```
