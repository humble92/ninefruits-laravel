# Pamamahala ng mga key

- Para kanino: mga IT operator

| Key | Layunin | Kapag nawala | Rotation |
|---|---|---|---|
| `APP_KEY` | Mga session, cookie, mga secret ng two-factor authentication, pag-sign ng mga cookie ng natatandaang device | Masa-sign out ang bawat user; titigil sa paggana ang naka-enroll na two-factor authentication (mga authenticator app) | Ilagay ang dating key sa `APP_PREVIOUS_KEYS`, saka mag-rotate |
| `PII_ENCRYPTION_KEY` | Pag-encrypt ng personal na datos gaya ng mga numero ng national ID | **Hindi na mababawi ang naka-encrypt na personal na datos** | Pamamaraan sa ibaba |
| `PII_HASH_KEY` | Mga hash para sa eksaktong paghahanap ng personal na datos | Titigil sa paggana ang paghahanap (nananatili ang datos) | Bilang patakaran, hindi nire-rotate |

Mga prinsipyo sa pag-iimbak:

- Magtago ng hindi bababa sa dalawang kopya ng tatlong key sa labas ng configuration ng environment ng server, sa isang secret store (Vault, cloud secret manager, atbp.) o sa selyadong offline na imbakan.
- Huwag kailanman itago ang mga ito kasama ng mga backup file. Kahit tumagas ang isang backup ng DB, dapat manatiling hindi mabasa ang personal na datos kung wala ang mga key.

## Pamamaraan ng rotation ng key ng personal na datos

1. Bumuo ng bagong key gamit ang `php artisan pii:generate-key` (ang `PII_ENCRYPTION_KEY` lamang mula sa output ang gamitin).
2. Ilagay ang kasalukuyang key sa `PII_PREVIOUS_KEYS` (pinaghihiwalay ng kuwit kung marami) at ang bagong key sa `PII_ENCRYPTION_KEY`.
3. I-reload ang configuration: `php artisan config:cache`, `php artisan horizon:terminate`.
4. Para sa bawat model ng personal na datos, suriin ang bilang ng mga apektadong rekord gamit ang `php artisan pii:reencrypt {model} --dry-run`, saka patakbuhin ito nang walang `--force`.
5. Kapag ipinakita ng Horizon na tapos na ang batch at nag-uulat ng 0 ang `--dry-run`, alisin ang dating key sa `PII_PREVIOUS_KEYS`.
6. Itago nang selyado ang dating key sa buong panahon ng pagpapanatili ng backup (kailangan ito upang ma-restore ang mas lumang mga backup).
