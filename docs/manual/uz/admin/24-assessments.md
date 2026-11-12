# 24. Imtihonlar

Menyu: **Imtihonlar > Imtihon sessiyalari**, **Imtihon natijalari**, **Imtihon turlari**. Talabalar portaldagi **Imtihonlar** boʻlimida roʻyxatdan oʻtadi va natijalarini koʻradi.

| Ruxsat | Nimaga imkon beradi |
|---|---|
| `Talabalarni roʻyxatga olish, natijalarni kiritish va eʼlon qilish, tashqi natijalarni qayd etish` | Talabalarni roʻyxatga olish, roʻyxatdan chiqarish va kelmagan deb belgilash, natijalarni kiritish, CSV import qilish, natijalarni eʼlon qilish, tashqi natijalarni qayd etish va olib tashlash, natijalar reyestrini koʻrish (talaba yozuvlarini koʻrish qamrovi doirasida) |
| `Imtihon turlari, boʻlimlari va sessiyalarini boshqarish` | Turlar, boʻlimlar va sessiyalarni yaratish va tahrirlash |

Rollar boʻyicha standart sozlamalar: Registrator va Oʻquv-uslubiy boshqarmada ikkalasi ham bor.

## Eski tizimdan farqlari

- **Har bir tur uchun qatʼiy ustunlar yoʻq.** Eski tizimda (YUSTIC) `CET` da grammatika, yozish, tinglab tushunish va oʻqish degan toʻrtta ustun qattiq belgilangan, kompleks imtihon uchun har bir fanga bitta P/N harfi (`SSCORE_ADD`), kompyuter imtihoni uchun esa yana bir alohida jadval (`COMEXAM`) bor edi. Yangi tizim boʻlimlar va baholash usulini **imtihon turi** da belgilaydi va har bir imtihonni bir xil sessiya, roʻyxatga olish va natija tuzilmasi bilan boshqaradi.
- **Oʻtish qarori.** Eski tizimda toʻrt boʻlim jami oʻtish chegarasiga teng yoki undan yuqori boʻlsa, oʻtgan hisoblanardi, har bir boʻlim uchun minimal ball yoʻq edi. Yangi tizim turning (yoki sessiyaning) oʻtish bali va **har bir boʻlim uchun minimal ball** asosida qaror qiladi. Oʻtdi/oʻtmadi turlari boʻyicha qarorni xodimlar qabul qiladi.
- **Roʻyxatga olish muddati va oʻrinlar soni.** Eski tizimda bular yoʻq edi. Sessiyada roʻyxatga olishning ochilish va yopilish vaqti hamda oʻrinlar soni bor.
- **Natijalarni eʼlon qilish.** Eski tizimda talabalar natijalarni kiritilishi bilanoq va faqat oxirgi sessiya uchun koʻrardi. Yangi tizimda talabalar natijalarni sessiyaning **Natijalarni eʼlon qilish** amalidan keyin koʻradi va barcha sessiyalar natijalari saqlanadi.
- **Tashqi natijalar.** Eski tizimda TOEFL yoki TOPIK kabi tashqi testlarni qayd etadigan joy yoʻq edi (bitiruvni tekshirish varaqasidagi HSK katagi boʻsh yozuv edi). Tashqi natijalar tasdiqlovchi fayl bilan qayd etiladi.
- **Bitiruv talablari bilan bogʻlanish.** Eski tizimda bitiruvni tekshirish varaqasini inson oʻqib, qaror qabul qilardi. **Topshirilgan imtihon** talab turi buni oʻquv rejaga kiritadi ([17-bob](17-courses-and-curriculum.md)).

## Imtihon turlari va boʻlimlari

**Imtihon turlari**: kod, nomi (3 tilda), toifa (Til, Kompleks, Bitiruv ishi, Tashqi test), baholash usuli, maksimal ball, oʻtish bali, tashqi natijalarga ruxsat, faol.

| Baholash usuli | Kiritiladigan maʼlumot | Qaror |
|---|---|---|
| Ball | Har bir boʻlim uchun ball (boʻlimlar boʻlmasa, jami) | Jami ≥ oʻtish bali va har bir boʻlim ≥ uning minimal bali. Oʻtish bali boʻlmasa, xodimlar tanlaydi |
| Oʻtdi/oʻtmadi | Oʻtdi yoki oʻtmadi | Xodimlar tanlaydi |

**Boʻlimlar** (tur tahrirlash ekranining pastki qismida): kod (CSV ustuni nomi), nomi, maksimal ball, oʻtish uchun minimal ball (ixtiyoriy). Boʻlimlar boʻlsa, jami boʻlim ballarining yigʻindisi boʻladi.

Koreya universiteti profili yaratadigan turlar:

