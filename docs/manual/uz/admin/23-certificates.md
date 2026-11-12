# 23. Maʼlumotnomalar

Menyu: **Maʼlumotnomalar > Berilgan maʼlumotnomalar**, **Maʼlumotnoma turlari**, **Sozlamalar > Maʼlumotnoma sozlamalari**. Maʼlumotnomalar talaba yozuvi ekranidagi **Maʼlumotnoma berish** tugmasi orqali beriladi (**Talabalar va xodimlar > Talaba yozuvlari > Koʻrish**). Talabalar oʻz maʼlumotnomalarining PDF fayllarini portaldagi **Maʼlumotnomalar** boʻlimidan yuklab oladi. Har kim maʼlumotnomani `/verify/{code}` sahifasida tekshirishi mumkin.

| Ruxsat | Nimaga imkon beradi |
|---|---|
| `Maʼlumotnomalarni berish, qayta yaratish va bekor qilish` | Berish, qayta yaratish, bekor qilish, berilgan maʼlumotnomalarni koʻrish (talaba yozuvlarini koʻrish qamrovi doirasida) |
| `Maʼlumotnoma turlari, shablonlari va sozlamalarini boshqarish` | Turlar va ularning har bir til uchun shablonlari, seriya raqami shabloni, imzolovchilar |

Rollar boʻyicha standart sozlamalar: Maʼlumotnomalar masʼuli maʼlumotnoma beradi, Registratorda ikkalasi ham bor, Oʻquv-uslubiy boshqarma turlar va shablonlarni boshqaradi.

## Eski tizimdan farqlari

- **Berilgan maʼlumotnomalar reyestri.** Eski tizim (YUSTIC) toʻgʻridan-toʻgʻri ekrandan chop etardi (`aca3/cti_*`), uning reyestri (`SRCERT`) esa faqat raqam yaratish uchun mavjud edi, shuning uchun maʼlumotnomani kim bergani yoki u bekor qilinganligi koʻrinmasdi. Yangi tizim har bir berish uchun seriya raqami, tekshirish kodi, xodim, maqsad, nusxalar soni, PDF fayl va nazorat summasini qayd etadi, bekor qilingan maʼlumotnoma esa oʻz qatorini saqlab qoladi.
- **Tekshirish.** Eski tizimda bu yoʻq edi. Har bir maʼlumotnomada tekshirish kodi va QR kod bor, ommaviy sahifa esa maʼlumotnoma berilganligi, uning turi, berilgan sanasi va niqoblangan egasini (familiya va ismning birinchi va oxirgi harfi, talaba raqamining birinchi 4 ta raqami) koʻrsatadi.
- **Shablonlar.** Eski tizimda har bir tur va til uchun alohida PDF skript bor edi. Yangi tizim har bir tur uchun har bir tildagi shablonni (sarlavha, matn, pastki qism, imzolovchi) ekranda tahrirlaydi.
- **Toʻsishlar.** Eski tizim fanlarga yozilishni rad etish roʻyxatidan qatʼi nazar maʼlumotnoma berardi. Yangi tizim maʼlumotnomalari toʻsilgan talabaga maʼlumotnoma bermaydi ([20-bob](20-registration.md)).
- Toʻlovlar qayd etilmaydi (eski tizim faqat summani qayd etardi).
- Talabalar tomonidan onlayn soʻrov yoʻq (koʻchirish rejasi 17.5). Maʼlumotnomalarni xodimlar beradi.

## Turlar va shablonlar

**Maʼlumotnoma turlari**: kod (seriya raqamida ishlatiladi), nomi (3 tilda), kim uchun (har qanday talaba yozuvi / talabalar / bitiruvchilar), baholarni oʻz ichiga oladi, suratni oʻz ichiga oladi, faol, tartib. Koreya universiteti profili uchta tur yaratadi.

