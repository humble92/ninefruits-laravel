# 23. Mga sertipiko

Menu: **Mga sertipiko > Mga inisyung sertipiko**, **Mga uri ng sertipiko**, **Mga setting > Mga setting ng sertipiko**. Iniisyu ang mga sertipiko gamit ang button na **Mag-isyu ng sertipiko** sa screen ng rekord ng mag-aaral (**Mga mag-aaral at kawani > Mga rekord ng mag-aaral > Tingnan**). Dina-download ng mga mag-aaral ang PDF ng sarili nilang sertipiko sa portal sa ilalim ng **Mga sertipiko**. Maaaring i-verify ng sinuman ang sertipiko sa pahinang `/verify/{code}`.

| Pahintulot | Ano ang pinapayagan nito |
|---|---|
| `Mag-isyu, muling bumuo at bumawi ng mga sertipiko` | Mag-isyu, buuin muli, bawiin, tingnan ang mga inisyung sertipiko (sa loob ng saklaw ng pagtingin sa rekord ng mag-aaral) |
| `Pamahalaan ang mga uri, template at setting ng sertipiko` | Mga uri at ang kanilang template bawat wika, pattern ng serial number, mga lumagda |

Mga default ng tungkulin: nag-iisyu ang Tagapangasiwa ng Sertipiko, nasa Registrar ang dalawa, pinamamahalaan ng Academic Affairs ang mga uri at template.

## Mga pagkakaiba sa lumang sistema

- **Talaan ng mga inisyung sertipiko.** Direktang nagpi-print mula sa screen ang lumang sistema (YUSTIC) (`aca3/cti_*`), at umiiral lamang ang talaan nito (`SRCERT`) para bumuo ng mga numero, kaya hindi nito ipinapakita kung sino ang nag-isyu ng sertipiko o kung binawi ito. Itinatala ng bagong sistema ang serial number, verification code, kawani, layunin, bilang ng kopya, PDF file at checksum sa bawat pag-isyu, at pinananatili ng binawing sertipiko ang row nito.
- **Beripikasyon.** Wala nito ang luma. May verification code at QR code ang bawat sertipiko, at ipinapakita ng isang pampublikong pahina na inisyu ito, ang uri nito, petsa ng pag-isyu at ang naka-mask na may-ari (una at huling titik ng apelyido at pangalan, unang 4 na digit ng student number).
- **Mga template.** May hiwalay na PDF script ang luma bawat uri at wika. Sa bagong sistema, ine-edit sa screen ang template bawat wika (pamagat, nilalaman, footer, lumagda) para sa bawat uri.
- **Mga paghaharang.** Nag-iisyu ang luma ng mga sertipiko anuman ang listahan ng pagtanggi sa pagpapatala. Hindi nag-iisyu ang bagong sistema sa mag-aaral na may paghaharang sa sertipiko ([kabanata 20](20-registration.md)).
- Hindi itinatala ang mga bayarin (ang halaga lamang ang itinatala ng luma).
- Walang online na kahilingan ng mga mag-aaral (porting plan 17.5). Ang mga kawani ang nag-iisyu ng mga sertipiko.

## Mga uri at template

**Mga uri ng sertipiko**: code (ginagamit sa serial number), pangalan (3 wika), para kanino (anumang rekord ng mag-aaral / mga mag-aaral / mga nagtapos), may kasamang mga marka, may kasamang larawan, aktibo, pagkakasunod. Nagse-seed ang profile ng unibersidad sa Korea ng tatlong uri.

| Code | Uri | Para kanino | Tala |
|---|---|---|---|
| `ENR` | Certificate of Enrollment | Mga mag-aaral (hindi nagtapos) | |
| `GRD` | Certificate of Graduation | Mga nagtapos | Petsa ng pagtatapos, numero ng degree |
| `TRN` | Transcript of Records | Anumang rekord ng mag-aaral | Talahanayan ng mga pinal na marka ayon sa termino, kabuuang GPA at nakuhang units |

**Mga template** (sa ibaba ng screen ng pag-edit ng uri): isa bawat wika. Pamagat, nilalaman (mga talatang pinaghihiwalay ng blangkong linya), footer, lumagda. Gumagamit ang nilalaman ng mga placeholder sa loob ng curly braces.

| Placeholder | Value |
|---|---|
| `{institution}` | Pangalan ng institusyon (sa wikang iyon) |
| `{student_name}`, `{student_number}` | Pangalan (sa anyo ng wikang iyon, kung wala ay ang baybay sa letrang Latin), student number |
| `{program}`, `{org_unit}` | Programa, yunit ng organisasyon |
| `{admission_date}`, `{graduation_date}`, `{degree_number}` | Petsa ng admisyon, petsa ng pagtatapos, numero ng degree |
| `{status}`, `{year_level}` | Katayuan ng mag-aaral, antas ng taon |
| `{birth_date}`, `{gender}`, `{nationality}` | Petsa ng kapanganakan, kasarian, nasyonalidad |
| `{issue_date}`, `{serial_number}`, `{purpose}`, `{copies}` | Petsa ng pag-isyu, serial number, layunin, bilang ng kopya |

Ipinapakita ang mga petsa sa format ng wikang iyon. Ang nawawalang value (hal. ang petsa ng pagtatapos) ay ipinapakita bilang blangko. Awtomatikong idinaragdag sa itaas ng nilalaman ang isang talahanayan ng personal na impormasyon (pangalan, student number, petsa ng kapanganakan, kasarian, nasyonalidad, programa, petsa ng admisyon, saka ang petsa ng pagtatapos para sa mga nagtapos o kung hindi ay katayuan at antas ng taon).

