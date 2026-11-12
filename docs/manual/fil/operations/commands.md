# Sanggunian ng mga Artisan command

- Para kanino: mga IT operator
- Saklaw: bawat command ng application na nakarehistro sa repository na ito. Kapag nagdagdag ng command, dapat din itong idagdag dito, kung hindi ay babagsak ang CI (`tests/Feature/Tooling/CommandDocumentationTest.php`).

## Mga karaniwang kumbensiyon

Bawat command ay pinapatakbo bilang `php artisan {command}`. Sa development environment, `./vendor/bin/sail artisan {command}`.

| Item | Panuntunan |
|---|---|
| Rekord ng audit | Ang mga command na nagbabago ng datos ("Audit: naitatala" sa ibaba) ay nagsusulat ng command, mga argument, mga option, OS user, exit code at resulta sa log ng aktibidad (log na `command`) sa bawat pagpapatakbo. Suriin ang mga ito sa **Audit > Log ng aktibidad** sa panel ng administrasyon |
| Run ID | Bawat pagpapatakbo ay binibigyan ng ID na nagsisimula sa `cmd-` kapalit ng request ID. Ang log ng aktibidad, ang mga pagbabago ng datos na ginawa ng pagpapatakbong iyon at ang mga queue job na ipinadala nito ay may iisang ID, kaya nasasagot ng ID ang tanong na "aling command ang gumawa ng pagbabagong ito". Ang `--actor` ay halagang ibinibigay ng operator, kaya hindi ito inilalagay sa column ng user ng mga pagbabago ng datos (ang tunay na actor ay ang OS user) |
| `--actor=<email>` | Karaniwan sa lahat ng command na may audit. Itinatala ang account ng administrator na responsable. Hinihinto ang pagpapatakbo kapag hindi kilala ang email |
| `--dry-run` | Ipinapakita ang resulta nang walang binabago. Mayroon sa mga operasyong mahirap bawiin |
| `--force` | Tumatakbo nang walang prompt ng kumpirmasyon (para sa mga iskedyul at deployment script) |
| `--reason=` | Mayroon sa mga command na nangangailangan ng dahilan |
| Mga exit code | 0 tagumpay, 1 pagkabigo o kinansela, 2 may nakitang problema ang isang command na pansuri |
| Konteksto ng DB | Habang tumatakbo ang command, nakatakda ang actor ng DB session sa `system:command:{command}` at lumilitaw ito sa mga pagbabago ng datos (trigger) |

## Pag-install at demo

### `setup:install`

- Layunin: mga bagong pag-install at update. Pinapatakbo nang sunud-sunod ang mga migration, mahahalagang datos, mga default ng institusyon, profile ng pag-install at ang unang administrator. Ligtas patakbuhing muli (pinapanatili ang umiiral na datos at mga halagang binago ng mga administrator).
- Kailan: sa unang pag-install at sa bawat deployment.
- Mga option: `--institution-name=`, `--locale=` (default na wika), `--timezone=` (halimbawa `Asia/Seoul`), `--profile=*` (key ng profile ng pag-install), `--grade-scale=` (iskala ng marka na `4.5` o `4.3`. Hindi na mababago kapag naitakda na. Kung blangko, ang iskala ng unang profile), `--admin-email=`, `--admin-name=`, `--force` (tumatakbo nang walang tanong. Binabasa ang password ng administrator mula sa standard input).
- Audit: naitatala
- Mga halimbawa

  ```bash
  php artisan setup:install
  echo "$ADMIN_PASSWORD" | php artisan setup:install --force --locale=ko --timezone=Asia/Seoul \
      --institution-name="Demo University" --admin-email=admin@example.ac.kr --admin-name="Administrator"
  ```

- Pagbasa ng output: `DONE`/`FAIL` bawat hakbang. Ang `Administrator exists` ay nangangahulugang mayroon nang pagtatalaga ng administrator ng sistema, kaya nilaktawan ang paggawa.
- Kapag bumigo: kung sinasabi ng error na may nawawalang key, ilagay sa configuration ng environment ang mga halagang binuo ng `key:generate` at `pii:generate-key`. Ang pagkabigo ng migration ay karaniwang nangangahulugang hindi pa handa ang mga role ng DB (tingnan ang manwal ng pag-install).

### `setup:demo`

- Layunin: gumagawa ng datos para sa isang kathang-isip na unibersidad gamit ang mga factory. Ipinapakita ang mga nakapirming demo account bawat tungkulin (`registrar@demo.test` at iba pa).
- Kailan: mga environment para sa development, pagsasanay at demonstrasyon. **Hindi ito tatakbo sa production (`APP_ENV=production`), kahit may `--force`.**
- Mga option: `--size=tiny|small|medium|large` (humigit-kumulang 20/100/2,000/20,000 mag-aaral. Ang `tiny` ay para sa mga test at mabilisang pagsuri), `--seed=` (parehong datos ang nabubuo ng parehong halaga), `--force`.
- `--student-accounts`: gumagawa ng account para sa bawat aktibong mag-aaral (`student{student number}@demo.test`, ang demo password) at isinusulat ang mga ito sa `storage/app/private/load-accounts.csv`. Para sa mga load test ([Load testing](load-testing.md)).
- Audit: naitatala
- Halimbawa: `php artisan setup:demo --size=small --seed=42`
- Kapag bumigo: kung nawawala ang mahahalagang datos, walang tungkulin ang mga demo account at nilalaktawan ang mga ito. Patakbuhin muna ang `setup:install`.

