# Artisan buyruqlari maʼlumotnomasi

- Kim uchun: IT operatorlari
- Qamrov: ushbu repozitoriyda roʻyxatdan oʻtkazilgan barcha ilova buyruqlari. Yangi buyruq qoʻshilganda u shu yerga ham qoʻshilishi shart, aks holda CI muvaffaqiyatsiz tugaydi (`tests/Feature/Tooling/CommandDocumentationTest.php`).

## Umumiy qoidalar

Har bir buyruq `php artisan {command}` sifatida ishga tushiriladi. Ishlab chiqish muhitida `./vendor/bin/sail artisan {command}`.

| Band | Qoida |
|---|---|
| Audit yozuvi | Maʼlumotlarni oʻzgartiradigan buyruqlar (quyida "Audit: qayd etiladi") har bir ishga tushirishda buyruq, argumentlar, parametrlar, OT foydalanuvchisi, chiqish kodi va natijani faoliyat jurnaliga (log `command`) yozadi. Ularni boshqaruv panelida **Audit > Faoliyat jurnali** boʻlimida tekshiring |
| Ishga tushirish ID si | Har bir ishga tushirish soʻrov ID si oʻrniga `cmd-` bilan boshlanadigan ID oladi. Faoliyat jurnali, oʻsha ishga tushirish kiritgan maʼlumot oʻzgarishlari va u joʻnatgan navbat vazifalari bir xil ID ga ega, shuning uchun "bu oʻzgarishni qaysi buyruq kiritgan" degan savolga ID orqali javob topiladi. `--actor` operator tomonidan berilgan qiymat, shuning uchun u maʼlumot oʻzgarishlarining foydalanuvchi ustuniga yozilmaydi (haqiqiy ijrochi — OT foydalanuvchisi) |
| `--actor=<email>` | Barcha audit qilinadigan buyruqlar uchun umumiy. Masʼul administrator hisobini qayd etadi. Notanish e-pochta ishga tushirishni toʻxtatadi |
| `--dry-run` | Hech narsani oʻzgartirmasdan natijani chiqaradi. Bekor qilish qiyin boʻlgan amallarda mavjud |
| `--force` | Tasdiqlash soʻrovlarisiz ishga tushiradi (rejalar va joriy etish skriptlari uchun) |
| `--reason=` | Sabab talab qiladigan buyruqlarda mavjud |
| Chiqish kodlari | 0 muvaffaqiyat, 1 muvaffaqiyatsizlik yoki bekor qilindi, 2 tekshiruv buyrugʻi muammo topdi |
| MB konteksti | Buyruq ishlayotganda MB sessiyasi ijrochisi `system:command:{command}` ga oʻrnatiladi va maʼlumot oʻzgarishlarida (trigger) koʻrinadi |

## Oʻrnatish va demo

### `setup:install`

- Vazifasi: yangi oʻrnatishlar va yangilashlar. Migratsiyalar, asosiy maʼlumotlar, muassasa standart qiymatlari, oʻrnatish profili va dastlabki administratorni navbat bilan bajaradi. Qayta ishga tushirish xavfsiz (mavjud maʼlumotlar va administratorlar oʻzgartirgan qiymatlar saqlanadi).
- Qachon: birinchi oʻrnatishda va har bir joriy etishda.
- Parametrlar: `--institution-name=`, `--locale=` (standart til), `--timezone=` (masalan, `Asia/Seoul`), `--profile=*` (oʻrnatish profili kaliti), `--grade-scale=` (baholash shkalasi `4.5` yoki `4.3`. Bir marta oʻrnatilgach oʻzgartirib boʻlmaydi. Boʻsh boʻlsa, birinchi profilning shkalasi), `--admin-email=`, `--admin-name=`, `--force` (savollarsiz ishga tushirish. Administrator paroli standart kirishdan oʻqiladi).
- Audit: qayd etiladi
- Misollar

  ```bash
  php artisan setup:install
  echo "$ADMIN_PASSWORD" | php artisan setup:install --force --locale=ko --timezone=Asia/Seoul \
      --institution-name="Demo University" --admin-email=admin@example.ac.kr --admin-name="Administrator"
  ```

- Natijani oʻqish: har bir qadam uchun `DONE`/`FAIL`. `Administrator exists` tizim administratori tayinlovi allaqachon mavjudligini, shuning uchun yaratish oʻtkazib yuborilganini bildiradi.
- Muvaffaqiyatsiz boʻlsa: agar xato kalit yetishmasligini aytsa, `key:generate` va `pii:generate-key` yaratgan qiymatlarni muhit sozlamalariga kiriting. Migratsiya muvaffaqiyatsizligi odatda MB rollari tayyorlanmaganini bildiradi (oʻrnatish qoʻllanmasiga qarang).

### `setup:demo`

- Vazifasi: fabrikalar yordamida xayoliy universitet maʼlumotlarini yaratadi. Har bir rol uchun qatʼiy belgilangan demo hisoblarni chiqaradi (`registrar@demo.test` va boshqalar).
- Qachon: ishlab chiqish, oʻqitish va namoyish muhitlari. **U production muhitida (`APP_ENV=production`) hatto `--force` bilan ham ishlamaydi.**
- Parametrlar: `--size=tiny|small|medium|large` (taxminan 20/100/2 000/20 000 talaba. `tiny` testlar va tezkor tekshiruvlar uchun), `--seed=` (bir xil qiymat bir xil maʼlumotlarni beradi), `--force`.
- `--student-accounts`: har bir faol talaba uchun hisob yaratadi (`student{student number}@demo.test`, demo parol) va ularni `storage/app/private/load-accounts.csv` ga yozadi. Yuklama testlari uchun ([Yuklama testi](load-testing.md)).
- Audit: qayd etiladi
- Misol: `php artisan setup:demo --size=small --seed=42`
- Muvaffaqiyatsiz boʻlsa: asosiy maʼlumotlar yetishmasa, demo hisoblarda rollar boʻlmaydi va ular oʻtkazib yuboriladi. Avval `setup:install` ni bajaring.

