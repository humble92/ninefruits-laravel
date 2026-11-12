# 20. Fanlarga yozilish

Menyu: **Fanlarga yozilish > Fanlarga yozilishlar**, **Cheklovlar**, **Qoidalardan istisnolar**, **Fanlarga yozilish qoidalari**, **Kredit chegaralari**, **Yozilishga urinishlar** hamda **Fan guruhlari** tahrirlash ekranidagi **Talabalar roʻyxati** va **Kutish roʻyxati** yorliqlari

| Ruxsat | Nimaga imkon beradi |
|---|---|
| `Fanlarga yozilishlar va talabalar roʻyxatlarini koʻrish` | Fanlarga yozilishlar, talabalar roʻyxatlari va yozilishga urinishlarni koʻrish |
| `Talabalar nomidan fanlarga yozish, chiqarish va voz kechtirish (sababi bilan)` | Xodim tomonidan fanga yozish, fandan chiqarish va voz kechtirish, kutish roʻyxatidan olib tashlash va darhol oʻtkazish. Tashkiliy birlik qamrovi bilan tayinlanganda faqat shu boʻlinma ochgan fan guruhlari |
| `Cheklov qoʻyish va olib tashlash, qoidalardan istisno berish` | Cheklovlar, qoidalardan istisnolar. Qamrov bilan tayinlanganda faqat shu qamrovdagi talabalar |
| `Fanlarga yozilish qoidalari toʻplamlari va kredit chegaralarini boshqarish` | Semestr boʻyicha qoidalar toʻplamlari, kredit chegaralari |

Rollar boʻyicha standart sozlamalar: Registratorda barcha toʻrt ruxsat bor, Oʻquv-uslubiy boshqarmada koʻrish hamda qoidalar va kredit chegaralari, Kafedra yordamchisida koʻrish va fanlarga yozilishni tuzatish ruxsatlari bor (kafedra qamrovi bilan tayinlanadi).

## Eski tizimdan farqlari

- Eski tizimda (YUSTIC) talaba raqami raqamlari yoki kafedra kodlariga qattiq bogʻlangan qoidalar, masalan kredit chegaralari yoki kredit banki fanlarini chiqarib tashlash, **semestr boʻyicha sozlamalar**ga aylandi. Eski YUST qoidalari `yust-legacy` oʻrnatish profili orqali qayta yaratiladi (quyida).
- Soʻrov rad etilganda **barcha sabablar birdaniga koʻrsatiladi** (eski tizim birinchi sababdayoq toʻxtardi). Har bir urinish va har bir qoida boʻyicha natija **Yozilishga urinishlar** da saqlanadi.
- 2014-yildan boshlangan talaba raqamlariga ega hamshiralik fakulteti uchun eski tizimdagi kredit chegarasi xatosi (21 kredit oʻrniga 18 kredit qoʻllanilgan) takrorlanmaydi. 21 kredit qoʻllaniladi.
- Fanlarga yozilish paytida darslik sotib olish bandi yoʻq.

## Semestrni tayyorlash

1. **Jarayon muddatlari** ([10-bob](10-terms.md)): fanlar savati, fanlarga yozilish (kurslar boʻyicha bosqichma-bosqich ochilish), qoʻshish/chiqish, voz kechish. Qoʻshish/chiqish va voz kechish muddatlari mashgʻulotlar davri boʻyicha belgilanadi.
2. **Fanlarga yozilish qoidalari > Semestr uchun qoidalarni tayyorlash**: semestrni va nusxa olinadigan oldingi semestrni tanlang. Standart qiymatlardan boshlash uchun uni boʻsh qoldiring. Qoidalar toʻplami boʻlmagan semestr standart qiymatlardan foydalanadi.
3. **Kredit chegaralari**: semestr turi va talabalar guruhi boʻyicha chegaralar (quyida).
4. **Fan guruhlari**: oʻrinlar soni, ajratilgan oʻrinlar (ajratilgan oʻrinlar va ularning ochilish vaqti), **Kutish roʻyxati hajmi** ([18-bob](18-sections-and-timetables.md)). Fanga yozilish qabul qilinishi uchun fan guruhi **eʼlon qilingan** boʻlishi kerak.
5. **Cheklovlar**: cheklovga ega talabalar roʻyxatini kiriting, masalan toʻlanmagan kontrakt toʻlovi (CSV import mumkin, [9-bob](09-csv-import.md)).
6. Tekshirish: `php artisan registration:preflight 2027-SP` ([operatsion buyruqlar](../operations/commands.md)). U muddatlar, qoidalar toʻplami, oʻrinlar soni va kredit chegarasi belgilanmagan talabalar soni haqida hisobot beradi.