## Sangguniang datos at mga termino

### `reference:import`

- Layunin: nag-i-import ng sangguniang datos mula sa CSV. Gumagamit ng parehong mga panuntunan sa pag-import gaya ng **Mag-import ng CSV** sa panel ng administrasyon (itinutugma ayon sa code at ina-update ang mga umiiral na row, pinapanatili ng mga blangkong cell ng salin ang umiiral na halaga, ang mga bigong row lamang ang hiwalay na itinatala).
- Mga argument: `{type}` isa sa `org_units`, `programs`, `buildings`, `rooms`, `lookup_values`, `students`, `instructors`, `courses`, `registration_holds` / `{file}` isang UTF-8 na CSV na may header row.
- Mga option: `--actor=` (kinakailangan. Sa account na ito napupunta ang rekord ng pag-import at ang abiso ng pagkumpleto), `--chunk=100` (mga row bawat queue job), `--dry-run` (pagsuri ng header at bilang ng row lamang).
- Audit: naitatala (uri, pangalan ng file, bilang ng row, bilang ng bigong row)
- Format ng column: [Kabanata 9 ng manwal ng administrator](../admin/09-csv-import.md).
- Mga exit code: kung synchronous ang queue, agad na ipinapakita ang resulta at 2 ang exit code kapag may mga bigong row. Kung asynchronous (Horizon), tinatanggap lamang ang pag-import at 0 ang exit code. Dumarating ang resulta bilang CSV ng mga bigong row sa mga abiso sa panel ng administrasyon ng account na `--actor`.
- Halimbawa: `php artisan reference:import org_units storage/app/org-units.csv --actor=admin@example.ac.kr --dry-run`

### `term:inspect`

- Layunin: ipinapakita ang terminong umiiral sa isang partikular na oras at ang mga bukas na takdang panahon ng termino (uri, termino, pamantayan ng target, oras ng pagtatapos). Gamitin ito upang suriin ang configuration ng termino at takdang panahon o upang sagutin ang mga tanong gaya ng "bakit hindi bukas ang pagpapatala".
- Mga option: `--at=` (default ay ngayon. Halimbawa `"2027-02-15 09:00"`. Kung walang time zone, binabasa ito sa time zone ng server (UTC). Maaaring magbigay ng zone gaya ng `"2027-02-15 09:00 Asia/Seoul"`).
- Audit: hindi naitatala
- Pagbasa ng output: ginagamit ng mga pasya sa petsa ang time zone ng institusyon. Hindi kailanman nabubuksan ang mga takdang panahon ng mga terminong may katayuang nakaplano o naka-archive.

## Seguridad

### `security:create-administrator`

- Layunin: gumagawa ng account na may tungkuling Administrator ng Sistema. Karaniwang tinatawag ng `setup:install`.
- Mga option: `--email=`, `--name=`, `--password-stdin` (password mula sa standard input. Kinakailangan para sa hindi interactive na pagpapatakbo).
- Audit: naitatala (ID ng ginawang user)
- Halimbawa: `php artisan security:create-administrator --email=admin@example.ac.kr --name="Administrator"`
- Kapag bumigo: ang `system_administrator role is missing` ay nangangahulugang dapat munang patakbuhin ang `setup:install` o `db:seed`. Dapat hindi bababa sa 12 character ang password.

### `security:sync-permissions`

- Layunin: inihahanay ang listahan ng pahintulot sa DB sa katalogo ng pahintulot sa code. Ginagawa ang mga bagong pahintulot; binubura ang mga pahintulot na wala sa katalogo (at inaalis sa bawat tungkulin). Laging natatanggap ng mga tungkulin ng sistema ang bawat pahintulot.
- Kailan: pagkatapos ng deployment (ginagawa ito ng `setup:install` bilang bahagi ng hakbang ng mahahalagang datos).
- Mga option: `--dry-run` (ipinapakita lamang ang listahan ng gagawin/buburahin), `--force` (nilalaktawan ang kumpirmasyon ng pagbura).
- Audit: naitatala (mga pahintulot na ginawa/binura)
- Pagbasa ng output: mga linyang `create` at `delete`. Lumilitaw ang prompt ng kumpirmasyon kapag may buburahin.
- Kapag bumigo: ang pagsagot ng "no" sa kumpirmasyon ay nagtatapos sa code 1 at walang binabago.

### `security:reset-mfa`