## Asosiy maʼlumotlar va semestrlar

### `reference:import`

- Vazifasi: asosiy maʼlumotlarni CSV dan import qiladi. Boshqaruv panelidagi **CSV import qilish** bilan bir xil import qoidalaridan foydalanadi (mavjud qatorlar kod boʻyicha topilib yangilanadi, boʻsh tarjima kataklari mavjud qiymatni saqlaydi, faqat muvaffaqiyatsiz qatorlar alohida qayd etiladi).
- Argumentlar: `{type}` — `org_units`, `programs`, `buildings`, `rooms`, `lookup_values`, `students`, `instructors`, `courses`, `registration_holds` dan biri / `{file}` — sarlavha qatoriga ega UTF-8 CSV.
- Parametrlar: `--actor=` (majburiy. Import yozuvi va tugallanganlik bildirishnomasi shu hisobga boradi), `--chunk=100` (bitta navbat vazifasidagi qatorlar soni), `--dry-run` (faqat sarlavhani tekshirish va qatorlarni sanash).
- Audit: qayd etiladi (tur, fayl nomi, qatorlar soni, muvaffaqiyatsiz qatorlar soni)
- Ustunlar formati: [Administrator qoʻllanmasining 9-bobi](../admin/09-csv-import.md).
- Chiqish kodlari: navbat sinxron ishlasa, natija darhol chiqariladi va muvaffaqiyatsiz qatorlar boʻlsa chiqish kodi 2 boʻladi. Asinxron (Horizon) boʻlsa, import faqat qabul qilinadi va chiqish kodi 0 boʻladi. Natija `--actor` hisobining boshqaruv paneli bildirishnomalarida muvaffaqiyatsiz qatorlar CSV fayli sifatida keladi.
- Misol: `php artisan reference:import org_units storage/app/org-units.csv --actor=admin@example.ac.kr --dry-run`

### `term:inspect`

- Vazifasi: berilgan vaqtda davom etayotgan semestrni va ochiq jarayon muddatlarini (turi, semestr, qamrov mezonlari, tugash vaqti) koʻrsatadi. Semestr va jarayon muddatlari sozlamalarini tekshirish yoki "nega fanlarga yozilish ochilmagan" kabi savollarga javob berish uchun foydalaning.
- Parametrlar: `--at=` (standart — hozir. Masalan, `"2027-02-15 09:00"`. Vaqt mintaqasi koʻrsatilmasa, server vaqt mintaqasida (UTC) talqin qilinadi. Mintaqani `"2027-02-15 09:00 Asia/Seoul"` kabi berish mumkin).
- Audit: qayd etilmaydi
- Natijani oʻqish: sanaga oid qarorlar muassasa vaqt mintaqasidan foydalanadi. Rejalashtirilgan yoki arxivlangan holatdagi semestrlarning jarayon muddatlari hech qachon ochilmaydi.

## Xavfsizlik

### `security:create-administrator`

- Vazifasi: tizim administratori roliga ega hisob yaratadi. Odatda `setup:install` tomonidan chaqiriladi.
- Parametrlar: `--email=`, `--name=`, `--password-stdin` (parol standart kirishdan. Interaktiv boʻlmagan ishga tushirishlar uchun majburiy).
- Audit: qayd etiladi (yaratilgan foydalanuvchi ID si)
- Misol: `php artisan security:create-administrator --email=admin@example.ac.kr --name="Administrator"`
- Muvaffaqiyatsiz boʻlsa: `system_administrator role is missing` avval `setup:install` yoki `db:seed` bajarilishi kerakligini bildiradi. Parol kamida 12 belgidan iborat boʻlishi kerak.

### `security:sync-permissions`

- Vazifasi: MB dagi ruxsatlar roʻyxatini koddagi ruxsatlar katalogi bilan moslashtiradi. Yangi ruxsatlar yaratiladi; katalogda yoʻq ruxsatlar oʻchiriladi (va barcha rollardan olib tashlanadi). Tizim rollari har doim barcha ruxsatlarni oladi.
- Qachon: joriy etishdan keyin (`setup:install` buni asosiy maʼlumotlar qadamining bir qismi sifatida bajaradi).
- Parametrlar: `--dry-run` (faqat yaratish/oʻchirish roʻyxatini chiqaradi), `--force` (oʻchirishni tasdiqlashni oʻtkazib yuboradi).
- Audit: qayd etiladi (yaratilgan/oʻchirilgan ruxsatlar)
- Natijani oʻqish: `create` va `delete` qatorlari. Oʻchirishlar boʻlsa, tasdiqlash soʻrovi chiqadi.
- Muvaffaqiyatsiz boʻlsa: tasdiqlashga "yoʻq" deb javob berish 1 kodi bilan chiqadi va hech narsani oʻzgartirmaydi.

### `security:reset-mfa`

