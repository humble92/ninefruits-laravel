# 9. CSV оруулах

Бүтцийн нэгж, хөтөлбөр, байр, танхим болон кодын жагсаалтын дэлгэцийн дээд хэсэгт байх **CSV оруулах** товчийг ашиглана (`Лавлах мэдээллийг CSV-ээс оруулах` эрх). Оюутны бүртгэл болон багшийн жагсаалтад ижил товч бий (тус тус `Оюутны бүртгэл үүсгэх, засах` болон `Багш нарыг удирдах` эрх). Маш том файлыг IT оператор `reference:import` командаар мөн адил оруулж болно.

## Нийтлэг дүрэм

- Эхний мөрөнд баганын нэрс байна (доорх хүснэгтүүд). Кодчилол нь UTF-8; Excel-д "CSV UTF-8" хэлбэрээр хадгална.
- Код нь аль хэдийн байгаа мөр тухайн бичлэгийг **шинэчилнэ**. Ижил файлыг дахин оруулахад давхардал үүсэхгүй.
- Хэл тус бүрийн багана `багана_хэл` хэлбэртэй: `name_en`, `name_ko`, `name_zh_CN`. Шинэ мөрөнд англи нэр (`name_en`) заавал шаардлагатай. Хоосон нүд одоо байгаа орчуулгыг арилгахгүй.
- Огноог `2026-03-01` хэлбэрээр; тийм/үгүйг `1`/`0` эсвэл `true`/`false` гэж бичнэ.
- Оруулалт ар талд ажиллана. Дуусахад үр дүн удирдлагын самбарын баруун дээд буланд мэдэгдлээр ирнэ; алдаатай мөр байвал **Download information about the failed rows** (алдаатай мөрийн мэдээллийг татах) товчоор мөр бүрийн шалтгаантай CSV авна. Түүнийг засаад зөвхөн тэр файлыг дахин оруулна.
- Дэлгэц дээрх баганын нэр өөр бол оруулах цонхонд баганыг гараар тааруулж болно.

## Баганын формат

| Төрөл | Заавал багана | Сонголтот багана |
|---|---|---|
| Бүтцийн нэгж | `code`, `type` (бүтцийн нэгжийн төрлийн код), `name_en`, `effective_start_date` | `parent` (дээд нэгжийн код), `name_ko` гэх мэт, `short_name_*`, `effective_end_date`, `sort_order` |
| Хөтөлбөр | `code`, `org_unit` (бүтцийн нэгжийн код), `degree_level`, `name_en`, `effective_start_date` | `degree_title_*`, `standard_duration_years`, `effective_end_date`, `allows_second_major`, `allows_minor` |
| Байр | `code`, `name_en` | `name_*`, `address`, `is_active` |
| Танхим | `building` (байрны код), `code` (танхимын дугаар) | `name_*`, `room_type` (танхимын төрлийн код), `org_unit`, `capacity`, `is_schedulable`, `is_active` |
| Кодын утга | `type` (жагсаалтын код), `code`, `name_en` | `name_*`, `sort_order`, `is_active` |

`degree_level`-ийн утгууд: `associate`, `bachelor`, `master`, `doctorate`, `certificate`, `non_degree`

Бүтцийн нэгжийн хувьд дээд нэгж эхэлж байх ёстой (их сургууль → бүрэлдэхүүн сургууль → тэнхим дарааллаар эрэмбэлнэ).

## Жишээ: бүтцийн нэгж

```csv
code,type,parent,name_en,name_ko,effective_start_date
UNI,university,,Sample University,샘플대학교,1990-03-01
ENG,college,UNI,College of Engineering,공과대학,1990-03-01
CSE,department,ENG,Computer Science,컴퓨터공학과,1995-03-01
```

Оюутан ба багш (2-р үе шат) болон хичээлийн (4-р үе шат) оруулалтыг тухайн функцүүдтэй хамт нэмнэ.

## Оюутан ба багш

Оюутны бүртгэлийг `student_number`-аар, багшийг `employee_number`-аар тааруулж шинэчилнэ. Хоёр төрөл хувь хүний баганыг хуваалцана.

| Төрөл | Шинэ мөрөнд заавал багана | Сонголтот багана |
|---|---|---|
| Оюутны бүртгэл | `student_number`, `program` (хөтөлбөрийн код), `admission_date`, `family_name` | `catalog_year` (хоосон бол элссэн он), `year_level`, `status`, `graduation_date`, `institutional_email` |
| Багш | `employee_number`, `org_unit` (бүтцийн нэгжийн код), `employment_type`, `family_name` | `academic_title_*`, `hire_date`, `end_date` |
| Хувь хүний багана (нийтлэг) | — | `given_name`, `name_ko` зэрэг хэл тус бүрийн нэр, `birth_date`, `gender`, `email`, `phone`, `national_id` |