- Layunin: nire-reset ang two-factor authentication ng isang user (authenticator app, mga email code, mga recovery code, mga natatandaang device). Mag-e-enroll muli ang user sa susunod na pag-sign in. Gamitin ito kapag walang administrator na makapag-sign in sa panel ng administrasyon (halimbawa, nawalan ng telepono ang nag-iisang administrator). Kung hindi, gamitin ang **Mga user > I-reset ang two-factor authentication** sa panel ng administrasyon.
- Mga argument/option: `{user}` email ng user, `--reason=` (kinakailangan), `--actor=` (kinakailangan. Ang administrator na nagpasya sa pag-reset).
- Audit: naitatala (sa log ng aktibidad at sa security log, kasama ang dahilan)
- Halimbawa: `php artisan security:reset-mfa admin@example.ac.kr --reason="Phone lost, identity verified by phone call" --actor=it-lead@example.ac.kr`

### `security:unlock-user`

- Layunin: ina-unlock ang account na na-lock dahil sa paulit-ulit na maling password bago matapos ang panahon ng lock. Kapareho ng **Mga user > I-unlock ang pag-sign in** sa panel ng administrasyon.
- Mga argument/option: `{user}` email ng user, `--reason=` (kinakailangan).
- Audit: naitatala (sa security log din)
- Pagbasa ng output: ang `The account is not locked.` ay nangangahulugang walang binago.
- Tala: ang mga hangganan ng lock (mga bigong pagtatangka, tagal ng lock) ay itinatakda sa **Mga setting > Mga paraan ng pag-sign in** sa panel ng administrasyon.

## Mga tao at rekord ng mag-aaral

### `people:find-duplicates`

- Layunin: inililista ang mga taong mukhang iisang tao. Ang pamantayan ay magkaparehong numero ng national ID (search hash), o magkaparehong romanisadong pangalan at petsa ng kapanganakan. Hindi kasama ang mga taong naisanib na.
- Mga option: `--limit=100` (bilang ng mga grupong ipapakita).
- Audit: hindi naitatala (pagbasa lamang)
- Mga exit code: 2 kapag may mga kandidatong duplicate.
- Kasunod na hakbang: repasuhin ang bawat grupo sa **Mga tao** sa panel ng administrasyon at, kung iisang tao nga sila, patakbuhin ang **Isanib ang duplicate** mula sa pahina ng taong pananatilihin. Hindi na mababawi ang pagsasanib ([Kabanata 14 ng manwal ng administrator](../admin/14-merge-and-invitations.md)).

## Admisyon

### `admission:matriculate`

- Layunin: gumagawa ng mga rekord ng mag-aaral (mga bagong student number) para sa mga aplikanteng kumpirmado na ang pag-enroll sa isang siklo ng admisyon. Kapareho ng maramihang aksiyong **Gumawa ng rekord ng mag-aaral** sa **Admisyon > Mga aplikasyon** sa panel ng administrasyon. Hiwalay na pinoproseso ang bawat aplikante, kaya hindi napapahinto ng isang pagkabigo ang iba.
- Mga argument/option: `{cycle}` code ng siklo ng admisyon, `--term=` (kinakailangan. Code ng termino ng admisyon, halimbawa `2027-SP`. Ang petsa ng admisyon ay ang unang araw ng termino), `--actor=` (kinakailangan. Isang account na may pahintulot na `Gumawa ng mga rekord ng mag-aaral para sa mga natanggap na aplikante`. Ang mga aplikante lamang sa loob ng saklaw ng pahintulot ng account na iyon ang pinoproseso), `--dry-run` (listahan ng target lamang).
- Audit: naitatala (siklo, termino, bilang ng target, bilang ng nagawa, mga numero ng bigong aplikasyon). Nagsusulat din ng rekord ng aktibidad (`application.matriculated`) bawat aplikante.
- Mga exit code: 2 kapag may bumigong aplikante.
- Halimbawa: `php artisan admission:matriculate DEMO-2027-REG --term=2027-SP --actor=admissions@example.ac.kr --dry-run`
- Kapag bumigo: ang `not ready` ay nangangahulugang mayroon nang rekord ng mag-aaral o wala sa katayuang kumpirmado ang pag-enroll ang aplikante; ang `program is not in effect` ay nangangahulugang hindi umiiral ang programa sa petsa ng admisyon (suriin ang panahon ng bisa sa Sangguniang datos > Mga programa).

### `admission:expire-drafts`

- Layunin: minamarkahang **Nag-expire** ang mga draft na aplikasyon ng mga saradong siklo at ang mga draft na aplikasyong hindi ginalaw sa itinakdang panahon (**Mga setting > Admisyon > Mag-expire ang mga draft na hindi ginagalaw pagkatapos ng (araw)**, default 90 araw). Hindi hinaharangan ng mga nag-expire na aplikasyon ang bagong aplikasyon sa parehong siklo.
- Kailan: naka-iskedyul (araw-araw nang 03:10, [Mga naka-iskedyul na job](scheduled-jobs.md)).
- Mga option: `--dry-run` (bilang lamang).
- Audit: naitatala (bilang ng nag-expire)