- Vazifasi: foydalanuvchining ikki bosqichli autentifikatsiyasini (autentifikator ilovasi, e-pochta kodlari, tiklash kodlari, eslab qolingan qurilmalar) tiklaydi. Foydalanuvchi keyingi kirishda uni qaytadan ulaydi. Hech bir administrator boshqaruv paneliga kira olmaganda foydalaning (masalan, yagona administrator telefonini yoʻqotganda). Boshqa hollarda boshqaruv panelidagi **Foydalanuvchilar > Ikki bosqichli autentifikatsiyani tiklash** dan foydalaning.
- Argumentlar/parametrlar: `{user}` foydalanuvchi e-pochtasi, `--reason=` (majburiy), `--actor=` (majburiy. Tiklash haqida qaror qabul qilgan administrator).
- Audit: qayd etiladi (sabab bilan birga faoliyat jurnalida ham, xavfsizlik jurnalida ham)
- Misol: `php artisan security:reset-mfa admin@example.ac.kr --reason="Phone lost, identity verified by phone call" --actor=it-lead@example.ac.kr`

### `security:unlock-user`

- Vazifasi: koʻp marta notoʻgʻri parol kiritilgani sababli bloklangan hisobni blok muddati tugashidan oldin blokdan chiqaradi. Boshqaruv panelidagi **Foydalanuvchilar > Kirishni blokdan chiqarish** bilan bir xil.
- Argumentlar/parametrlar: `{user}` foydalanuvchi e-pochtasi, `--reason=` (majburiy).
- Audit: qayd etiladi (xavfsizlik jurnalida ham)
- Natijani oʻqish: `The account is not locked.` hech narsa oʻzgartirilmaganini bildiradi.
- Eslatma: blok chegaralari (muvaffaqiyatsiz urinishlar, blok davomiyligi) boshqaruv panelidagi **Sozlamalar > Kirish usullari** boʻlimida belgilanadi.

## Shaxslar va talaba yozuvlari

### `people:find-duplicates`

- Vazifasi: bir shaxs boʻlib koʻrinadigan shaxslarni roʻyxat qilib chiqaradi. Mezonlar: bir xil JShShIR (qidiruv xeshi) yoki bir xil lotinlashtirilgan ism va tugʻilgan sana. Birlashtirilgan shaxslar hisobga olinmaydi.
- Parametrlar: `--limit=100` (chiqariladigan guruhlar soni).
- Audit: qayd etilmaydi (faqat oʻqish)
- Chiqish kodlari: takror nomzodlar mavjud boʻlsa 2.
- Keyingi qadam: har bir guruhni boshqaruv panelidagi **Shaxslar** boʻlimida koʻrib chiqing va agar ular bir shaxs boʻlsa, saqlab qolinadigan shaxs sahifasidan **Takrorni birlashtirish** ni bajaring. Birlashtirishni bekor qilib boʻlmaydi ([Administrator qoʻllanmasining 14-bobi](../admin/14-merge-and-invitations.md)).

## Qabul

### `admission:matriculate`

- Vazifasi: qabul mavsumining oʻqishga kirishi tasdiqlangan abituriyentlari uchun talaba yozuvlarini (yangi talaba raqamlari) yaratadi. Boshqaruv panelidagi **Qabul > Arizalar** boʻlimidagi ommaviy **Talaba yozuvini yaratish** amali bilan bir xil. Har bir abituriyent alohida qayta ishlanadi, shuning uchun bitta muvaffaqiyatsizlik boshqalarni toʻxtatmaydi.
- Argumentlar/parametrlar: `{cycle}` qabul mavsumi kodi, `--term=` (majburiy. Qabul semestri kodi, masalan, `2027-SP`. Qabul sanasi semestrning birinchi kuni), `--actor=` (majburiy. `Qabul qilingan abituriyentlar uchun talaba yozuvlarini yaratish` ruxsatiga ega hisob. Faqat shu hisobning ruxsat qamrovidagi abituriyentlar qayta ishlanadi), `--dry-run` (faqat nishonlar roʻyxati).
- Audit: qayd etiladi (mavsum, semestr, nishonlar soni, yaratilganlar soni, muvaffaqiyatsiz ariza raqamlari). Har bir abituriyent uchun faoliyat yozuvi (`application.matriculated`) ham yoziladi.
- Chiqish kodlari: biror abituriyent muvaffaqiyatsiz boʻlsa 2.
- Misol: `php artisan admission:matriculate DEMO-2027-REG --term=2027-SP --actor=admissions@example.ac.kr --dry-run`
- Muvaffaqiyatsiz boʻlsa: `not ready` talaba yozuvi allaqachon mavjudligini yoki abituriyent oʻqishga kirishi tasdiqlangan holatda emasligini bildiradi; `program is not in effect` taʼlim dasturi qabul sanasida amalda emasligini bildiradi (Asosiy maʼlumotlar > Taʼlim dasturlari boʻlimida amal qilish davrini tekshiring).

### `admission:expire-drafts`

- Vazifasi: yopilgan mavsumlarning qoralama arizalarini va belgilangan muddat davomida tegilmagan qoralama arizalarni (**Sozlamalar > Qabul > Tegilmagan qoralamalar muddati (kun)**, standart 90 kun) **Muddati oʻtgan** deb belgilaydi. Muddati oʻtgan arizalar oʻsha mavsumda yangi ariza berishga toʻsqinlik qilmaydi.
- Qachon: rejalashtirilgan (har kuni 03:10 da, [Rejalashtirilgan vazifalar](scheduled-jobs.md)).
- Parametrlar: `--dry-run` (faqat sanash).
- Audit: qayd etiladi (muddati oʻtganlar soni)

