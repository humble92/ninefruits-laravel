# Kalitlarni boshqarish

- Kim uchun: IT operatorlari

| Kalit | Vazifasi | Yoʻqolsa | Almashtirish |
|---|---|---|---|
| `APP_KEY` | Sessiyalar, cookie fayllari, ikki bosqichli autentifikatsiya sirlari, eslab qolingan qurilma cookie fayllarini imzolash | Barcha foydalanuvchilar tizimdan chiqariladi; ulangan ikki bosqichli autentifikatsiya (autentifikator ilovalari) ishlamay qoladi | Oldingi kalitni `APP_PREVIOUS_KEYS` ga qoʻying, soʻngra almashtiring |
| `PII_ENCRYPTION_KEY` | JShShIR kabi shaxsiy maʼlumotlarni shifrlash | **Shifrlangan shaxsiy maʼlumotlarni tiklab boʻlmaydi** | Quyidagi tartib |
| `PII_HASH_KEY` | Shaxsiy maʼlumotlar boʻyicha aniq moslik qidiruvi uchun xeshlar | Qidiruv ishlamay qoladi (maʼlumotlar saqlanib qoladi) | Odatda almashtirilmaydi |

Saqlash tamoyillari

- Uchala kalitning kamida ikki nusxasini serverning muhit sozlamalaridan tashqarida, maxfiy maʼlumotlar omborida (Vault, bulutli maxfiy maʼlumotlar menejeri va h.k.) yoki muhrlangan oflayn omborda saqlang.
- Ularni hech qachon zaxira fayllar bilan birga saqlamang. MB zaxirasi sizib chiqqan taqdirda ham shaxsiy maʼlumotlar kalitlarsiz oʻqib boʻlmaydigan boʻlib qolishi kerak.

## Shaxsiy maʼlumotlar kalitini almashtirish tartibi

1. `php artisan pii:generate-key` bilan yangi kalit yarating (natijadan faqat `PII_ENCRYPTION_KEY` dan foydalaning).
2. Joriy kalitni `PII_PREVIOUS_KEYS` ga (bir nechta boʻlsa vergul bilan ajratib), yangi kalitni esa `PII_ENCRYPTION_KEY` ga qoʻying.
3. Sozlamalarni qayta yuklang: `php artisan config:cache`, `php artisan horizon:terminate`.
4. Har bir shaxsiy maʼlumotlar modeli uchun `php artisan pii:reencrypt {model} --dry-run` bilan taʼsirlanadigan yozuvlar sonini tekshiring, soʻngra uni `--force` siz ishga tushiring.
5. Horizon paketni tugallangan deb koʻrsatgach va `--dry-run` 0 ni koʻrsatgach, oldingi kalitni `PII_PREVIOUS_KEYS` dan olib tashlang.
6. Oldingi kalitni zaxiralarni saqlash muddati davomida muhrlangan holda saqlang (eski zaxiralarni tiklash uchun kerak boʻladi).
