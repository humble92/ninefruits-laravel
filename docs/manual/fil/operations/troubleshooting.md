# Pag-troubleshoot

- Para kanino: mga IT operator

| Sintomas | Ano ang susuriin |
|---|---|
| Inililipat sa pahina ng seguridad pagkatapos mismong mag-sign in | "Kinakailangan" ang patakaran sa two-factor authentication at lumipas na ang palugit. Malulutas kapag nag-enroll na ang user. Ang patakaran ay nasa **Mga setting > Seguridad** |
| 403 sa panel ng administrasyon | Walang balidong pagtatalaga ng tungkulin ang user na may pahintulot na `admin_panel.access`. Suriin ang **Mga user at access > Mga user > Mga pagtatalaga ng tungkulin** |
| Nawawala ang isang item sa menu | Kulang sa tungkulin ang pahintulot para sa screen na iyon (halimbawa `roles.view`) |
| Hindi dumarating ang mga email code | Mga setting ng mail (`MAIL_*`), ang mga entry na `Naipadala ang code sa email` sa mga pangyayaring pangseguridad, ang mga limitasyon sa pagpapadala (isang beses bawat minuto, lima bawat oras) |
| Nagpapakita ang isang screen ng 0 row ng datos ng ibang tao | Maaaring normal (row level security). Kumpirmahing nakatakda ang actor ng DB session sa pagtingin sa column ng user sa **Mga pagbabago ng datos** |
| `permission denied for schema public` | Sinubukan ng application ang DDL bilang `app_runtime`. Patakbuhin ang mga migration gamit lamang ang `php artisan migrate` (awtomatikong gumagamit ng `app_migrator`) |
| `Audit records are append-only` | Isang pagtatangkang i-update o burahin ang isang table ng audit. Sinadya ang pagharang na ito |
| Bumibigo ang pagbuo ng PDF | `php artisan pdf:sample`, ang katayuan ng Gotenberg, `GOTENBERG_URL` |
| Naiipit ang mga queue job | Suriin ang mga bigong job sa `/horizon`, `php artisan horizon:status` |

Pagsubaybay bawat request: gamit ang halaga ng response header na `X-Request-Id`, maaari mong hanapin nang sabay-sabay ang mga log, ang log ng aktibidad, ang mga pagbabago ng datos at ang mga pangyayaring pangseguridad.