| Kod | Tur | Qaror | Izoh |
|---|---|---|---|
| `ENG_COMP` | Ingliz tili boʻyicha kompleks imtihon | Ball, 60 dan oʻtiladi | Tinglab tushunish, oʻqish, yozish, grammatika, har biri 25 ball |
| `COMP_EXAM` | Kompyuter savodxonligi boʻyicha kompleks imtihon | Oʻtdi/oʻtmadi | |
| `THESIS` | Bitiruv malakaviy ishi | Oʻtdi/oʻtmadi | |
| `TOPIK` | TOPIK (Koreys tilini bilish darajasi testi) | Oʻtdi/oʻtmadi, tashqi | Daraja va sertifikat raqami qayd etiladi |

## Sessiyalar

**Imtihon sessiyalari**: tur, sana (har bir tur uchun yagona), joy, oʻrinlar soni, roʻyxatga olish ochilishi va yopilishi, oʻtish bali (boʻsh: turning qiymati). **Roʻyxatdan oʻtganlar** roʻyxati sessiya tahrirlash ekranining pastki qismida joylashgan.

- **Talabani roʻyxatga olish** (talaba raqamini kiriting): xodimlar talabalarni roʻyxatga olish muddatidan qatʼi nazar, imtihon sanasigacha roʻyxatga olishi mumkin. Oʻrinlar soni cheklovi amal qiladi. Sanasi oʻtgan sessiyaga hech kimni roʻyxatga olib boʻlmaydi. Natijalar faqat roʻyxatdan oʻtgan talabalar uchun kiritiladi; roʻyxatdan oʻtmagan talabalarning CSV qatorlari xato sifatida koʻrsatiladi va oʻtkazib yuboriladi.
- Talabalar roʻyxatga olish muddati davomida portalda roʻyxatdan oʻtadi va bekor qiladi. Buni faqat oʻqiyotgan va akademik taʼtildagi talabalar qila oladi.
- **Natijani kiritish**: har bir boʻlim uchun ball (yoki jami), kerak boʻlganda oʻtdi yoki oʻtmadi, izoh. Eʼlon qilishdan oldin qayta kiritish uni qayta yozadi (audit hodisasi `assessment.result_recorded`). **Eʼlon qilingandan keyin u bloklanadi** (quyidagi tuzatish soʻrovlariga qarang).
- **Kelmadi**: roʻyxatdan oʻtgan, lekin imtihonga kelmagan. Natija kiritilsa, roʻyxatga olish yana roʻyxatdan oʻtgan holatiga qaytadi.
- **Roʻyxatdan chiqarish**: natijasi bor roʻyxatga olishni bekor qilib boʻlmaydi.
- **Natijalarni eʼlon qilish**: shundan boshlab talabalar ushbu sessiya natijalarini koʻradi. Eʼlon qilingandan keyin natijalarni bevosita tahrirlab yoki oʻchirib boʻlmaydi; ular **tuzatish soʻrovi** orqali oʻzgaradi (2026-10-23 dagi qaror). `assessment:publish-results` buyrugʻi bilan bir xil.
- Sessiyaning **oʻtish bali** (turnikini bekor qiladi): imtihon qiyin boʻlgani sababli majlis oʻtish chegarasini pasaytirganda qoʻllaniladi. Uni **eʼlon qilishdan oldin** oʻzgartirish mumkin va oʻzgargan zahoti shu sessiya uchun allaqachon kiritilgan barcha natijalar saqlangan ballar asosida **qayta baholanadi** (jami yangi chegaraga, boʻlim minimumlari turda belgilanganicha). Sabab koʻrsatilganda `assessment.pass_score_changed` audit hodisasi eski va yangi chegaralarni qarori oʻzgargan talabalar raqamlari bilan birga saqlaydi. **Eʼlon qilingandan keyin** u bloklanadi (faqat tuzatish soʻrovlari). Har bir boʻlim uchun minimal ballni sessiyada oʻzgartirib boʻlmaydi (tur sozlamasi).
- **Natijalarni import qilish (CSV)**: format quyida. Roʻyxatdan oʻtmagan talabalar qatorlari xato sifatida oʻtkazib yuboriladi. `assessment:import-results` buyrugʻi bilan bir xil.

CSV formati (sarlavha qatori, UTF-8):

| Ustun | Mazmuni |
|---|---|
| `student_number` | Talaba raqami (majburiy) |
| Boʻlim kodi (`LISTENING`, …) | Boʻlim bali. Katta-kichik harf farqlanmaydi |
| `total` | Boʻlimlarsiz turlar uchun jami |
| `passed` | `Y`/`N`. Oʻtdi/oʻtmadi turlari yoki oʻtish bali boʻlmagan turlar uchun majburiy |
| `absent` | `Y` talabani kelmagan deb belgilaydi |
| `note` | Izoh |