## Qoidalar

Barcha qoidalar quyidagi tartibda tekshiriladi. **Muddat, fan guruhining ochiqligi va oʻrinlar** har doim qoʻllaniladi va roʻyxatga kiritilmagan.

| Qoida | Nimani tekshiradi | Parametrlar |
|---|---|---|
| Yozilish muddati | Talabaning maqsadli mezonlariga mos jarayon muddati ochiq boʻlishi kerak. Fandan chiqish uchun fanlarga yozilish muddati yoki fan mashgʻulotlar davrining qoʻshish/chiqish muddati, voz kechish uchun esa mashgʻulotlar davrining voz kechish muddati kerak | Yoʻq |
| Cheklovlar | Cheklov fanlarga yozilishni toʻsganda rad etiladi | Yoʻq |
| Talaba holati | Faqat tanlangan talaba holatlari (standart: oʻqiyotgan) fanlarga yozila oladi | Ruxsat etilgan holatlar |
| Fan guruhi shartlari | Fan guruhining yozilish va chiqarib tashlash mezonlari | Yoʻq |
| Bir fanga ikki marta | Bir semestrda bitta fanga faqat bir marta | Yoʻq |
| Vaqt toʻqnashuvi | Vaqt boshqa yozilgan fan guruhi bilan ustma-ust tushsa, rad etiladi. Sanalari bir-birini qoplamaydigan semestrning birinchi va ikkinchi yarmi toʻqnashmaydi | Ruxsat etilgan ustma-ust tushish (daqiqa) |
| Kredit chegarasi | Yozilgan kreditlar jami ≤ qoʻllaniladigan kredit chegarasi. "Kredit chegarasiga hisoblanadi" sozlamasi oʻchiq fanlar hisoblanmaydi | Yoʻq (Kredit chegaralari ekranida belgilanadi) |
| Prerekvizitlar | Prerekvizitlardan oʻtilgan (muqobil fanlar hisoblanadi, minimal baho); korekvizitlarga shu semestrda yozilish mumkin | Yoʻq (fan ekranida belgilanadi) |
| Qayta oʻqishlar | Oʻtilgan fanni qayta oʻqish mumkinmi | Qayta oʻqishga ruxsat, maksimal urinishlar, faqat shu bahodan past boʻlsa |

Har bir qoidaning oʻz sozlamalari bor.

- **Yoqilgan**: oʻchiq boʻlsa, qoida tekshirilmaydi.
- **Bajarilmasa**: Toʻsish yoki Faqat ogohlantirish. Ogohlantirishda fanga yozilish amalga oshadi va talaba faqat xabarni koʻradi.
- **Istisnoga ruxsat**: qoidalardan istisnolar va xodim tomonidan fanga yozishdagi "Qoidalarni chetlab oʻtish" maydoni ishlashi uchun yoqilgan boʻlishi kerak. Cheklovlar va bir fanga ikki marta qoidalari uchun standart holatda oʻchiq.

Qayta oʻqishlar avtomatik aniqlanadi va fanga yozilish **Qayta oʻqish** deb belgilanadi. Qayta oʻqishlarda baholarni qayta ishlash 6-bosqichga tegishli.

## Kredit chegaralari

Semestr turi (boʻsh: barcha semestr turlari), talabalar (kurs, qabul yili, dastur, tashkiliy birlik, akademik holat), maksimal kreditlar va ustuvorlikni belgilang. Akademik holat semestr baholari yopilganda belgilanadi ([21-bob](21-grades.md)). Masalan, akademik ogohlantirish olgan talabalarni 12 kredit bilan cheklash uchun akademik holatda Akademik ogohlantirishni tanlang va chegaraga umumiy chegaradan yuqoriroq ustuvorlik bering (demo maʼlumotlar shunday sozlangan). Talabaga bir nechta chegara mos kelsa, faqat **eng yuqori ustuvorlikka ega boʻlgani** qoʻllaniladi. Hech biri mos kelmasa, chegara yoʻq (`registration:preflight` bu haqda xabar beradi).

Misol: eski YUST qoidalari (`yust-legacy` profili)

