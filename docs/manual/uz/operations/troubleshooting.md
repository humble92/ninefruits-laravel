# Nosozliklarni bartaraf etish

- Kim uchun: IT operatorlari

| Alomat | Nimani tekshirish kerak |
|---|---|
| Tizimga kirgandan soʻng darhol xavfsizlik sahifasiga yoʻnaltiriladi | Ikki bosqichli autentifikatsiya siyosati "majburiy" va imtiyozli muddat tugagan. Foydalanuvchi uni ulagach hal boʻladi. Siyosat **Sozlamalar > Xavfsizlik** boʻlimida |
| Boshqaruv panelida 403 | Foydalanuvchida `admin_panel.access` ruxsatiga ega amaldagi rol tayinlash yoʻq. **Foydalanuvchilar va kirish > Foydalanuvchilar > Rol tayinlashlar** ni tekshiring |
| Menyu bandi yoʻq | Rolda oʻsha ekran uchun ruxsat yoʻq (masalan, `roles.view`) |
| E-pochta kodlari kelmaydi | Pochta sozlamalari (`MAIL_*`), xavfsizlik hodisalari orasidagi `E-pochta kodi yuborildi` yozuvlari, yuborish cheklovlari (daqiqasiga bir marta, soatiga besh marta) |
| Ekranda boshqa shaxslar maʼlumotlaridan 0 ta qator koʻrsatiladi | Normal holat boʻlishi mumkin (qatorlar darajasidagi xavfsizlik). **Maʼlumot oʻzgarishlari** dagi foydalanuvchi ustuniga qarab MB sessiyasi ijrochisi oʻrnatilganini tasdiqlang |
| `permission denied for schema public` | Ilova `app_runtime` sifatida DDL bajarishga urindi. Migratsiyalarni faqat `php artisan migrate` bilan bajaring (u avtomatik ravishda `app_migrator` dan foydalanadi) |
| `Audit records are append-only` | Audit jadvalini yangilash yoki oʻchirishga urinish. Bu blokirovka ataylab qilingan |
| PDF yaratish muvaffaqiyatsiz | `php artisan pdf:sample`, Gotenberg holati, `GOTENBERG_URL` |
| Navbat vazifalari tiqilib qolgan | `/horizon` da muvaffaqiyatsiz vazifalarni tekshiring, `php artisan horizon:status` |

Soʻrovlar boʻyicha kuzatuv: `X-Request-Id` javob sarlavhasi qiymati yordamida jurnallar, faoliyat jurnali, maʼlumot oʻzgarishlari va xavfsizlik hodisalarini birgalikda qidirish mumkin.