## Fan guruhlari

### `section:copy`

- Vazifasi: yangi semestr rejalashtirishini oldingi semestrning fan guruhlaridan boshlaydi. Boshqaruv panelidagi **Fan guruhlari > Boshqa semestrdan nusxalash** bilan bir xil. Bekor qilinmagan fan guruhlarini qoralama sifatida oʻqituvchilar (hali ishlayotgan boʻlsa), dars vaqtlari va auditoriyalar, oʻrin taqsimotlari (boʻshatish vaqtlari semestrlar boshlanish sanalari orasidagi farqqa siljitiladi) va sillabus (yopiq qoralama sifatida) bilan birga nusxalaydi. Fan versiyasi yangi mashgʻulotlar davri boshida amalda boʻlgan versiya bilan almashtiriladi.
- Argumentlar/parametrlar: `{from-term}`, `{to-term}` semestr kodlari (masalan, `2026-FA`), `--org-unit=*` (faqat shu tashkiliy birlik kodidagi fan guruhlari), `--dry-run` (faqat natijalar, hech narsa saqlanmaydi), `--actor=` (majburiy. Faqat shu hisobning `Fan guruhlarini rejalashtirish` ruxsati qamrovidagi fan guruhlari nusxalanadi).
- Audit: qayd etiladi (nusxalangan, oʻtkazib yuborilgan va koʻrib chiqishni talab qiladiganlar soni)
- Natijani oʻqish: `skipped` maqsadli semestrda allaqachon xuddi shu fan guruhi kodi borligini yoki amaldagi fan versiyasi yoʻqligini bildiradi; `note` fan guruhi nusxalangani, lekin biror narsa tushib qolganini bildiradi (auditoriya allaqachon band boʻlgani uchun auditoriyasiz nusxalangan, ishdan ketgan oʻqituvchi va h.k.). Mashgʻulotlar davrlari kod boʻyicha moslashtiriladi; agar mos kelmasa, fan guruhi butun semestrlik mashgʻulotlar davriga joylanadi.
- Misol: `php artisan section:copy 2026-FA 2027-FA --actor=academic@example.ac.kr --dry-run`

### `schedule:check-conflicts`

- Vazifasi: dars jadvalini eʼlon qilishdan oldin semestrning fan guruhlarini tekshiradi. Auditoriyalar va oʻqituvchilarning ikki marta band qilinishining oldi MB tomonidan allaqachon olinadi; bu buyruq MB ruxsat beradigan muammolarni topadi: auditoriya sigʻimidan ortiq sigʻim, mashgʻulotlar davri boshlanish sanasida amalda boʻlmagan fan versiyasi, ishdan ketgan oʻqituvchi, sigʻimdan ortiq oʻrin taqsimotlari, mashgʻulotlar davridan tashqaridagi dars sanalari. Shuningdek, zaxiradan tiklash kabi cheklovlarsiz kirib kelgan maʼlumotlardagi auditoriya toʻqnashuvlarini ham tekshiradi.
- Argumentlar: `{term}` semestr kodi.
- Audit: qayd etilmaydi (faqat oʻqish)
- Chiqish kodlari: muammolar mavjud boʻlsa 2. Hali oʻqituvchi yoki auditoriyasi yoʻq fan guruhlari faqat maʼlumot uchun koʻrsatiladi.

## Fanlarga yozilish

### `registration:preflight`

- Vazifasi: fanlarga yozilishni ochishdan oldin semestrni tekshiradi. Fanlarga yozilish va qoʻshish/chiqish muddatlarini, qoidalar toʻplamini, eʼlon qilingan fan guruhlari sonini, sigʻimi 0 boʻlgan fan guruhlarini, sigʻimdan ortiq oʻrin taqsimotlarini, mashgʻulotlar davri boshlanish sanasida amalda boʻlmagan fan versiyalarini, kredit chegarasi bilan qamrab olinmagan faol talabalar sonini va fanlarga yozilish cheklovi qoʻyilgan talabalar sonini koʻrsatadi.
- Argumentlar: `{term}` semestr kodi (masalan, `2027-SP`).
- Audit: qayd etilmaydi (faqat oʻqish)
- Chiqish kodlari: muammolar mavjud boʻlsa 2. Qoidalar toʻplamining yoʻqligi muammo sifatida emas, maʼlumot sifatida koʻrsatiladi (qoidalarning standart qiymatlari qoʻllanadi).
- Misol: `php artisan registration:preflight 2027-SP`

### `registration:simulate`

- Vazifasi: talaba hozir (yoki berilgan vaqtda) fan guruhiga yozilsa nima boʻlishini qoidama-qoida koʻrsatadi. Hech narsa saqlanmaydi va urinish qayd etilmaydi. "Nega yozila olmayapman?" kabi savollar uchun foydalaning.
- Argumentlar: `{student-number}` talaba raqami, `{section}` `fan kodi-guruh kodi` (masalan, `CSE2010-01`).
- Parametrlar: `--term=` (semestr kodi. Standart — shu fan guruhi mavjud boʻlgan eng soʻnggi semestr), `--at=` (baholash vaqti. Masalan, `"2027-02-10 10:00 Asia/Seoul"`).
- Audit: qayd etilmaydi (faqat oʻqish)
- Natijani oʻqish: har bir qoida uchun oʻtdi/oʻtmadi, bloklash/ogohlantirish va xabar. Qoidadan istisno bilan qamrab olingan qoida oʻtmagan deb koʻrsatiladi, lekin bloklamaydi. Sigʻim qoidalar jadvali ostida alohida koʻrsatiladi.
- Chiqish kodlari: fanga yozilish rad etiladigan boʻlsa 2.

