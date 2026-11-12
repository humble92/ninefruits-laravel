# Zaxiralash va tiklash

- Kim uchun: IT operatorlari

## Nimani zaxiralash kerak

| Obyekt | Usul | Davriylik (tavsiya etiladi) |
|---|---|---|
| PostgreSQL | `pg_dump -Fc` yoki PITR (WAL arxivlash) | Har kuni toʻliq + uzluksiz WAL |
| Fayllar ombori (`storage/app/private` yoki S3) | Snapshot yoki versiyalanadigan bucket | MB bilan bir xil |
| Muhit sozlamalari va kalitlar | Maxfiy maʼlumotlar ombori | Har bir oʻzgarishda |

MB va fayllar omborini bir xil vaqt nuqtasiga tiklash mumkin boʻlishi shart (fotosuratlar va maʼlumotnoma PDF fayllarining metamaʼlumotlari MB da saqlanadi).

## MB ni zaxiralash misoli

```bash
pg_dump -U app_migrator -d sis -Fc -f sis-$(date +%F).dump
```

`app_migrator` barcha sxemalarning (`public`, `audit`, `reporting`, `util`) egasi, shuning uchun zaxiralashni shu hisob bilan bajaring. Rollar (`app_*`) klaster obyektlari boʻlib, dumpga kirmaydi. Tiklash serverida avval rollarni [Oʻrnatish](installation.md) boʻlimining 2-qismida tavsiflanganidek yarating.

## Tiklash misoli

```bash
createdb -U postgres sis_restore
psql -U postgres -d sis_restore -f database/sql/roles/prepare_database.sql
pg_restore -U app_migrator -d sis_restore --no-owner --role=app_migrator sis-2026-10-22.dump
```

## Tiklash mashqlari

Har chorakda alohida serverga tiklang va quyidagilarni tekshiring: tizimga kirish, shaxsiy maʼlumotlarning shifrdan chiqarilishi (kalitlar mos kelishi), audit yozuvlarini koʻrish, fayllarni ochish (fotosuratlar, PDF fayllar).