Xato qatorlar oʻtkazib yuboriladi va natija xabarnomasida (ekran) yoki chiqishda (buyruq) qator raqami bilan koʻrsatiladi.

## Natijalarni tuzatish

Eʼlon qilingan sessiya natijalarini oʻzgartirish tartibi. U baholarni oʻzgartirish kabi ishlaydi.

1. Sessiyaning roʻyxatdan oʻtganlar roʻyxatidan **Tuzatishni soʻrash**: yangi ballar (yoki oʻtdi yoki oʻtmadi) va sabab. Har bir natija uchun bitta koʻrib chiqilayotgan soʻrov. Audit hodisasi `assessment.correction_requested`.
2. **Imtihonlar > Natijalarni tuzatish** boʻlimida `Imtihon turlari, boʻlimlari va sessiyalarini boshqarish` ruxsatiga ega xodim **Tasdiqlash** (izoh ixtiyoriy) yoki **Rad etish** (izoh majburiy) tugmasini bosadi. **Hech kim oʻz soʻrovi boʻyicha qaror qabul qila olmaydi.**
3. Tasdiqlanganda natija darhol yangi qiymatlarga oʻzgaradi va talaba uni koʻradi. Audit hodisalari `assessment.correction_approved`, `assessment.result_corrected`. Soʻrov beruvchi qaror qabul qilinguncha soʻrovni bekor qilishi mumkin.

Roʻyxat standart holatda koʻrib chiqilayotgan soʻrovlarni joriy qiymatlar va taklif etilgan qiymatlar bilan yonma-yon koʻrsatadi.

## Natijalar reyestri

**Imtihon natijalari**: barcha natijalar (sessiyalar va tashqi). Tur, manba va oʻtganlik boʻyicha filtrlang. Koʻrish qamrovi talaba yozuvlariniki bilan bir xil.

- **Tashqi natijani qayd etish**: talaba raqami, tur (faqat tashqi natijalarga ruxsat beruvchi turlar), topshirilgan sana (kelajakdagi sana emas), jami, daraja, oʻtdi yoki oʻtmadi, sertifikat raqami, tasdiqlovchi fayl (PDF, JPG, PNG, 5 MB), izoh. Audit hodisasi `assessment.external_recorded`.
- **Tasdiqlovchi hujjat**: faylni ochadi. Talabalar oʻz fayllarini ocha oladi.
- **Olib tashlash**: sabab majburiy, audit hodisasi `assessment.result_removed`. Notoʻgʻri kiritilgan tashqi natijani yoki eʼlon qilinmagan natijani oʻchirib, qayta qayd etish uchun. Eʼlon qilingan sessiya natijalarini olib tashlab boʻlmaydi (tuzatish soʻrovi).

## Bitiruv talablari

**Bitiruv talablari** muharririda **Topshirilgan imtihon** talab turini tanlang va imtihon turini koʻrsating. Talaba bu talabni shu turdagi kamida bitta oʻtgan natija (sessiya yoki tashqi) bilan bajaradi. Portaldagi **Imtihonlar** sahifasining yuqori qismida talabaning oʻquv rejasi talab qiladigan turlar va ulardan oʻtilganligi koʻrsatiladi. Bitiruv talablarini tekshirish (11-bosqich) bu talabni oʻqiydi.

## Talaba portali

**Imtihonlar**: oʻquv reja talablari va ulardan oʻtilganligi, roʻyxatga olish ochiq sessiyalar (roʻyxatdan oʻtish, bekor qilish), mening roʻyxatdan oʻtishlarim, mening natijalarim (eʼlon qilingan sessiyalar va tashqi natijalar, boʻlim ballari, tasdiqlovchi hujjat).

## Koʻp beriladigan savollar

- **Talabani roʻyxatga olish muddatidan tashqarida roʻyxatga olishim kerak.** Xodimlar ularni sessiyadagi **Roʻyxatdan oʻtganlar > Talabani roʻyxatga olish** orqali roʻyxatga oladi.
- **Oʻtish balini faqat shu sessiya uchun oʻzgartirmoqchiman.** Natijalar eʼlon qilinishidan oldin sessiyaning oʻtish balini kiriting. Allaqachon kiritilgan barcha natijalar saqlangan ballar asosida darhol qayta baholanadi (boʻlimlar boʻyicha minimal ballar turdagidek qoladi). Eʼlondan keyin oʻtish bali qulflanadi; tuzatish soʻrovlaridan foydalaning.
- **Ballni notoʻgʻri kiritdim.** Eʼlon qilishdan oldin uni qayta yozish uchun yana **Natijani kiritish**. Eʼlon qilingandan keyin **tuzatish soʻrovi** yuboring va uni boshqa xodimga tasdiqlating.
- **Kelmaganlar statistikasi.** Roʻyxatdan oʻtganlar roʻyxatini holat boʻyicha filtrlang.