### `waitlist:promote`

- Vazifasi: boʻsh oʻrinlari bor fan guruhlariga kutish roʻyxatidagi talabalarni navbat tartibida yozadi. Oʻtkazishda barcha qoidalar qayta tekshiriladi; biror qoidadan oʻtmagan kutish roʻyxati yozuvlari kutish roʻyxatidan olib tashlanadi (sababi qayd etiladi). Fanlarga yozilish va qoʻshish/chiqish muddatlari ikkalasi ham yopilgan fan guruhlarining kutish roʻyxatlari muddati tugaydi. Talabalar natijani e-pochta orqali oladi.
- Argumentlar/parametrlar: `{term?}` semestr kodi (standart: kutish roʻyxati yozuvlari bor barcha semestrlar), `--actor=`.
- Audit: qayd etiladi (fan guruhlari, oʻtkazilganlar, olib tashlanganlar, muddati tugaganlar soni)
- Qachon: fandan chiqish, voz kechish yoki oʻrin taqsimotini boʻshatishda tegishli fan guruhi navbat vazifasi orqali darhol qayta ishlanadi. Bu buyruq esa tushib qolgan narsalarni qayta ishlaydigan, har soatda rejalashtirilgan sugʻurta vositasidir.
- Misol: `php artisan waitlist:promote 2027-SP --actor=registrar@example.ac.kr`

### `registration:release-reserved-seats`

- Vazifasi: boʻshatish vaqti oʻtgan oʻrin taqsimotlarini (masalan, mutaxassislik talabalari uchun band qilingan oʻrinlar) qayta ishlangan deb belgilaydi va oʻsha fan guruhlari uchun kutish roʻyxatidan oʻtkazish vazifasini navbatga qoʻyadi. Band qilingan oʻrinlar boʻshatish vaqti oʻtishi bilanoq hisob-kitobda umumiy oʻrinlarga aylanadi, shuning uchun bu buyruq oʻrinlarni faqat kutish roʻyxatiga topshiradi.
- Audit: qayd etilmaydi (har 5 daqiqada ishlaydi. Oʻtkazish natijalari fanlarga yozilish yozuvlarida)
- Qachon: rejalashtirilgan (har 5 daqiqada).

## Baholar

### `term:close`

- Vazifasi: semestrni yopadi. Semestrdagi barcha baholar yakunlangan boʻlishi kerak. Har bir talaba uchun semestr natijasini (kreditlar, semestr va umumiy GPA) hisoblaydi, akademik holatni (yaxshi holat, akademik ogohlantirish, oʻqishdan chetlashtirishni koʻrib chiqish) va imtiyozlarga muvofiqlikni aniqlaydi (navbat paketi), soʻngra imtiyozlarni har bir taʼlim dasturi boʻyicha reytinglaydi, semestr natijalarini rasmiy yozuv sifatida yopadi va semestrni **Baholar yopilgan** holatiga oʻtkazadi. Boshqaruv panelidagi **Baholar > Semestr natijalari > Semestrni yopish** bilan bir xil.
- Argumentlar/parametrlar: `{term}` semestr kodi, `--dry-run` (faqat semestrni yopish mumkinligini tekshiradi), `--reason=` (yozuvda saqlanadigan sabab), `--actor=`.
- Audit: qayd etiladi (talabalar soni, baholari yakunlanmagan fan guruhlari soni). Yopish tugagach, `term.closed` audit yozuvida akademik ogohlantirish, oʻqishdan chetlashtirishni koʻrib chiqish va imtiyozlar soni saqlanadi.
- Chiqish kodlari: baʼzi fan guruhlarida yakunlanmagan baholar boʻlsa 2 (har bir fan guruhi boʻyicha yakunlanmaganlar soni chiqariladi). Semestr eʼlon qilingan holatda boʻlmasa 1.
- Qachon: baho kiritish va yakunlash tugallangandan keyin. Paket jarayonini Horizon da kuzating. Navbat ishlovchisisiz semestr holati oʻzgarmaydi.
- Misollar: `php artisan term:close 2026-FA --dry-run`, `php artisan term:close 2026-FA --reason="Fall 2026 grades closed" --actor=registrar@example.ac.kr`

### `grades:recalculate`

- Vazifasi: yakunlangan baholardan semestr natijalarini (kreditlar, semestr va umumiy GPA) qayta hisoblaydi. Oʻzgartirilgan baholash siyosatini (GPA kasr xonalari, qayta oʻqishni hisobga olish) oʻtgan semestrlarga qoʻllash uchun foydalaning. Akademik holat va imtiyozlar semestr yopilganda belgilangan qiymatlarni saqlaydi. Tasdiqlangan bahoni oʻzgartirish oʻsha talabani avtomatik ravishda qayta hisoblaydi, shuning uchun buning uchun bu buyruq kerak emas.
- Parametrlar: `--term=` (oʻsha semestrda baholari bor talabalar, oʻsha semestrdan boshlab), `--student-number=` (bitta talaba), `--reason=` (majburiy), `--actor=`.
- Audit: qayd etiladi (talabalar soni, sabab)
- Misol: `php artisan grades:recalculate --term=2026-SP --reason="GPA changed to three decimal places"`

