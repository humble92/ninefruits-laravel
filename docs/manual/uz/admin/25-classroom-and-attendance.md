# 25. Oʻqituvchi auditoriyasi va davomat

Menyu: oʻqituvchilar portaldagi **Mening darslarim > Roʻyxat va davomat** dan, talabalar portaldagi **Davomat** dan foydalanadi. Xodimlar **Oʻquv reja va fan guruhlari > Fan guruhlari > Tahrirlash** ekranining pastki qismidagi **Darslar** dan foydalanadi.

| Kim | Nima qila oladi |
|---|---|
| Tayinlangan oʻqituvchilar | Talabalar roʻyxati (suratlar bilan) va CSV, davomat jurnali PDF, darslarni yaratish, qoʻshish va bekor qilish, davomatni kiritish |
| `Fan guruhlarini rejalashtirish (oʻqituvchilar, vaqtlar, auditoriyalar)` ruxsatiga ega xodimlar (qamrov doirasida) | Oʻqituvchilar bilan bir xil + boshqaruv panelidagi darslar |
| `Fan guruhlarini koʻrish` ruxsatiga ega xodimlar (qamrov doirasida) | Talabalar roʻyxati, davomat jurnali, davomatni koʻrish |
| Talabalar | Oʻz davomati |

Eʼlonlar, topshiriqlar va materiallar kabi auditoriya funksiyalari LMS ga qoldirilgan (koʻchirish rejasi, 12-boʻlim).

## Eski tizimdan farqlari

- **Eski tizim (YUSTIC) davomatni qayd etmasdi.** Uning "davomat jurnali" talabalar roʻyxati yonida boʻsh kataklarni (nusxaga qarab 27 tadan 34 tagacha) chop etadigan PDF boʻlib, unda sanalar ham, jamilar ham yoʻq edi. Yangi tizim dars vaqtlaridan **darslar** yaratadi, har bir dars uchun qatnashdi, kechikdi, qatnashmadi yoki sababli holatini qayd etadi va yozuvlar hamda jamilarni davomat jurnali PDF ida chop etadi.
- **Talabalar roʻyxati.** Eski tizimning oʻqituvchi ekrani faqat talaba raqamlari va ismlarini koʻrsatardi (va qalqib chiquvchi oynada fuqarolik roʻyxatga olish raqami va manzilni oshkor qilardi), Excel yuklab olish imkoni ham yoʻq edi. Yangi tizim faqat talaba raqami, ism, surat, taʼlim dasturi, kurs va aloqa e-pochtasini koʻrsatadi va CSV sifatida yuklab beradi. Yuklab olishlar audit jurnalida qayd etiladi.
- **Oʻqituvchilarning oʻz talabalariga kirishi.** Oʻqituvchilar oʻz fan guruhlaridagi talabalarning talaba yozuvlari va shaxslarini koʻra olishi uchun RLS siyosati qoʻshildi (avval ular faqat oʻz maslahat oluvchilarini koʻra olardi, shu sababli hatto baho kiritish ekranida ham ismlar boʻsh qolardi).

## Darslar

- **Yaratish**: haftalik dars vaqtlarining (kun, vaqt, para) har bir takrori uchun bitta dars. Oʻqituvchilar **Dars vaqtlaridan yaratish** tugmasidan, xodimlar esa boshqaruv panelidagi **Darslar > Darslarni yaratish** dan yoki `attendance:generate-sessions {term}` buyrugʻidan (butun semestr) foydalanadi. Mavjud darslar va davomatga tegilmaydi, shuning uchun dars vaqtlari oʻzgargandan keyin uni qayta ishga tushirish mumkin.
- **Dars qoʻshish**: dars vaqtlaridan tashqari dars, masalan qoʻshimcha (oʻrnini toʻldiruvchi) dars. Faqat mashgʻulotlar davri ichidagi sanalar.
- **Bekor qilish**: darsni sabab koʻrsatib bekor qiling. Dars saqlanib qoladi, lekin jamilarga kiritilmaydi. **Tiklash** uni qaytaradi.
- Bayramlar taqvimi yoʻq. Bayram kunlaridagi darslarni bekor qiling (13.2-boʻlimga qarang; semestr taqvimi qoʻshilgach, bu avtomatik boʻladi).

## Davomatni kiritish

Darsning **Davomat** boʻlimida har bir talaba uchun qatnashdi, kechikdi, qatnashmadi yoki sababli holatini tanlang va izoh qoʻshing (200 belgi). Eng tez usul — **Hammasi qatnashdi** ni bosib, soʻngra istisnolarni oʻzgartirish.

- Davomatni dars kunidan boshlab kiritish mumkin (kelajakdagi darslar uchun emas).
- Qayta saqlanganda faqat oʻzgargan talabalar qayd etiladi va audit hodisasi (`attendance.recorded`) eski va yangi qiymatlarni saqlaydi.
- Holatni tozalash talabani "qayd etilmagan" holatiga qaytaradi.

## Jamilar

| Band | Taʼrif |
|---|---|
| Oʻtkazilgan darslar | Bekor qilinmagan va bugun yoki undan oldin boʻlgan darslar |
| Davomat foizi | (qatnashdi + kechikdi + sababli) ÷ oʻtkazilgan darslar × 100 |
| Qayd etilmagan | Yozuvsiz oʻtkazilgan darslar |

Maʼlum miqdordagi kechikishlarni bitta qatnashmaslikka aylantiradigan yoki darslarning uchdan biridan koʻpiga qatnashmaganlikda F qoʻyadigan qoida yoʻq. Bu universitetlar orasida farq qiladi, shuning uchun oʻqituvchilar davomatni bahoda oʻzlari aks ettiradi (koʻrib chiqish bandi 13.5).

## Davomat jurnali PDF va talabalar roʻyxati CSV

Ikkala yuklab olish ham audit jurnalida qayd etiladi (`attendance.sheet_exported`; talabalar roʻyxati CSV avvalgidek). `=`, `+`, `-` yoki `@` bilan boshlanadigan CSV qiymatlari formula sifatida bajarilmasligi uchun boshiga bitta tirnoq qoʻyilib eksport qilinadi.

- **Davomat jurnali (PDF)**: albom formatidagi A4. Talabalar pastga, darslar yonga (bekor qilinganlari kiritilmaydi), O/△/X/E belgilari va har bir talaba boʻyicha jamilar. Hali boʻlmagan darslar boʻsh qoladi, shuning uchun u qoʻlda qayd etish uchun ham xizmat qiladi.
- **Talabalar roʻyxati (CSV)**: talaba raqami, ism, taʼlim dasturi, kurs, aloqa e-pochtasi, fanga yozilish holati. BOM bilan UTF-8, shuning uchun Excel da toʻgʻridan-toʻgʻri ochiladi. Har bir yuklab olish `attendance.roster_exported` audit hodisasida qayd etiladi.

## Talaba portali

**Davomat**: har bir semestr va fan boʻyicha qatnashgan, kechikkan, qatnashmagan va sababli darslar soni, davomat foizi va har bir dars boʻyicha yozuv (faqat oʻqituvchi kiritgan maʼlumotlar).

## Ish tartibi

1. Semestr boshlanishidan oldin fan guruhlarining dars vaqtlarini yakunlang ([18-bob](18-sections-and-timetables.md)).
2. Darslarni `php artisan attendance:generate-sessions {term} --actor=...` bilan yarating (oʻqituvchilar ularni ekranda ham yaratishi mumkin).
3. Dars vaqtlari oʻzgarganda uni qayta ishga tushiring. Olib tashlangan vaqtlarning darslari avtomatik oʻchirilmaydi; ularni bekor qiling.