| Kod | Tur | Kim uchun | Izoh |
|---|---|---|---|
| `ENR` | Talabalik haqida maʼlumotnoma | Talabalar (bitirmagan) | |
| `GRD` | Bitirganlik haqida maʼlumotnoma | Bitiruvchilar | Bitirish sanasi, diplom raqami |
| `TRN` | Akademik maʼlumotnoma | Har qanday talaba yozuvi | Semestrlar boʻyicha yakunlangan baholar jadvali, umumiy GPA va toʻplangan kreditlar |

**Shablonlar** (tur tahrirlash ekranining pastki qismida): har bir til uchun bittadan. Sarlavha, matn (xatboshilar boʻsh qator bilan ajratiladi), pastki qism, imzolovchi. Matnda jingalak qavslardagi oʻrinbosarlar ishlatiladi.

| Oʻrinbosar | Qiymat |
|---|---|
| `{institution}` | Muassasa nomi (shu tilda) |
| `{student_name}`, `{student_number}` | Ism (shu tildagi shaklda, aks holda lotincha yozilishi), talaba raqami |
| `{program}`, `{org_unit}` | Taʼlim dasturi, tashkiliy birlik |
| `{admission_date}`, `{graduation_date}`, `{degree_number}` | Qabul sanasi, bitirish sanasi, diplom raqami |
| `{status}`, `{year_level}` | Talaba holati, kurs |
| `{birth_date}`, `{gender}`, `{nationality}` | Tugʻilgan sana, jinsi, fuqaroligi |
| `{issue_date}`, `{serial_number}`, `{purpose}`, `{copies}` | Berilgan sana, seriya raqami, maqsad, nusxalar soni |

Sanalar shu tilning formatida chop etiladi. Mavjud boʻlmagan qiymat (masalan, bitirish sanasi) boʻsh holda chop etiladi. Matn ustiga shaxsiy maʼlumotlar jadvali (ism, talaba raqami, tugʻilgan sana, jinsi, fuqaroligi, taʼlim dasturi, qabul sanasi, soʻngra bitiruvchilar uchun bitirish sanasi, aks holda holat va kurs) avtomatik qoʻshiladi.

## Sozlamalar

**Sozlamalar > Maʼlumotnoma sozlamalari**

- **Seriya raqami shabloni**: standart `{TYPE}-{YYYY}-{SEQ:6}` → `ENR-2026-000001`. `{SEQ:n}` majburiy. Ketma-ketlik har bir alohida prefiks uchun alohida hisoblanadi. `{TYPE}-{SEQ:6}` yillar oraligʻida har bir tur boʻyicha, `{YYYY}{SEQ:5}` turlar oraligʻida har bir yil boʻyicha hisoblaydi. Bir vaqtda berilgan maʼlumotnomalar hech qachon toʻqnashmaydi (hisoblagich maʼlumotlar bazasida atomar tarzda oshiriladi).
- **Tekshirishning amal qilish muddati (kun)**: 0 doimiy degani. Muddat oʻtgach, tekshirish sahifasi "muddati oʻtgan" deb javob beradi. Berish yozuvining oʻzi reyestrda qoladi.
- **Tillar boʻyicha imzolovchi**: muhr ustida chop etiladigan nom ("Rektor, Ninefruits universiteti"). Shablondagi imzolovchi ustuvor hisoblanadi.

## Maʼlumotnoma berish

Talaba yozuvi ekranidagi **Maʼlumotnoma berish**: tur, til, maqsad, nusxalar (1 dan 20 gacha). Saqlanganda PDF yangi yorliqda ochiladi.

Quyidagi hollarda berish rad etiladi:

- turning kim uchunligi mos kelmasa (bitiruvchiga talabalik haqida maʼlumotnoma, amaldagi talabaga bitirganlik haqida maʼlumotnoma)
- tur faol boʻlmasa
- shu til uchun shablon boʻlmasa
- talaba uchun **maʼlumotnomalarni toʻsish** amalda boʻlsa (cheklov, [20-bob](20-registration.md)); avval uni olib tashlang
- talaba yozuvi koʻrish qamrovidan tashqarida boʻlsa