## Mga seksiyon ng asignatura

### `section:copy`

- Layunin: sinisimulan ang pagpaplano ng bagong termino mula sa mga seksiyon ng nakaraang termino. Kapareho ng **Mga seksiyon > Kopyahin mula sa ibang termino** sa panel ng administrasyon. Kinokopya bilang draft ang mga seksiyong hindi kinansela, kasama ang mga instruktor (kung nakaempleyo pa), mga oras ng klase at silid, mga nakareserbang upuan (inilipat ang mga oras ng pagpapalaya ayon sa pagitan ng mga petsa ng simula ng termino) at ang syllabus (bilang pribadong draft). Pinapalitan ang bersiyon ng asignatura ng bersiyong umiiral sa simula ng bagong sesyon.
- Mga argument/option: `{from-term}`, `{to-term}` mga code ng termino (halimbawa `2026-FA`), `--org-unit=*` (mga seksiyon lamang ng code ng yunit ng organisasyong ito), `--dry-run` (mga resulta lamang, walang sine-save), `--actor=` (kinakailangan. Ang mga seksiyon lamang sa loob ng saklaw ng pahintulot na `Planuhin ang mga seksiyon` ng account na iyon ang kinokopya).
- Audit: naitatala (bilang ng nakopya, nilaktawan at kailangang repasuhin)
- Pagbasa ng output: ang `skipped` ay nangangahulugang mayroon nang kaparehong code ng seksiyon ang target na termino o walang umiiral na bersiyon ng asignatura; ang `note` ay nangangahulugang nakopya ang seksiyon ngunit may naiwan (nakopya nang walang silid dahil okupado na ang silid, isang retiradong instruktor, at iba pa). Itinutugma ang mga sesyon ayon sa code; kung wala, napupunta ang seksiyon sa sesyong buong termino.
- Halimbawa: `php artisan section:copy 2026-FA 2027-FA --actor=academic@example.ac.kr --dry-run`

### `schedule:check-conflicts`

- Layunin: sinusuri ang mga seksiyon ng isang termino bago ilathala ang iskedyul ng klase. Pinipigilan na ng DB ang dobleng booking ng mga silid at instruktor; hinahanap nito ang mga problemang pinapayagan ng DB: kapasidad na lampas sa kapasidad ng silid, bersiyon ng asignaturang hindi umiiral sa petsa ng simula ng sesyon, retiradong instruktor, mga nakareserbang upuan na lampas sa kapasidad, mga petsa ng pagkikita na labas sa sesyon. Sinusuri rin nito ang mga salungatan sa silid sa datos na pumasok nang walang constraint, gaya ng restore ng backup.
- Mga argument: `{term}` code ng termino.
- Audit: hindi naitatala (pagbasa lamang)
- Mga exit code: 2 kapag may mga problema. Ang mga seksiyong wala pang instruktor o silid ay ipinapakita bilang impormasyon lamang.

## Pagpapatala

### `registration:preflight`

- Layunin: sinusuri ang isang termino bago buksan ang pagpapatala. Ipinapakita ang mga takdang panahon ng pagpapatala at add/drop, ang set ng panuntunan, ang bilang ng mga nakalathalang seksiyon, mga seksiyong may kapasidad na 0, mga nakareserbang upuan na lampas sa kapasidad, mga bersiyon ng asignaturang hindi umiiral sa petsa ng simula ng sesyon, ang bilang ng mga aktibong mag-aaral na hindi saklaw ng limitasyon ng units, at ang bilang ng mga mag-aaral na may hold.
- Mga argument: `{term}` code ng termino (halimbawa `2027-SP`).
- Audit: hindi naitatala (pagbasa lamang)
- Mga exit code: 2 kapag may mga problema. Ang nawawalang set ng panuntunan ay ipinapakita bilang impormasyon, hindi bilang problema (nalalapat ang mga default ng panuntunan).
- Halimbawa: `php artisan registration:preflight 2027-SP`

### `registration:simulate`

- Layunin: ipinapakita, panuntunan bawat panuntunan, kung ano ang mangyayari kung magpapatala ang mag-aaral sa seksiyon ngayon (o sa isang partikular na oras). Walang sine-save at walang naitatalang pagtatangka. Gamitin ito para sa mga tanong na "bakit hindi ako makapagpatala?".
- Mga argument: `{student-number}` student number, `{section}` `code ng asignatura-code ng seksiyon` (halimbawa `CSE2010-01`).
- Mga option: `--term=` (code ng termino. Ang default ay ang pinakabagong terminong may seksiyong iyon), `--at=` (oras ng pagsusuri. Halimbawa `"2027-02-10 10:00 Asia/Seoul"`).
- Audit: hindi naitatala (pagbasa lamang)
- Pagbasa ng output: bawat panuntunan, pasado/bigo, harang/babala at ang mensahe. Ang panuntunang saklaw ng override ay lumalabas na bigo ngunit hindi humaharang. Hiwalay na ipinapakita ang kapasidad sa ibaba ng table ng mga panuntunan.
- Mga exit code: 2 kapag tatanggihan ang pagpapatala.