## Mga setting

**Mga setting > Mga setting ng sertipiko**

- **Pattern ng serial number**: default `{TYPE}-{YYYY}-{SEQ:6}` → `ENR-2026-000001`. Kailangan ang `{SEQ:n}`. Hiwalay na nagbibilang ang sequence para sa bawat natatanging prefix. Nagbibilang ang `{TYPE}-{SEQ:6}` bawat uri sa lahat ng taon, ang `{YYYY}{SEQ:5}` bawat taon sa lahat ng uri. Hindi kailanman nagbabanggaan ang sabay-sabay na pag-isyu (atomikong dinaragdagan ang counter sa database).
- **Bisa ng beripikasyon (araw)**: ang 0 ay nangangahulugang palagi. Pagkalipas ng panahon, sumasagot ang pahina ng beripikasyon ng "nag-expire". Nananatili sa talaan ang pag-isyu mismo.
- **Lumagda ayon sa wika**: ang pangalang ipinapakita sa itaas ng selyo ("Pangulo, Ninefruits University"). Nangunguna ang lumagdang nasa template.

## Pag-isyu

**Mag-isyu ng sertipiko** sa screen ng rekord ng mag-aaral: uri, wika, layunin, mga kopya (1 hanggang 20). Sa pag-save, nagbubukas ang PDF sa bagong tab.

Tinatanggihan ang pag-isyu kapag:

- hindi tumutugma ang para kanino ng uri (Certificate of Enrollment para sa nagtapos, Certificate of Graduation para sa kasalukuyang mag-aaral)
- hindi aktibo ang uri
- walang template para sa wikang iyon
- may bisang **paghaharang sa sertipiko** para sa mag-aaral (isang hold, [kabanata 20](20-registration.md)); alisin muna ito
- nasa labas ng saklaw ng pagtingin ang rekord ng mag-aaral

Itinatala sa log ng aktibidad ang pag-isyu (`certificate.issued`: student number, uri, wika, serial number, mga kopya, layunin).

## Mga inisyung sertipiko

**Mga sertipiko > Mga inisyung sertipiko**: petsa ng pag-isyu, serial number, uri at wika, student number, pangalan, layunin, mga kopya, katayuan, kawani. I-filter ayon sa uri at katayuan. Kapareho ng sa mga rekord ng mag-aaral ang saklaw ng pagtingin.

- **PDF**: binubuksan ang nakaimbak na file. Hindi ito muling binubuo, kaya eksakto itong gaya noong iniisyu.
- **Pahina ng beripikasyon**: binubuksan ang pampublikong pahina.
- **Buuin muli**: bumubuo ng bagong PDF mula sa kasalukuyang datos, hal. matapos ayusin ang template, at pinapalitan ang file. Nananatili ang serial number at verification code; nagbabago ang checksum. Audit event `certificate.rerendered`. Katulad ng command na `certificate:render`.
- **Bawiin**: kailangan ng dahilan. Nananatili ang row at file, nagiging binawi ang katayuan, at sumasagot ang pahina ng beripikasyon ng "binawi". Audit event `certificate.revoked`. Hindi ibinabalik ang binawing sertipiko; nag-iisyu ng bago.

Hindi maaaring i-edit o i-delete sa screen ang mga row ng talaan.

## Beripikasyon

- Ipinapakita sa ibaba ng sertipiko ang verification URL (`/verify/{code}`) at isang QR code. Ang code ay random na string na may 20 character.
- Nagbubukas ang pahina nang hindi nagsa-sign in at limitado sa 30 kahilingan bawat minuto. Ipinapakita nito kung inisyu ang sertipiko, ang uri, serial number, petsa ng pag-isyu, at ang naka-mask na pangalan at student number. Magkakaiba ang sagot para sa binawi, nag-expire at hindi kilalang code.
- Para suriin ang mismong isinumiteng PDF file, ikumpara ang checksum (SHA-256) sa talaan sa hash ng file. Ginagawa rin ito ng command na `certificate:verify {code}`.

## Portal ng mag-aaral

**Mga sertipiko**: ang mga sertipikong inisyu sa sariling rekord ng mag-aaral, kasama ang mga PDF. Nakalista ang mga binawi kasama ang kanilang katayuan, pero hindi mada-download ng mag-aaral ang PDF ng binawing sertipiko (mabubuksan ito ng kawaning nag-isyu para suriin ang rekord). Hindi mabubuksan ang mga file ng ibang mag-aaral (ipinatutupad sa application at sa database).

## Mga madalas itanong

- **Mali ang baybay ng pangalan.** Iwasto ang variant ng pangalan ng tao (sa wikang iyon) at **Buuin muli**. Kung naibigay na ang sertipiko, bawiin ito at mag-isyu ng bago.
- **Binago ko ang template; paano ang mga sertipikong naiisyu na?** Hindi nagbabago ang mga ito. **Buuin muli** kung kailangan.
- **May nalaktawang serial number.** Kapag nagkaroon ng error sa pag-isyu (hal. hindi gumagana ang PDF service), nagagamit ang numero at maaaring walang maiwang row sa talaan. Ginagarantiya na natatangi ang mga numero, hindi na magkakasunod.