| Semestr turi | Talabalar | Maksimal | Ustuvorlik |
|---|---|---|---|
| 1- va 2-semestrlar | 2014-yil va undan keyin qabul qilinganlar, hamshiralik fakulteti (tashkiliy birlik kodi `NURS`) | 21 | 40 |
| 1- va 2-semestrlar | 2014-yil va undan keyin qabul qilinganlar | 18 | 30 |
| 1- va 2-semestrlar | 2013-yil va undan oldin qabul qilinganlar, hamshiralik fakulteti | 20 | 20 |
| 1- va 2-semestrlar | 2013-yil va undan oldin qabul qilinganlar | 17 | 10 |
| Yozgi va qishki | 2014-yil va undan keyin qabul qilinganlar, hamshiralik fakulteti / boshqalar | 6 / 5 | 40 / 30 |
| Yozgi va qishki | 2013-yil va undan oldin qabul qilinganlar | 6 | 10 |

Kodlari `009` yoki `900` bilan boshlanadigan kredit banki fanlari uchun fanda "Kredit chegarasiga hisoblanadi" ni oʻchiring. Profil `setup:install --profile=yust-legacy` bilan oʻrnatiladi (u Koreya universiteti profilini ham oʻz ichiga oladi).

## Oʻrinlar va kutish roʻyxati

- Oʻrin fanga yozilish paytida fan guruhi qatorini bloklash orqali band qilinadi. Koʻp talabalar bir vaqtda yozilsa ham oʻrinlar sonidan oshib ketmaydi.
- **Ajratilgan oʻrinlar** (masalan, mutaxassislik talabalari uchun 30 oʻrin) ochilish vaqtigacha faqat oʻz talabalari tomonidan ishlatiladi. Ochilish vaqtida qolgan ajratilgan oʻrinlar ochiq oʻrinlarga aylanadi. Portal ularni "ochiq, ajratilgan, jami" koʻrinishida koʻrsatadi.
- **Kutish roʻyxati hajmi** 0 dan katta boʻlsa, talabalar toʻlgan fan guruhining **Kutish roʻyxatiga qoʻshilish** imkoniga ega. Kutish roʻyxatiga qoʻshilish uchun ham oʻrinlardan tashqari barcha qoidalardan oʻtish kerak.
- Fandan chiqish, voz kechish yoki ajratilgan oʻrinlarning boʻshatilishi natijasida oʻrin boʻshasa, kutayotgan talabalar navbat boʻyicha **avtomatik oʻtkaziladi**. Oʻtkazishda barcha qoidalar qayta tekshiriladi. Bu orada boshqa fanga yozilib, endi kredit chegarasidan oshgan yoki vaqt toʻqnashuviga ega talaba sababi qayd etilgan holda kutish roʻyxatidan olib tashlanadi. Talabaga e-pochta xati yuboriladi.
- Fanlarga yozilish va qoʻshish/chiqish muddatlari toʻliq yopilgach, kutish roʻyxatida qolgan yozuvlarning muddati oʻtadi.
- Oʻrinlar sonini allaqachon yozilgan talabalar sonidan kamaytirib boʻlmaydi.

Kutish roʻyxati tartibini oʻzgartirishning imkoni yoʻq. Muayyan talabani birinchi oʻringa qoʻyish uchun **Talabani fanga yozish** dan foydalaning (fan guruhi toʻlgan boʻlsa, oʻrinlar sonini oshiring).

## Talaba ekrani

Portaldagi **Fanlarga yozilish** boʻlimida talaba semestrni tanlaydi, fan kodi yoki nomi boʻyicha qidiradi va fanga yoziladi, savatga qoʻshadi yoki kutish roʻyxatiga qoʻshiladi. Ekranda yozilish muddatining holati (ochiq, faqat savat, ochilish vaqti), kredit chegarasiga nisbatan yozilgan kreditlar va dars jadvali koʻrsatiladi.

- **Savat**: savat muddati davomida talabalar fanlarni yigʻadi va fanlarga yozilish ochilgach **Hammasiga yozilish** tugmasini bosadi. Savatga qoʻshishda qoidalar oldindan tekshiriladi va muammolar haqida xabar beriladi, lekin faqat muddat, fan guruhining ochiqligi va bir fanga ikki marta tekshiruvlari qoʻshishga toʻsqinlik qiladi. Fanga yozilishda qoidalar qayta tekshiriladi.
- Chiqish va voz kechish tugmalari jarayon muddatlariga qarab oʻzgaradi. Voz kechilgan fan tarixda **Voz kechilgan** sifatida qoladi (W bahosi 6-bosqichga tegishli).
- Har bir shaxs daqiqasiga 30 tagacha soʻrov yuborishi mumkin.