### `waitlist:promote`

- Layunin: ipinapatala nang sunud-sunod ang mga mag-aaral na nasa waitlist sa mga seksiyong may bakanteng upuan. Sinusuri muli ang bawat panuntunan sa promosyon; inaalis sa waitlist ang mga entry na bumigo sa isang panuntunan (itinatala ang dahilan). Nag-e-expire ang mga waitlist ng mga seksiyong sarado na ang parehong takdang panahon ng pagpapatala at add/drop. Natatanggap ng mga mag-aaral ang resulta sa email.
- Mga argument/option: `{term?}` code ng termino (default: bawat terminong may mga entry sa waitlist), `--actor=`.
- Audit: naitatala (bilang ng mga seksiyon, na-promote, inalis, nag-expire)
- Kailan: kapag may pag-drop, pag-withdraw o pagpapalaya ng nakareserbang upuan, agad na pinoproseso ng isang queue job ang apektadong seksiyon. Ang command na ito ang naka-iskedyul bawat oras na panangga na humahawak sa anumang nakaligtaan.
- Halimbawa: `php artisan waitlist:promote 2027-SP --actor=registrar@example.ac.kr`

### `registration:release-reserved-seats`

- Layunin: minamarkahang naproseso ang mga nakareserbang upuan na lumipas na ang oras ng pagpapalaya (halimbawa, mga upuang nakareserba para sa mga major) at inilalagay sa queue ang job ng promosyon mula sa waitlist para sa mga seksiyong iyon. Nagiging pangkalahatang upuan sa kalkulasyon ang mga nakareserbang upuan sa sandaling lumipas ang oras ng pagpapalaya, kaya ipinapasa lamang ng command na ito ang mga upuan sa waitlist.
- Audit: hindi naitatala (tumatakbo bawat 5 minuto. Nasa mga rekord ng pagpapatala ang mga resulta ng promosyon)
- Kailan: naka-iskedyul (bawat 5 minuto).

## Mga marka

### `term:close`

- Layunin: isinasara ang isang termino. Dapat pinal ang bawat marka sa termino. Para sa bawat mag-aaral, kinakalkula nito ang rekord ng termino (units, GPA ng termino at kabuuang GPA), tinutukoy ang academic standing (good standing, academic probation, pagsusuri para sa pagkatanggal) at ang pagiging kwalipikado sa karangalan (queue batch), saka niraranggo ang karangalan bawat programa, isinasara ang mga rekord ng termino bilang opisyal na rekord at itinatakda ang termino sa **Sarado na ang marka**. Kapareho ng **Mga marka > Mga rekord ng termino > Isara ang termino** sa panel ng administrasyon.
- Mga argument/option: `{term}` code ng termino, `--dry-run` (sinusuri lamang kung maisasara ang termino), `--reason=` (dahilang iniingatan sa rekord), `--actor=`.
- Audit: naitatala (bilang ng mag-aaral, bilang ng mga seksiyong may hindi pa pinal na marka). Kapag natapos ang pagsasara, hawak ng rekord ng audit na `term.closed` ang bilang ng academic probation, pagsusuri para sa pagkatanggal at karangalan.
- Mga exit code: 2 kapag may mga seksiyong may hindi pa pinal na marka (ipinapakita ang bilang ng hindi pa pinal bawat seksiyon). 1 kapag wala sa katayuang nakalathala ang termino.
- Kailan: pagkatapos makumpleto ang pagpasok ng marka at paggawang pinal. Subaybayan ang progreso ng batch sa Horizon. Kung walang queue worker, hindi nagbabago ang katayuan ng termino.
- Mga halimbawa: `php artisan term:close 2026-FA --dry-run`, `php artisan term:close 2026-FA --reason="Fall 2026 grades closed" --actor=registrar@example.ac.kr`

### `grades:recalculate`

- Layunin: kinakalkula muli ang mga rekord ng termino (units, GPA ng termino at kabuuang GPA) mula sa mga pinal na marka. Gamitin ito upang ilapat sa mga nakaraang termino ang binagong patakaran sa pagmamarka (bilang ng decimal place ng GPA, paghawak sa retake). Pinapanatili ng academic standing at karangalan ang mga halagang napagpasyahan sa pagsasara ng termino. Awtomatikong kinakalkula muli ng isang naaprubahang pagwawasto ng marka ang mag-aaral na iyon, kaya hindi kailangan ang command na ito para roon.
- Mga option: `--term=` (mga mag-aaral na may marka sa terminong iyon, mula sa terminong iyon pasulong), `--student-number=` (isang mag-aaral), `--reason=` (kinakailangan), `--actor=`.
- Audit: naitatala (bilang ng mag-aaral, dahilan)
- Halimbawa: `php artisan grades:recalculate --term=2026-SP --reason="GPA changed to three decimal places"`