### `grades:remind-missing`

- Vazifasi: baholari hali topshirilmagan (kiritilmagan yoki qoralama sifatida saqlangan) fan guruhlarining oʻqituvchilariga e-pochta yuboradi. Fan guruhlari har bir oʻqituvchi boʻyicha bitta xatga guruhlanadi.
- Argumentlar/parametrlar: `{term}` semestr kodi, `--dry-run` (yubormasdan nishonlarni chiqaradi), `--actor=`.
- Audit: qayd etiladi (fan guruhlari, oʻqituvchilar, yuborilgan xatlar)
- Qachon: baho kiritish muddati yopilishidan bir necha kun oldin. Kerak boʻlsa, uni rejaga qoʻshing.

## Fan soʻrovnomasi

### `evaluation:remind`

- Vazifasi: fan soʻrovnomasi muddati ochiq paytda hali baholanmagan fanlari bor talabalarga e-pochta yuboradi. Qolgan fanlar har bir talaba boʻyicha bitta xatga guruhlanadi.
- Argumentlar/parametrlar: `{term}` semestr kodi, `--dry-run` (faqat nishon talabalar soni, hech narsa yuborilmaydi), `--actor=`.
- Audit: qayd etiladi (nishon talabalar, yuborilgan xatlar)
- Qachon: soʻrovnoma muddati davomida bir yoki ikki marta. Kerak boʻlsa, uni rejaga qoʻshing.

### `evaluation:publish-results`

- Vazifasi: semestrning fan soʻrovnomasi natijalarini oʻqituvchilarga eʼlon qiladi. Boshqaruv panelidagi **Fan soʻrovnomasi > Semestr soʻrovnomalari > Natijalarni eʼlon qilish** bilan bir xil. Eʼlon qilingandan keyin ham natijalar faqat javoblarning minimal soni va javob berish ulushiga yetgan fan guruhlari uchun koʻrinadi.
- Argumentlar/parametrlar: `{term}` semestr kodi, `--actor=`.
- Audit: qayd etiladi (javoblar soni, allaqachon eʼlon qilinganmi)
- Qachon: soʻrovnoma muddati va baholarni qayta ishlash tugallangandan keyin. Allaqachon eʼlon qilingan semestr oʻzgartirilmaydi.

## Maʼlumotnomalar

### `certificate:verify`

- Vazifasi: maʼlumotnoma tekshirish kodini ochiq tekshirish sahifasidagi mezonlar bilan tekshiradi. Turi, berilish raqami, berilgan sanasi va niqoblangan egasini chiqaradi.
- Argumentlar/parametrlar: `{code}` tekshirish kodi.
- Audit: yoʻq (faqat oʻqish)
- Chiqish kodlari: haqiqiy boʻlsa 0, notanish kod, bekor qilingan maʼlumotnoma yoki amal qilish muddati tugagan boʻlsa 2.

### `certificate:render`

- Vazifasi: berilgan maʼlumotnomaning PDF faylini joriy maʼlumotlar va shablon bilan qayta yaratib, arxivlangan faylni almashtiradi. Boshqaruv panelidagi **Berilgan maʼlumotnomalar > Qayta yaratish** bilan bir xil. Berilish raqami va tekshirish kodi oʻzgarmaydi; nazorat summasi oʻzgaradi.
- Argumentlar/parametrlar: `{issue}` berilish raqami, `--reason=` (majburiy), `--actor=`.
- Audit: qayd etiladi (berilish raqami, yangi nazorat summasi)
- Qachon: shablon yoki ism yozilishini tuzatgandan keyin muayyan maʼlumotnomani qayta yaratish uchun.

## Imtihonlar

### `assessment:import-results`

- Vazifasi: imtihon sessiyasi natijalarini CSV dan kiritadi. Boshqaruv panelidagi **Imtihon sessiyalari > Natijalarni import qilish (CSV)** bilan bir xil qoidalar (faqat roʻyxatdan oʻtgan talabalar, oʻtdi/oʻtmadi ball boʻyicha aniqlanadi, eʼlon qilinishidan oldin qayta kiritish natijalarni almashtiradi).
- Argumentlar/parametrlar: `{type}` imtihon turi kodi / `{held_date}` imtihon sanasi `YYYY-MM-DD` / `{file}` sarlavha qatoriga ega UTF-8 CSV, `--actor=` (majburiy), `--dry-run` (faqat qatorlarni tekshirish, hech narsa qayd etilmaydi).
- Ustunlar formati: [Administrator qoʻllanmasining 24-bobi](../admin/24-assessments.md).
- Audit: qayd etiladi (sessiya, fayl nomi, qatorlar soni, kiritilgan/qatnashmagan/xato soni)
- Chiqish kodlari: xatoli qatorlar boʻlsa 2 (qolgan qatorlar qayd etiladi).
- Misol: `php artisan assessment:import-results ENG_COMP 2026-05-15 storage/app/eng.csv --actor=registrar@example.ac.kr --dry-run`

### `assessment:publish-results`

