# 27. Hisobotlar va reporting koʻrinishlari

Menyu: **Hisobotlar > Hisobotlar** (`Standart hisobotlarni koʻrish va yuklab olish` ruxsati). Rollar boʻyicha standart sozlamalar: Registrator, Oʻquv-uslubiy boshqarma, Kafedra yordamchisi (qamrov doirasida).

## Standart hisobotlar

Hisobot va uning filtrlarini tanlang: ekranda 500 tagacha qator koʻrsatiladi, **CSV yuklab olish** esa barchasini yuklab beradi (BOM bilan UTF-8, Excel da toʻgʻridan-toʻgʻri ochiladi). Yuklab olishlar `reports.exported` audit hodisasida qayd etiladi. `=`, `+`, `-` yoki `@` bilan boshlanadigan qiymatlar Excel ularni formula sifatida bajarmasligi uchun boshiga bitta tirnoq (`'`) qoʻyilib eksport qilinadi.

| Hisobot | Filtrlar | Mazmuni |
|---|---|---|
| Talabalar roʻyxati | Tashkiliy birlik (quyi boʻlinmalari bilan birga), holat, kurs | Talaba raqami, ism, taʼlim dasturi, tashkiliy birlik, kurs, holat, qabul sanasi, bitirish sanasi, diplom raqami |
| Boʻlinma, dastur, kurs va holat boʻyicha talabalar soni | Tashkiliy birlik, holat | Talabalar soni |
| Semestr baholari xulosasi (ogohlantirishlar, imtiyozlar) | Semestr, akademik holat | Semestr va umumiy kreditlar hamda GPA, akademik ogohlantirishlar soni, imtiyozlar reytingi ([21-bob](21-grades.md)) |
| Bitiruvga nomzodlar (oxirgi tekshiruv) | Bajarilgan yoki yoʻq | Har bir talaba va dastur boʻyicha oxirgi bitiruv talablari tekshiruvi ([26-bob](26-graduation.md)) |
| Maqom oʻzgarishlari | Semestr, davr | Oʻzgarish turi, oldingi va keyingi holatlar, kuchga kirish sanasi, soʻrov holati |
| Fan guruhlari boʻyicha yozilganlar | Semestr, tashkiliy birlik | Fan guruhi, oʻqituvchilar, oʻrinlar soni, yozilganlar, kutayotganlar |
| Semestrda fanlarga yozilmagan talabalar | Semestr, tashkiliy birlik | Shu semestrda fanlarga yozilmagan oʻqiyotgan talabalar |

Koʻrinadigan maʼlumotlar koʻruvchining ruxsat qamroviga (talaba yozuvlarini koʻrish qamrovi va shu kabilar) bogʻliq. Eski tizimning (YUSTIC) toʻlov va kontrakt hisobotlari (toʻlanmagan kontrakt va shu kabilar) qamrovdan tashqarida va mavjud emas.

## reporting koʻrinishlari

Hisobotlar maʼlumotlar bazasidagi `reporting` sxemasining koʻrinishlarini (view) oʻqiydi. BI vositalari va SQL foydalanuvchilari xuddi shu koʻrinishlarga faqat oʻqish uchun moʻljallangan `app_reporting` roli bilan soʻrov yuboradi.

| Koʻrinish | Mazmuni |
|---|---|
| `reporting.students` | Talaba yozuvi + shaxs ismi + dastur va tashkiliy birlik nomlari |
| `reporting.headcount` | Tashkiliy birlik, dastur, kurs va holat boʻyicha talabalar soni |
| `reporting.term_records` | Har bir semestr boʻyicha baholar xulosasi |
| `reporting.course_grades` | Har bir fan boʻyicha baholar (yakunlangan yoki yoʻq, baho, oʻzlashtirilgan, qayta oʻqish) |
| `reporting.section_enrollments` | Har bir fan guruhi boʻyicha yozilganlar va kutayotganlar, oʻqituvchilar |
| `reporting.section_meetings` | Dars vaqtlari va auditoriyalar |
| `reporting.status_changes` | Maqom oʻzgarishlari |
| `reporting.degree_audits` | Har bir talaba va dasturdagi oʻrin boʻyicha oxirgi bitiruv talablari tekshiruvi |

- Tarjima qilingan ustunlar (`program_name` va shu kabilar) sessiya tiliga bogʻliq: `SET app.locale = 'ko';`. Xom jsonb ustunlari (`program_name_i18n` va shu kabilar) ham mavjud.
- Koʻrinishlar `security_invoker` boʻlgani sababli koʻruvchining qator darajasidagi xavfsizlik (RLS) siyosati oʻzgarishsiz qoʻllaniladi. Talaba hisobi bilan oʻqilganda ular faqat shu talabaning qatorlarini qaytaradi.
- Koʻrinishlar taʼriflari `database/sql/views/reporting.sql` da joylashgan; oʻzgartirilgandan keyin ular migratsiya orqali qayta qoʻllaniladi.

Eski `AV_*` koʻrinishlari (taxminan 90 ta) ishlatilmagan va koʻchirilmagan.