### `grades:remind-missing`

- Layunin: nagpapadala ng email sa mga instruktor ng mga seksiyong hindi pa naisusumite ang marka (hindi pa naipapasok o naka-save bilang draft). Pinagsasama-sama ang mga seksiyon bawat instruktor sa iisang mensahe bawat isa.
- Mga argument/option: `{term}` code ng termino, `--dry-run` (ipinapakita ang mga target nang hindi nagpapadala), `--actor=`.
- Audit: naitatala (mga seksiyon, mga instruktor, mga naipadalang mensahe)
- Kailan: ilang araw bago magsara ang takdang panahon ng pagpasok ng marka. Idagdag sa iskedyul kung kailangan.

## Ebalwasyon ng asignatura

### `evaluation:remind`

- Layunin: nagpapadala ng email sa mga mag-aaral na may mga asignaturang hindi pa naeebalwa habang bukas ang takdang panahon ng ebalwasyon ng asignatura. Pinagsasama-sama ang natitirang mga asignatura bawat mag-aaral sa iisang mensahe.
- Mga argument/option: `{term}` code ng termino, `--dry-run` (bilang lamang ng mga target na mag-aaral, walang ipinapadala), `--actor=`.
- Audit: naitatala (mga target na mag-aaral, mga naipadalang mensahe)
- Kailan: isa o dalawang beses sa takdang panahon ng ebalwasyon. Idagdag sa iskedyul kung kailangan.

### `evaluation:publish-results`

- Layunin: inilalathala sa mga instruktor ang mga resulta ng ebalwasyon ng asignatura ng termino. Kapareho ng **Ebalwasyon ng asignatura > Mga ebalwasyon ng termino > Ilathala ang mga resulta** sa panel ng administrasyon. Kahit nailathala na, makikita lamang ang mga resulta para sa mga seksiyong umabot sa pinakamababang bilang ng tugon at antas ng pagtugon.
- Mga argument/option: `{term}` code ng termino, `--actor=`.
- Audit: naitatala (bilang ng tugon, kung nailathala na)
- Kailan: pagkatapos makumpleto ang takdang panahon ng ebalwasyon at ang pagproseso ng marka. Hindi binabago ang terminong nailathala na.

## Mga sertipiko

### `certificate:verify`

- Layunin: sinusuri ang verification code ng sertipiko gamit ang parehong pamantayan ng pampublikong pahina ng beripikasyon. Ipinapakita ang uri, numero ng isyu, petsa ng isyu at ang naka-mask na may-ari.
- Mga argument/option: `{code}` verification code.
- Audit: wala (pagbasa lamang)
- Mga exit code: 0 kapag balido, 2 para sa hindi kilalang code, binawing sertipiko o lumipas na ang bisa.

### `certificate:render`

- Layunin: binubuo muli ang PDF ng isang inisyung sertipiko gamit ang kasalukuyang datos at template, at pinapalitan ang naka-archive na file. Kapareho ng **Mga inisyung sertipiko > Buuin muli** sa panel ng administrasyon. Nananatili ang numero ng isyu at verification code; nagbabago ang checksum.
- Mga argument/option: `{issue}` numero ng isyu, `--reason=` (kinakailangan), `--actor=`.
- Audit: naitatala (numero ng isyu, bagong checksum)
- Kailan: upang muling buuin ang isang partikular na sertipiko pagkatapos ayusin ang template o ang baybay ng pangalan.

## Mga pagsusulit

### `assessment:import-results`

- Layunin: ipinapasok ang mga resulta ng isang sesyon ng pagsusulit mula sa CSV. Parehong mga panuntunan gaya ng **Mga sesyon ng pagsusulit > Mag-import ng mga resulta (CSV)** sa panel ng administrasyon (mga nakarehistrong mag-aaral lamang, pasado/bagsak ayon sa iskor, pinapalitan ang mga resulta kapag muling ipinasok bago ilathala).
- Mga argument/option: `{type}` code ng uri ng pagsusulit / `{held_date}` petsa ng pagsusulit `YYYY-MM-DD` / `{file}` isang UTF-8 na CSV na may header row, `--actor=` (kinakailangan), `--dry-run` (validation ng row lamang, walang naitatala).
- Format ng column: [Kabanata 24 ng manwal ng administrator](../admin/24-assessments.md).
- Audit: naitatala (sesyon, pangalan ng file, bilang ng row, bilang ng naipasok/absent/error)
- Mga exit code: 2 kapag may mga row na may error (naitatala ang ibang mga row).
- Halimbawa: `php artisan assessment:import-results ENG_COMP 2026-05-15 storage/app/eng.csv --actor=registrar@example.ac.kr --dry-run`

### `assessment:publish-results`

