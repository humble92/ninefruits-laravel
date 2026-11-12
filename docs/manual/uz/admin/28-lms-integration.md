# 28. LMS integratsiyasi

Menyu: **LMS integratsiyasi > LMS platformalari**, **Kurs bogʻlanishlari**, **Sinxronlash jurnallari** (`LMS platformalari, bogʻlanishlar va sinxronlashni boshqarish` ruxsati; standart: Tizim administratori va Registratordan tashqari Oʻquv-uslubiy boshqarma). Oʻqituvchilar **Mening darslarim** va fan guruhi tafsilotlaridagi LMS havolalaridan hamda baho kiritish ekranidagi **Ballarni LMS dan yuklash** dan foydalanadi. Yuklash baho kiritish bilan bir xil shartlarga boʻysunadi (oʻqituvchilar baho kiritish muddati ichida, xodimlar oʻzlarining **Baholar** boshqaruv qamrovi doirasida).

Dasturchilar uchun tafsilotlar [LMS integratsiyasi qoʻllanmasi](../../../lms-integration-guide.md) da.

## Nima qayerga uzatiladi

| SIS → LMS | Qachon |
|---|---|
| Fan guruhi (kurs yaratish) + oʻqituvchilar | Fan guruhi **eʼlon qilinganda** va `lms:sync` bilan |
| Tasdiqlangan fanga yozilish → kursga yozish | Fanga yozilish, kutish roʻyxatidan oʻtkazish yoki xodim tomonidan fanga yozishda darhol |
| Fandan chiqish yoki voz kechish → kursdan chiqarish | Darhol |
| Toʻliq solishtirish | Har kuni soat 02:30 da (`lms:reconcile`) |

| LMS → SIS | Qachon |
|---|---|
| Yakuniy ballar (qoralama) | Oʻqituvchi baho kiritish ekranida **Ballarni LMS dan yuklash** tugmasini bosganda. Saqlash va topshirishni oʻqituvchi bajaradi |

Yakunlangan baholar LMS ga yuborilmaydi. Kurs materiallari, topshiriqlar va testlar LMS da joylashadi.

## Platformalar

**LMS platformalari**: kod, nomi, drayver, asosiy URL, kirish maʼlumotlari (kalit/qiymat, shifrlangan holda saqlanadi, tahrirlashda boʻsh qoldirilsa saqlanib qoladi), faol, talabalar roʻyxatini sinxronlash, yakuniy ballarni olish.

- **Yoʻq (faqat qayd etadi)** drayveri: hech narsani chaqirmaydi va nima yuborilishi kerakligini qayd etadi. Integratsiyadan oldin jarayonni tekshirish yoki namoyish uchun foydalaning. Demo maʼlumotlardagi `DEMO` platformasi undan foydalanadi.
- `Moodle`, `Open edX`: hali konnektor yoʻq (hali amalga oshirilmagan deb belgilangan). Ular tanlansa, sinxronlashlar muvaffaqiyatsiz deb qayd etiladi.
- **Faol** yoqilsa, shundan boshlab fan guruhlarini eʼlon qilish va fanlarga yozilishdagi oʻzgarishlar darhol uzatiladi va platforma tungi solishtirishga kiritiladi.

## Kurs bogʻlanishlari va sinxronlash jurnallari

- **Kurs bogʻlanishlari**: qaysi fan guruhi qaysi platforma kursiga bogʻlangani (ID, URL), holat (Kutilmoqda/Bogʻlangan/Muvaffaqiyatsiz), oxirgi sinxronlash va xato. **Qayta sinxronlash** fan guruhi, oʻqituvchilar va talabalar roʻyxatini qaytadan yuboradi.
- **Sinxronlash jurnallari**: har bir qadam (kurs, aʼzo qoʻshish va olib tashlash, ballarni olish, solishtirish) boʻyicha natija va xabar. Muvaffaqiyatsizlik sababini shu yerdan qidiring.
- Buyruqlar: `lms:sync {platform} --term={term}` (toʻliq yuborish, `--dry-run`), `lms:reconcile` (solishtirish).

## Portal

- Platforma kursi havolasi talabalar va oʻqituvchilar uchun fan tafsilotlarida hamda oʻqituvchining **Mening darslarim** roʻyxatida (faqat bogʻlangan fan guruhlari) koʻrsatiladi.
- Baho kiritish: platformada **Yakuniy ballarni olish** yoqilgan va fan guruhi bogʻlangan boʻlsa, **Ballarni LMS dan yuklash** tugmasi paydo boʻladi. U faqat ball ustunini toʻldiradi va saqlamaydi.

## Koʻp beriladigan savollar

- **LMS da talaba yoʻq.** Talaba sinxronlanishi uchun uning hisobi (foydalanuvchisi) boʻlishi kerak. Hisobi boʻlmasa, uni taklif qiling ([14-bob](14-merge-and-invitations.md)); keyingi solishtirish uni qoʻshadi.
- **Sinxronlash muvaffaqiyatsizligicha qolmoqda.** Sinxronlash jurnalidagi xabarni oʻqing. Koʻpincha kirish maʼlumotlarining muddati oʻtgan boʻladi. Platformaning tahrirlash ekranida qiymatlarni oʻzgartiring va **Qayta sinxronlash**.
- **Platformani almashtirdik.** Yangi platformani yarating va eskisining Faol sozlamasini oʻchiring. Bogʻlanishlar har bir platforma uchun alohida yaratiladi.