- `status`-ийн утгууд: `enrolled`, `on_leave`, `withdrawn`, `dismissed`, `graduated` (graduated бол `graduation_date` заавал). Оруулалтаар өөрчилсөн төлөвийг **өгөгдлийн засвар** гэж үзэх бөгөөд шилжилт хөдөлгөөний түүх үүсгэхгүй. Бодит өөрчлөлтийг дэлгэц дээр бүртгэнэ ([13-р бүлэг](13-status-changes-and-advisors.md)).
- `employment_type`-ийн утгууд: `full_time`, `part_time`, `adjunct`, `visiting`, `emeritus`. `gender`-ийн утгууд: `female`, `male`, `other`, `undisclosed`.
- Шинэ оюутны бүртгэлийн `national_id` одоо байгаа хувь хүнийхтэй таарвал бүртгэлийг тэр хувь хүнд холбоно (дахин элсэх). Үгүй бол шинэ хувь хүн үүсгэнэ. Шинэчлэх үед хоосон нүд одоо байгаа утгыг арилгахгүй.
- Оруулсан оюутан болон багшид бүртгэл байхгүй. [Бүртгэлийн урилга](14-merge-and-invitations.md#бүртгэлийн-урилга)-аар урилга илгээнэ.

```csv
student_number,program,admission_date,year_level,status,family_name,given_name,name_ko,birth_date
202600001,B-CSE,2026-03-02,1,enrolled,Kim,Minsu,김민수,2007-05-01
```

## Хичээл

Хичээлийн жагсаалтын дээд хэсэгт байх **CSV оруулах** (`Хичээл, хувилбар, холбоо хичээлийг удирдах` эрх) эсвэл `reference:import courses`. Нэг мөр нь нэг хичээлийн хувилбар.

| Заавал багана | Сонголтот багана |
|---|---|
| `code`, `effective_start_date`, `title_en`, `credits` (шинэ хувилбар) | `org_unit` (шинэ хичээлд заавал; хариуцах бүтцийн нэгжийн код), `effective_end_date`, `title_*`, `lecture_hours`, `lab_hours`, `category` (хичээлийн ангиллын код), `grading_basis` (`graded`, `pass_fail`), `counts_toward_credit_limit` |

- Хичээлийг `code`-оор, хувилбарыг `code` + `effective_start_date`-ээр тааруулна. Ижил файлыг дахин оруулбал шинэчилнэ.
- Засварын түүхийг нэг файлаас ачаалж болно: ижил кодын мөрүүдийг хугацаа тус бүрээр жагсаана (өмнөх хувилбарт `effective_end_date` тавина).
- Хугацаа нь өөр хувилбартай давхцсан мөр алдаатай болно. Шинэ хичээлийн мөр алдаатай бол хичээл ч үүсэхгүй.
- Холбоо хичээл, дүйцэх хичээл болон төгсөлтийн шаардлагыг дэлгэц дээр оруулна ([17-р бүлэг](17-courses-and-curriculum.md)).

```csv
code,org_unit,effective_start_date,effective_end_date,title_en,title_ko,credits,category
CSE2010,CSE,2015-03-01,2026-02-28,Data Structures,자료구조,3,MAJ_REQ
CSE2010,,2026-03-01,,Data Structures,자료구조,4,MAJ_REQ
```

## Хичээл сонголтын хориг

**Хичээл сонголт > Хоригууд** жагсаалтын дээд хэсэгт байх **CSV оруулах** (`Хориг тавих, цуцлах, дүрмээс онцгой зөвшөөрөл олгох` эрх) эсвэл `reference:import registration_holds`. Хуучин `SRDENY` өгөгдөл эсвэл санхүүгийн албаны төлбөр төлөөгүй оюутны жагсаалтыг ачаалахад ашиглана.

| Заавал багана | Сонголтот багана |
|---|---|
| `student_number`, `hold_type` (`unregistered`, `financial`, `academic`, `administrative`, `other`), `reason` | `starts_at` (анхдагч: оруулсан цаг), `ends_at`, `blocks_registration` (анхдагч 1), `blocks_grades`, `blocks_certificates` |

- Мөр бүр шинэ хориг үүсгэнэ (эх сурвалж `import`). Ижил файлыг хоёр удаа оруулбал хориг хоёр удаа үүснэ, тиймээс дахин оруулахын өмнө жагсаалтыг шалгана уу.
- Үл мэдэгдэх оюутны кодтой мөр алдаатай болно. Хоригийг дэлгэц дээр шалтгаантайгаар цуцална ([20-р бүлэг](20-registration.md)).

```csv
student_number,hold_type,reason,ends_at
202312345,financial,2027-1 등록금 미납,
202254321,academic,학사경고 상담 필요,2027-03-15
```
