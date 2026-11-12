# Audit yaxlitligi va muntazam texnik xizmat

## Audit xesh zanjiri

Audit jadvallari (maʼlumot oʻzgarishlari uchun `audit.record_changes`, ish faoliyati uchun `audit.activity_log`, xavfsizlik hodisalari uchun `audit.security_events`) faqat qoʻshishga ruxsat beradi (triggerlar bilan taʼminlanadi). Bundan tashqari, soxtalashtirishni **fosh qiladigan** mexanizm mavjud.

- Har bir qatorga qoʻshilayotganda uning mazmunidan SHA-256 (`row_hash`) hisoblanadi.
- `audit:seal` (har kuni 03:30 da rejalashtirilgan) yangi qatorlarni ID tartibida zanjirga bogʻlaydi: `chain_hash = SHA-256(previous chain_hash || row_hash)`.
- `audit:verify-chain` har bir qator uchun mazmun xeshini va zanjirni qayta hisoblaydi. Mazmuni oʻzgargan qator "row mismatch", yetishmayotgan yoki tartibi oʻzgargan qator "chain mismatch" sifatida koʻrsatiladi va buyruq 2 chiqish kodini qaytaradi.

Yodda tutish kerak boʻlgan jihatlar

- Muhrlanishdan oldin (oʻsha kunning oʻzida) oʻzgartirilgan qatorni zanjir aniqlay olmaydi. Shu sababli muhrlash har kuni bajariladi, tekshirish esa zaxiralashdan oldin va xavfsizlik tekshiruvlari paytida oʻtkaziladi.
- Tozalash (`audit:prune`) eng eski qatorlarni oʻchirganda, qolgan birinchi qatorning bogʻlanishi tekshirilmaydi (oʻchirilgan oraliq tozalash buyrugʻining audit yozuvida saqlanadi).
- `app.audit_maintenance = on` ni faqat MB administratori (sxema egasi) oʻrnatishi mumkin. Ilova rolida UPDATE yoki DELETE huquqi yoʻq.

## Saqlash muddati va tozalash

1. Saqlash muddatlarini belgilang (masalan, maʼlumot oʻzgarishlari 3 yil, ish faoliyati 5 yil, xavfsizlik hodisalari 1 yil).
2. Tozalashdan oldin zaxira nusxa oling ([Zaxiralash va tiklash](backup-restore.md)) va `audit:verify-chain` bilan zanjir buzilmaganini tasdiqlang.
3. `audit:prune --before=YYYY-MM-DD --table=record_changes --dry-run` bilan sonlarni tekshiring, soʻngra `--dry-run` siz ishga tushiring. Muhrlanmagan qatorlar hech qachon oʻchirilmaydi, shuning uchun avval `audit:seal` ni bajaring.
4. Tozalashdan keyin `audit:verify-chain` ni yana ishga tushiring.

Tozalashning oʻzi ham qayd etiladi: har bir ishga tushirish faoliyat jurnaliga (log `command`) chegara sanasi, parametrlar, ijrochi va OT foydalanuvchisini, shuningdek har bir jadval boʻyicha **oʻchirilgan qatorlar soni, oʻchirilgan ID lar oraligʻi va chegaradagi chain_hash** ni yozadi; haqiqiy oʻchirish qoʻshimcha ravishda `audit.pruned` xavfsizlik hodisasi sifatida ham qayd etiladi (**Audit > Xavfsizlik hodisalari**). Bu yozuv qatorining oʻzi ham keyingi muhrlashda zanjirga bogʻlanadi. Aynan shu chegara sababli `audit:verify-chain` qolgan birinchi qatorning bogʻlanishini tekshirmaydi; chegara xeshini tozalash yozuvi bilan solishtirib, oʻchirilgan oraliq qayd etilgan tozalashga mos kelishini tasdiqlash mumkin.

## Fayllarni tozalash

`media:prune-orphans --dry-run` egasi endi mavjud boʻlmagan media qatorlarini va qatori yoʻq kataloglarni roʻyxat qilib chiqaradi; `--dry-run` siz ularni oʻchiradi. U shaxslar fotosuratlari, ariza hujjatlari, maʼlumotnoma PDF fayllari va imtihon dalillarini qamrab oladi. Uni har oy yoki ommaviy oʻchirishdan keyin ishga tushiring.

## Holat tekshiruvi

`system:health-check` sozlamalar va kalitlarni, MB rollari va RLS ni, migratsiyalarni, ruxsatlar katalogini, Redis va navbatni (Horizon), Gotenberg ni, saqlash joyini va rejalashtiruvchi yurak urishini (har daqiqada keshga yoziladi) tekshiradi hamda biror narsa notoʻgʻri boʻlsa 2 chiqish kodini qaytaradi. Monitoringga ulash uchun `--json` dan foydalaning.
