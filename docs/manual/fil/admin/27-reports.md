# 27. Mga ulat at reporting view

Menu: **Mga ulat > Mga ulat** (pahintulot na `Tingnan at i-download ang mga karaniwang ulat`). Mga default ng tungkulin: Registrar, Academic Affairs, Katuwang ng Departamento (sa loob ng saklaw).

## Mga karaniwang ulat

Pumili ng ulat at ng mga filter nito: hanggang 500 row ang ipinapakita sa screen, at kinukuha ng **I-download ang CSV** ang lahat (UTF-8 na may BOM, direktang nabubuksan sa Excel). Itinatala ang mga download sa audit event na `reports.exported`. Ang mga value na nagsisimula sa `=`, `+`, `-` o `@` ay ine-export na may nangungunang single quote (`'`) para hindi patakbuhin ng Excel bilang formula.

| Ulat | Mga filter | Nilalaman |
|---|---|---|
| Listahan ng mag-aaral | Yunit ng organisasyon (kasama ang mga yunit sa ilalim nito), katayuan, antas ng taon | Student number, pangalan, programa, yunit ng organisasyon, antas ng taon, katayuan, petsa ng admisyon, petsa ng pagtatapos, numero ng degree |
| Bilang ng mag-aaral ayon sa yunit, programa, taon at katayuan | Yunit ng organisasyon, katayuan | Bilang ng mga mag-aaral |
| Buod ng marka ng termino | Termino, academic standing | Units at GPA ng termino at kabuuan, bilang ng academic warning, ranggo sa karangalan ([kabanata 21](21-grades.md)) |
| Mga kandidato sa pagtatapos | Natugunan o hindi | Pinakahuling degree audit bawat mag-aaral at programa ([kabanata 26](26-graduation.md)) |
| Mga pagbabago ng katayuan | Termino, panahon | Uri ng pagbabago, katayuan bago at pagkatapos, petsa ng bisa, katayuan ng kahilingan |
| Enrollment bawat seksiyon | Termino, yunit ng organisasyon | Seksiyon, mga instruktor, kapasidad, naka-enroll, naghihintay |
| Mga mag-aaral na hindi nagpatala sa isang termino | Termino, yunit ng organisasyon | Mga naka-enroll na mag-aaral na walang pagpapatala sa terminong iyon |

Ang nakikita ay sumusunod sa saklaw ng pahintulot ng tumitingin (saklaw ng pagtingin sa rekord ng mag-aaral at iba pa). Ang mga ulat sa bayarin at matrikula ng lumang sistema (YUSTIC) (hindi bayad na matrikula at iba pa) ay labas sa saklaw at wala.

## Mga reporting view

Binabasa ng mga ulat ang mga view ng schema na `reporting` sa database. Kinu-query ng mga BI tool at ng mga user ng SQL ang parehong mga view gamit ang read-only na tungkuling `app_reporting`.

| View | Nilalaman |
|---|---|
| `reporting.students` | Rekord ng mag-aaral + pangalan ng tao + mga pangalan ng programa at yunit ng organisasyon |
| `reporting.headcount` | Bilang ng mag-aaral ayon sa yunit ng organisasyon, programa, antas ng taon at katayuan |
| `reporting.term_records` | Buod ng marka bawat termino |
| `reporting.course_grades` | Mga marka bawat asignatura (pinal man o hindi, marka, pumasa, retake) |
| `reporting.section_enrollments` | Naka-enroll at naghihintay bawat seksiyon, mga instruktor |
| `reporting.section_meetings` | Mga oras ng klase at silid |
| `reporting.status_changes` | Mga pagbabago ng katayuan |
| `reporting.degree_audits` | Pinakahuling degree audit bawat mag-aaral at papel sa programa |

- Sumusunod sa locale ng session ang mga isinalin na column (`program_name` at iba pa): `SET app.locale = 'ko';`. Makukuha rin ang mga raw na jsonb column (`program_name_i18n` at iba pa).
- `security_invoker` ang mga view, kaya ipinatutupad nang walang pagbabago ang row level security ng tumitingin. Kapag binasa gamit ang account ng mag-aaral, ang mga row lamang ng mag-aaral na iyon ang ibinabalik.
- Nasa `database/sql/views/reporting.sql` ang mga depinisyon ng view; matapos ang pagbabago, muling ipinatutupad ang mga ito mula sa isang migration.

Hindi ginagamit ang mga lumang view na `AV_*` (mga 90) at hindi inilipat.