- Layunin: inilalathala sa mga mag-aaral ang mga resulta ng isang sesyon. Kapareho ng **Mga sesyon ng pagsusulit > Ilathala ang mga resulta** sa panel ng administrasyon.
- Mga argument/option: `{type}` code ng uri ng pagsusulit / `{held_date}` petsa ng pagsusulit, `--actor=` (kinakailangan).
- Audit: naitatala (sesyon, bilang ng resulta)
- Kailan: pagkatapos makumpleto ang pagpasok ng resulta. Bumibigo ito para sa sesyong nailathala na.

## Pagdalo

### `attendance:generate-sessions`

- Layunin: gumagawa ng mga pagkikita ng klase para sa bawat seksiyon ng isang termino mula sa mga oras ng klase nito (araw ng linggo, oras, period). Kapareho ng **Bumuo ng mga pagkikita** sa portal ng instruktor at sa panel ng administrasyon. Pinapanatili ang mga umiiral na pagkikita at ang pagdalo sa mga ito (ligtas patakbuhing muli).
- Mga argument/option: `{term}` code ng termino, `--section=` (code ng asignatura-code ng seksiyon, halimbawa `CSE2010-01`, isa lamang), `--dry-run` (bilang lamang ng mga target na seksiyon), `--actor=`.
- Audit: naitatala (termino, mga seksiyon, mga pagkikitang nagawa)
- Kailan: bago magsimula ang termino, pagkatapos makumpirma ang mga oras ng klase. Patakbuhing muli kapag nagbago ang mga oras ng klase.

## Pagtatapos

### `degree-audit:run`

- Layunin: sine-save ang degree audit ng mga kinakailangan sa pagtatapos para sa bawat aktibong mag-aaral. Kapareho ng **Mga degree audit > Patakbuhin ang mga audit** sa panel ng administrasyon. Ang pinakabagong naka-save na audit ang batayan ng pagproseso ng pagtatapos.
- Mga option: `--term=` (code ng terminong kinabibilangan ng audit), `--student-number=` (isang mag-aaral), `--min-year-level=` (ang year level na ito at pataas lamang), `--actor=`.
- Audit: naitatala (bilang ng mag-aaral, bilang ng nakatugon)
- Kailan: bago ang pagsusuri para sa pagtatapos (katapusan ng termino), at pagkatapos baguhin ang mga set ng kinakailangan o mga katumbas na asignatura.

## Integrasyon ng LMS

### `lms:sync`

- Layunin: itinutulak sa platform ang mga nakalathalang seksiyon (paggawa at pag-link ng course, mga instruktor, mga naka-enroll na mag-aaral). Ginagawa para sa buong termino ang ginagawa ng **Mga link ng asignatura > I-sync muli** sa panel ng administrasyon. Tinitiyak lamang muli ang mga seksiyong naka-link na (idempotent).
- Mga argument/option: `{platform}` code ng platform, `--term=` (code ng termino, default ay bawat terminong hindi pa natatapos), `--section=` (isang code ng asignatura-code ng seksiyon), `--dry-run` (itinatala lamang bilang "skipped" ang ipapadala sana), `--actor=`.
- Audit: naitatala (platform, mga seksiyon, mga miyembro, mga pagkabigo)
- Mga exit code: 2 kapag may bumigong seksiyon.

### `lms:reconcile`

- Layunin: inihahambing ang mga roster ng course sa platform sa mga listahan ng pagpapatala sa student information system, idinadagdag ang mga nawawalang miyembro at inaalis ang mga sobra. Iniingatan ang mga pagkakaiba sa sync log (`reconcile`).
- Mga argument/option: `{platform?}` (bawat aktibong platform kung wala), `--term=`, `--dry-run` (itinatala lamang ang mga pagkakaiba), `--actor=`.
- Audit: naitatala (mga seksiyon bawat platform, bilang ng idinagdag/inalis/bumigo)
- Kailan: naka-iskedyul araw-araw nang 02:30. Patakbuhin nang mano-mano pagkatapos ng outage ng platform.

## Mga pagsusuri sa operasyon at paglilinis

### `system:health-check`

- Layunin: sinusuri ang configuration at mga key (APP_KEY, mga PII key, APP_DEBUG), ang koneksiyon at mga role ng DB (runtime, migrator, reporting), RLS sa mga table ng personal na datos, mga nakabinbing migration, sync ng katalogo ng pahintulot, Redis, ang queue (Horizon), Gotenberg, pagsusulat sa media storage at ang heartbeat ng scheduler.
- Mga option: `--json` (output para sa monitoring).
- Audit: wala (pagbasa lamang)
- Mga exit code: 2 kapag may bumigong pagsuri.
- Kailan: kaagad pagkatapos ng deployment, kapag may hinihinalang outage, pana-panahon mula sa monitoring.

### `audit:seal`

- Layunin: iniuugnay sa hash chain ang mga bagong row ng mga table ng audit ([Integridad ng audit](audit-and-maintenance.md)).
- Mga option: `--table=` (isa sa record_changes, activity_log, security_events; maaaring ulitin), `--actor=`.
- Audit: naitatala (mga row na na-seal bawat table)
- Kailan: naka-iskedyul araw-araw nang 03:30.

### `audit:verify-chain`