- Vazifasi: sessiya natijalarini talabalarga eʼlon qiladi. Boshqaruv panelidagi **Imtihon sessiyalari > Natijalarni eʼlon qilish** bilan bir xil.
- Argumentlar/parametrlar: `{type}` imtihon turi kodi / `{held_date}` imtihon sanasi, `--actor=` (majburiy).
- Audit: qayd etiladi (sessiya, natijalar soni)
- Qachon: natijalarni kiritish tugallangandan keyin. Allaqachon eʼlon qilingan sessiya uchun muvaffaqiyatsiz tugaydi.

## Davomat

### `attendance:generate-sessions`

- Vazifasi: semestrning har bir fan guruhi uchun dars vaqtlaridan (hafta kuni, vaqt, davr) darslarni yaratadi. Oʻqituvchi portali va boshqaruv panelidagi **Darslarni yaratish** bilan bir xil. Mavjud darslar va ularning davomati saqlanadi (qayta ishga tushirish xavfsiz).
- Argumentlar/parametrlar: `{term}` semestr kodi, `--section=` (fan kodi-guruh kodi, masalan, `CSE2010-01`, faqat bittasi), `--dry-run` (faqat nishon fan guruhlari soni), `--actor=`.
- Audit: qayd etiladi (semestr, fan guruhlari, yaratilgan darslar)
- Qachon: semestr boshlanishidan oldin, dars vaqtlari tasdiqlangandan keyin. Dars vaqtlari oʻzgarganda qayta ishga tushiring.

## Bitiruv

### `degree-audit:run`

- Vazifasi: har bir faol talaba uchun bitiruv talablarini tekshirish natijasini saqlaydi. Boshqaruv panelidagi **Bitiruv talablari tekshiruvlari > Tekshiruvlarni bajarish** bilan bir xil. Eng soʻnggi saqlangan tekshiruv bitiruvni rasmiylashtirish uchun asos boʻladi.
- Parametrlar: `--term=` (tekshiruv tegishli boʻlgan semestr kodi), `--student-number=` (bitta talaba), `--min-year-level=` (faqat shu kurs va undan yuqori), `--actor=`.
- Audit: qayd etiladi (talabalar soni, talablarga javob berganlar soni)
- Qachon: bitiruvni koʻrib chiqishdan oldin (semestr oxiri) va talablar toʻplamlari yoki muqobil fanlar oʻzgartirilgandan keyin.

## LMS integratsiyasi

### `lms:sync`

- Vazifasi: eʼlon qilingan fan guruhlarini platformaga uzatadi (kurs yaratish va bogʻlash, oʻqituvchilar, yozilgan talabalar). Boshqaruv panelidagi **Kurs bogʻlanishlari > Qayta sinxronlash** bajaradigan ishni butun semestr uchun bajaradi. Allaqachon bogʻlangan fan guruhlari faqat qayta taʼminlanadi (idempotent).
- Argumentlar/parametrlar: `{platform}` platforma kodi, `--term=` (semestr kodi, standart — tugamagan barcha semestrlar), `--section=` (bitta fan kodi-guruh kodi), `--dry-run` (yuboriladigan narsalarni faqat "skipped" sifatida qayd etadi), `--actor=`.
- Audit: qayd etiladi (platforma, fan guruhlari, aʼzolar, muvaffaqiyatsizliklar)
- Chiqish kodlari: biror fan guruhi muvaffaqiyatsiz boʻlsa 2.

### `lms:reconcile`

- Vazifasi: platformadagi kurs roʻyxatlarini talabalar axborot tizimidagi fanga yozilganlar roʻyxatlari bilan solishtiradi, yetishmayotgan aʼzolarni qoʻshadi va ortiqchalarini olib tashlaydi. Farqlar sinxronlash jurnalida (`reconcile`) saqlanadi.
- Argumentlar/parametrlar: `{platform?}` (koʻrsatilmasa, barcha faol platformalar), `--term=`, `--dry-run` (faqat farqlarni qayd etadi), `--actor=`.
- Audit: qayd etiladi (har bir platforma boʻyicha fan guruhlari, qoʻshilgan/olib tashlangan/muvaffaqiyatsiz soni)
- Qachon: har kuni 02:30 da rejalashtirilgan. Platformadagi uzilishdan keyin qoʻlda ishga tushiring.

## Ekspluatatsiya tekshiruvlari va tozalash

### `system:health-check`

- Vazifasi: sozlamalar va kalitlarni (APP_KEY, PII kalitlari, APP_DEBUG), MB ulanishi va rollarini (runtime, migrator, reporting), shaxsiy maʼlumotlar jadvallaridagi RLS ni, bajarilmagan migratsiyalarni, ruxsatlar katalogi sinxronligini, Redis, navbat (Horizon), Gotenberg, media omboriga yozish va rejalashtiruvchi yurak urishini tekshiradi.
- Parametrlar: `--json` (monitoring uchun natija).
- Audit: yoʻq (faqat oʻqish)
- Chiqish kodlari: biror tekshiruv muvaffaqiyatsiz boʻlsa 2.
- Qachon: joriy etishdan darhol keyin, uzilish gumon qilinganda, monitoring tomonidan davriy ravishda.

### `audit:seal`

- Vazifasi: audit jadvallarining yangi qatorlarini xesh zanjiriga bogʻlaydi ([Audit yaxlitligi](audit-and-maintenance.md)).
- Parametrlar: `--table=` (record_changes, activity_log, security_events dan biri; takrorlanishi mumkin), `--actor=`.
- Audit: qayd etiladi (har bir jadval boʻyicha muhrlangan qatorlar)
- Qachon: har kuni 03:30 da rejalashtirilgan.

### `audit:verify-chain`

