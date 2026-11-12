# 4. Audit

**Audit** menyusidagi uchta ekran faqat koʻrish uchun; ularni hech kim tahrirlay yoki oʻchira olmaydi (`Audit yozuvlarini koʻrish` va `Xavfsizlik hodisalarini koʻrish` ruxsatlari kerak).

| Ekran | Nimani koʻrsatadi | Misollar |
|---|---|---|
| Faoliyat jurnali | Kim nima qilgani va **nima uchun**. Sabab va tafsilotlar | Rol tayinlash (sabab: semestr davomida oʻrnini bosish), ruxsatni oʻzgartirish (qoʻshilganlar/olib tashlanganlar roʻyxati), sozlamani oʻzgartirish (oldin/keyin), buyruqni ishga tushirish |
| Maʼlumot oʻzgarishlari | Jadval qatorlarining oʻzgarishdan oldingi va keyingi qiymatlari. Ilovani chetlab oʻtgan bevosita oʻzgarishlar ham qayd etiladi | `users` UPDATE, oʻzgargan ustun `locale` |
| Xavfsizlik hodisalari | Tizimga kirishlar, muvaffaqiyatsiz kirishlar, ikki bosqichli autentifikatsiya, ruxsatlarga oid hodisalar, parol va e-pochta oʻzgarishlari, audit yozuvlarini tozalash (`audit:prune`) | Kirish muvaffaqiyatsiz (kirish nomi, IP) |

## Qanday oʻqish kerak

- **Foydalanuvchi** ustuni: ekranda bajarilgan amallar uchun foydalanuvchi ismi yoki `user:raqam`, avtomatik vazifalar uchun `system:job:...`, buyruqlar uchun `system:command:...` koʻrsatiladi.
- Parollar, ikki bosqichli autentifikatsiya kalitlari va shu kabi qiymatlar `[redacted]` bilan yashiriladi.
- Bir xil **Soʻrov ID** ga ega yozuvlar bitta ekran soʻrovi natijasida yaratilgan. Ekranlar boʻylab bir xil soʻrov ID ni qidirish bitta amalning toʻliq izini koʻrsatadi.

## Koʻp uchraydigan soʻrovlar

| Nimani bilmoqchisiz | Qanday qilib |
|---|---|
| Foydalanuvchi ruxsatlari qachon oʻzgargan | Faoliyat jurnalida `security` jurnali, `role.assigned`, `role.assignment_revoked` hodisalari |
| Muvaffaqiyatsiz kirishlari koʻp boʻlgan hisob | Xavfsizlik hodisalarida `Kirish muvaffaqiyatsiz` hodisasi boʻyicha filtrlang, kirish nomi ustuniga qarang |
| Xavfsizlik siyosatini kim oʻzgartirgan | Faoliyat jurnalidagi `settings.updated` hodisasi |
| Muayyan qator qanday oʻzgargan | Maʼlumot oʻzgarishlarida jadval boʻyicha filtrlang va yozuv ID si boʻyicha qidiring |