- Layunin: kinukuwenta muli ang mga hash ng nilalaman at ang chain ng mga row ng audit upang matukoy ang pakikialam, pagbura at pagbabago ng pagkakasunod-sunod.
- Mga option: `--table=`.
- Audit: wala (pagbasa lamang)
- Mga exit code: 2 kapag may hindi tugma.
- Kailan: bago ang mga backup, sa mga pagsusuring pangseguridad, pagkatapos ng pruning.

### `audit:prune`

- Layunin: binubura ang mga **na-seal** na row ng audit na lampas na sa panahon ng pagpapanatili. Tumatakbo sa koneksiyon ng may-ari ng schema.
- Mga option: `--before=YYYY-MM-DD` (kinakailangan), `--table=`, `--dry-run` (bilang lamang), `--actor=`.
- Audit: naitatala (petsang hangganan, mga row na binura bawat table)
- Kailan: ayon sa patakaran sa pagpapanatili, pagkatapos ng backup.

### `media:prune-orphans`

- Layunin: binubura ang mga media row na wala nang may-ari at ang mga directory na walang row (mga larawan ng tao, mga dokumento ng aplikasyon, mga PDF ng sertipiko, ebidensiya ng pagsusulit).
- Mga option: `--dry-run` (listahan lamang), `--actor=`.
- Audit: naitatala (mga row, mga directory)
- Kailan: buwan-buwan o pagkatapos ng maramihang pagbura.

## Mga encryption key ng personal na datos

### `pii:generate-key`

- Layunin: ipinapakita ang mga bagong key para sa `PII_ENCRYPTION_KEY` at `PII_HASH_KEY`. Walang sine-save.
- Audit: hindi naitatala
- Babala: ang pagbabago ng `PII_HASH_KEY` ay nagpapawalang-bisa sa bawat search hash. Ang pamamaraan ng rotation ng key ay nasa [security-keys.md](security-keys.md).

### `pii:reencrypt`

- Layunin: pagkatapos ng rotation ng key, ine-encrypt muli gamit ang kasalukuyang key ang personal na datos na naka-encrypt gamit ang dating key. Tumatakbo bilang queue batch.
- Mga argument: ang model class (sa kasalukuyan ay `App\\Models\\Person\\Person`).
- Mga option: `--chunk=500`, `--dry-run` (binibilang lamang ang mga rekord na gumagamit ng dating key), `--force`.
- Audit: naitatala (batch ID, bilang ng job / bilang sa preview)
- Beripikasyon: subaybayan ang progreso ng batch sa Horizon (`/horizon`). Kapag natapos ito at nag-uulat ng 0 ang `--dry-run`, alisin ang dating key sa `PII_PREVIOUS_KEYS`.

## Lokalisasyon

### `i18n:missing`

- Layunin: ipinapakita, bawat wika, ang mga key na nawawala kumpara sa batayang wika (en) at ang mga key na hindi na ginagamit.
- Mga argument/option: `{locale?}` (isang wika lamang), `--strict` (exit code 2 kapag may nawawalang key. Ginagamit sa CI).
- Audit: hindi naitatala

### `i18n:export`

- Layunin: gumagawa ng JSON file na ibibigay sa mga tagasalin. Format: `{"key": {"source": "English text", "translation": "translation"}}`.
- Mga argument/option: `{locale} {path}`, `--missing` (mga nawawalang key lamang).
- Audit: hindi naitatala
- Halimbawa: `php artisan i18n:export ko storage/app/ko.json --missing`

### `i18n:import`

- Layunin: inilalapat sa `lang/{locale}` ang JSON na pinunan ng mga tagasalin. Ang mga key lamang na umiiral sa batayang wika ang ini-import; binabalewala ang mga blangkong salin.
- Mga argument/option: `{locale} {path}`, `--dry-run` (ipinapakita lamang ang listahan ng mga key na isusulat).
- Audit: naitatala
- Babala: pinamamahalaan sa repository ang mga file ng salin. I-commit ang mga pagbabago pagkatapos mag-import.

## Mga pagsusuri

### `naming:check`

- Layunin: sinusuri ang mga pangalan ng table at column sa mga migration laban sa [mga kumbensiyon sa pagpapangalan](../../../development/naming.md).
- Mga option: `--path=*` (mga directory na susuriin, default `database/migrations`).
- Audit: hindi naitatala
- Mga exit code: 2 kapag may mga problema.

### `pdf:sample`

- Layunin: nire-render sa PDF ang isang table na pinaghahalo ang Hangul, CJK, Lao, Thai, Devanagari, Cyrillic at Latin na sulat upang suriin ang mga font at ang koneksiyon sa PDF service (Gotenberg).
- Mga argument: `{path=pdf-sample.pdf}` (relatibo sa local disk na `storage/app/private`).
- Audit: hindi naitatala
- Kapag bumigo: para sa error sa koneksiyon, suriin ang `GOTENBERG_URL` at ang katayuan ng Gotenberg container.