- Vazifasi: soxtalashtirish, oʻchirish va tartib oʻzgarishini aniqlash uchun audit qatorlarining mazmun xeshlari va zanjirini qayta hisoblaydi.
- Parametrlar: `--table=`.
- Audit: yoʻq (faqat oʻqish)
- Chiqish kodlari: nomuvofiqlik boʻlsa 2.
- Qachon: zaxiralashdan oldin, xavfsizlik tekshiruvlari paytida, tozalashdan keyin.

### `audit:prune`

- Vazifasi: saqlash muddati oʻtgan **muhrlangan** audit qatorlarini oʻchiradi. Sxema egasi ulanishida ishlaydi.
- Parametrlar: `--before=YYYY-MM-DD` (majburiy), `--table=`, `--dry-run` (faqat sanash), `--actor=`.
- Audit: qayd etiladi (chegara sanasi, har bir jadval boʻyicha oʻchirilgan qatorlar)
- Qachon: saqlash siyosatiga koʻra, zaxiralashdan keyin.

### `media:prune-orphans`

- Vazifasi: egasi endi mavjud boʻlmagan media qatorlarini va qatori yoʻq kataloglarni oʻchiradi (shaxslar fotosuratlari, ariza hujjatlari, maʼlumotnoma PDF fayllari, imtihon dalillari).
- Parametrlar: `--dry-run` (faqat roʻyxat), `--actor=`.
- Audit: qayd etiladi (qatorlar, kataloglar)
- Qachon: har oy yoki ommaviy oʻchirishdan keyin.

## Shaxsiy maʼlumotlarni shifrlash kalitlari

### `pii:generate-key`

- Vazifasi: `PII_ENCRYPTION_KEY` va `PII_HASH_KEY` uchun yangi kalitlarni chiqaradi. Hech narsa saqlamaydi.
- Audit: qayd etilmaydi
- Diqqat: `PII_HASH_KEY` ni oʻzgartirish barcha qidiruv xeshlarini yaroqsiz qiladi. Kalitni almashtirish tartibi [security-keys.md](security-keys.md) da.

### `pii:reencrypt`

- Vazifasi: kalit almashtirilgandan keyin oldingi kalit bilan shifrlangan shaxsiy maʼlumotlarni joriy kalit bilan qayta shifrlaydi. Navbat paketi sifatida ishlaydi.
- Argumentlar: model klassi (hozircha `App\\Models\\Person\\Person`).
- Parametrlar: `--chunk=500`, `--dry-run` (faqat oldingi kalitdan foydalanayotgan yozuvlarni sanaydi), `--force`.
- Audit: qayd etiladi (paket ID si, vazifalar soni / oldindan koʻrish soni)
- Tekshirish: paket jarayonini Horizon da (`/horizon`) kuzating. U tugagach va `--dry-run` 0 ni koʻrsatgach, oldingi kalitni `PII_PREVIOUS_KEYS` dan olib tashlang.

## Mahalliylashtirish

### `i18n:missing`

- Vazifasi: har bir til boʻyicha asosiy til (en) bilan solishtirganda yetishmayotgan kalitlarni va endi ishlatilmaydigan kalitlarni koʻrsatadi.
- Argumentlar/parametrlar: `{locale?}` (faqat bitta til), `--strict` (kalitlar yetishmasa chiqish kodi 2. CI da ishlatiladi).
- Audit: qayd etilmaydi

### `i18n:export`

- Vazifasi: tarjimonlarga topshirish uchun JSON fayl yaratadi. Format: `{"key": {"source": "English text", "translation": "translation"}}`.
- Argumentlar/parametrlar: `{locale} {path}`, `--missing` (faqat yetishmayotgan kalitlar).
- Audit: qayd etilmaydi
- Misol: `php artisan i18n:export ko storage/app/ko.json --missing`

### `i18n:import`

- Vazifasi: tarjimonlar toʻldirgan JSON ni `lang/{locale}` ga qoʻllaydi. Faqat asosiy tilda mavjud boʻlgan kalitlar import qilinadi; boʻsh tarjimalar eʼtiborsiz qoldiriladi.
- Argumentlar/parametrlar: `{locale} {path}`, `--dry-run` (faqat yoziladigan kalitlar roʻyxatini chiqaradi).
- Audit: qayd etiladi
- Diqqat: tarjima fayllari repozitoriyda boshqariladi. Import qilgandan keyin oʻzgarishlarni commit qiling.

## Tekshiruvlar

### `naming:check`

- Vazifasi: migratsiyalardagi jadval va ustun nomlarini [nomlash qoidalari](../../../development/naming.md) ga muvofiqligini tekshiradi.
- Parametrlar: `--path=*` (tekshiriladigan kataloglar, standart `database/migrations`).
- Audit: qayd etilmaydi
- Chiqish kodlari: muammolar mavjud boʻlsa 2.

### `pdf:sample`

- Vazifasi: shriftlarni va PDF xizmatiga (Gotenberg) ulanishni tekshirish uchun hangul, CJK, lao, tay, devanagari, kirill va lotin yozuvlari aralash jadvalni PDF ga chiqaradi.
- Argumentlar: `{path=pdf-sample.pdf}` (`storage/app/private` mahalliy diskiga nisbatan).
- Audit: qayd etilmaydi
- Muvaffaqiyatsiz boʻlsa: ulanish xatosida `GOTENBERG_URL` va Gotenberg konteyneri holatini tekshiring.