Berish faoliyat jurnalida qayd etiladi (`certificate.issued`: talaba raqami, tur, til, seriya raqami, nusxalar, maqsad).

## Berilgan maʼlumotnomalar

**Maʼlumotnomalar > Berilgan maʼlumotnomalar**: berilgan sana, seriya raqami, tur va til, talaba raqami, ism, maqsad, nusxalar, holat, xodim. Tur va holat boʻyicha filtrlang. Koʻrish qamrovi talaba yozuvlariniki bilan bir xil.

- **PDF**: saqlangan faylni ochadi. U qayta yaratilmaydi, shuning uchun aynan berilgan holatda boʻladi.
- **Tekshirish sahifasi**: ommaviy sahifani ochadi.
- **Qayta yaratish**: joriy maʼlumotlardan yangi PDF yaratadi, masalan shablon tuzatilgandan keyin, va faylni almashtiradi. Seriya raqami va tekshirish kodi oʻzgarmaydi; nazorat summasi oʻzgaradi. Audit hodisasi `certificate.rerendered`. `certificate:render` buyrugʻi bilan bir xil.
- **Bekor qilish**: sabab majburiy. Qator va fayl saqlanib qoladi, holat bekor qilingan boʻladi va tekshirish sahifasi "bekor qilingan" deb javob beradi. Audit hodisasi `certificate.revoked`. Bekor qilingan maʼlumotnoma tiklanmaydi; yangisi beriladi.

Reyestr qatorlarini ekranda tahrirlab yoki oʻchirib boʻlmaydi.

## Tekshirish

- Tekshirish URL manzili (`/verify/{code}`) va QR kod maʼlumotnomaning pastki qismida chop etiladi. Kod tasodifiy 20 belgili satrdir.
- Sahifa tizimga kirmasdan ochiladi va daqiqasiga 30 ta soʻrov bilan cheklangan. U maʼlumotnoma berilganmi-yoʻqligini, uning turi, seriya raqami, berilgan sanasi hamda niqoblangan ism va talaba raqamini koʻrsatadi. Bekor qilingan, muddati oʻtgan va nomaʼlum kodlarning har biriga turlicha javob beriladi.
- Topshirilgan PDF faylning oʻzini tekshirish uchun reyestrdagi nazorat summasini (SHA-256) fayl xeshi bilan solishtiring. `certificate:verify {code}` buyrugʻi ham buni bajaradi.

## Talaba portali

**Maʼlumotnomalar**: talabaning oʻz yozuvi boʻyicha berilgan maʼlumotnomalar, PDF fayllari bilan. Bekor qilinganlari holati bilan roʻyxatda koʻrsatiladi, lekin talaba bekor qilingan maʼlumotnomaning PDF faylini yuklab ola olmaydi (bergan xodim yozuvni tekshirish uchun uni ochishi mumkin). Boshqa talabalarning fayllarini ochib boʻlmaydi (ilova va maʼlumotlar bazasida taʼminlanadi).

## Koʻp beriladigan savollar

- **Ism notoʻgʻri yozilgan.** Shaxsning (shu tildagi) ism variantini tuzating va **Qayta yaratish**. Agar maʼlumotnoma allaqachon topshirilgan boʻlsa, uni bekor qiling va yangisini bering.
- **Shablonni oʻzgartirdim; allaqachon berilgan maʼlumotnomalar-chi?** Ular oʻzgarmaydi. Kerak boʻlsa, **Qayta yaratish**.
- **Seriya raqami tashlab ketildi.** Berish paytida xato yuz berganda (masalan, PDF xizmati ishlamay qolsa), raqam sarflanadi va reyestr qatori qolmasligi mumkin. Raqamlarning yagonaligi kafolatlanadi, ketma-ketligi emas.