## Fanlarga yozilishlar va xodim tomonidan tuzatishlar

**Fanlarga yozilishlar**: semestr va holat boʻyicha filtrlang, talaba raqami yoki ism boʻyicha qidiring. Standart holatda joriy (yoki keyingi) semestrning yozilgan yozuvlari koʻrsatiladi.

**Talabani fanga yozish** (Fanlarga yozilishlar yuqori qismida yoki fan guruhining **Talabalar roʻyxati** yorligʻida): talaba raqami, fan guruhi, chetlab oʻtiladigan qoidalar va sababni kiriting.

- Talabalar uchun qoʻllaniladigan qoidalarning oʻzi tekshiriladi. **Yozilish muddati xodim tomonidan fanga yozishga qoʻllanilmaydi.**
- "Qoidalarni chetlab oʻtish" faqat **Istisnoga ruxsat** yoqilgan qoidalar uchun ishlaydi. Fan guruhining ochiqligi va oʻrinlarni tanlab boʻlmaydi (uning oʻrniga oʻrinlar sonini oshiring).
- Natija (yozildi, kutish roʻyxatiga qoʻshildi, sabablari bilan rad etildi) xabarnoma sifatida koʻrsatiladi. U faoliyat jurnalida sababi bilan qayd etiladi.

**Chiqarish va voz kechtirish**: Fanlarga yozilishlar qatoridan yoki Talabalar roʻyxati yorligʻidan, sabab bilan. Jarayon muddatlaridan qatʼi nazar mumkin. Oʻrin boʻshaganda kutish roʻyxatidan oʻtkazish amalga oshadi.

## Cheklovlar va qoidalardan istisnolar

**Cheklovlar** (eski tizimdagi fanlarga yozilishni rad etish `SRDENY`): talaba raqami, cheklov turi (Semestrga roʻyxatdan oʻtmagan, Moliyaviy, Akademik, Maʼmuriy, Boshqa), taʼsiri (fanlarga yozilishni / baholarni koʻrishni / maʼlumotnomalarni toʻsadi), boshlanishi va tugashi, sabab. Baholarni koʻrish va maʼlumotnomalarni toʻsish standart holatda oʻchiq. Toʻlanmagan kontrakt toʻlovidan tashqari ular akademik halollik buzilishini tekshirish kabi baholarni bir muddat ushlab turish zarur boʻlgan maʼmuriy holatlarda ham qoʻllaniladi. Baholarni koʻrish toʻsilganda talaba portaldagi baholar ekranini yoki baholar hisobotini koʻra olmaydi ([21-bob](21-grades.md)). Xodimlar ekranlari va oʻqituvchilarning baho kiritishiga taʼsir qilmaydi. Maʼlumotnomalar toʻsilganda xodimlar maʼlumotnoma bera olmaydi ([23-bob](23-certificates.md)). Cheklovlar oʻchirilmaydi; ular sabab koʻrsatilib **olib tashlanadi**. Standart roʻyxat amaldagi cheklovlarni koʻrsatadi.

**Qoidalardan istisnolar** (eski tizimdagi fanlarga yozilishga ruxsat `SRALLOW`): talaba, semestr, qoida, nimaga qoʻllanilishi (boʻsh: butun semestr yoki muayyan fan guruhi yoki fan), amal qilish muddati, sabab. Istisno mavjud boʻlsa, shu qoida bajarilmasa ham fanga yozilish amalga oshadi (qoidada Istisnoga ruxsat yoqilgan boʻlsa). Yozilish muddati qoidasini ham istisno orqali ochish mumkin (masalan, muddatni oʻtkazib yuborgan bitiruvchi kurs talabasi). Fan guruhining ochiqligi va oʻrinlar uchun istisno berib boʻlmaydi. Istisno endi kerak boʻlmaganda uni **Bekor qilish**.

## Yozilishga urinishlar

Har bir fanga yozilish, chiqish, voz kechish, kutish roʻyxati, oʻtkazish va savat urinishi vaqti, natijasi, bajarilmagan qoidalar va xabarlar, uni bajargan xodim yoki talaba hamda soʻrov ID si bilan saqlanadi. "Nega fanga yozila olmadim" degan savollar uchun talaba raqami boʻyicha qidiring. Talaba hozir fanga yozilsa nima boʻlishini `php artisan registration:simulate {student number} {course code-section number}` bilan oldindan koʻrish mumkin.
