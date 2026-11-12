# 9. Pag-import ng CSV

Gamitin ang button na **Mag-import ng CSV** sa itaas ng mga screen ng yunit ng organisasyon, programa, gusali, silid at listahan ng code (pahintulot na `Mag-import ng sangguniang datos mula sa CSV`). May parehong button ang mga listahan ng rekord ng mag-aaral at instruktor (mga pahintulot na `Gumawa at mag-edit ng mga rekord ng mag-aaral` at `Pamahalaan ang mga instruktor` ayon sa pagkakabanggit). Ang napakalalaking file ay maaaring i-import sa parehong paraan ng IT operator gamit ang command na `reference:import`.

## Mga karaniwang panuntunan

- Nasa unang linya ang mga pangalan ng column (mga table sa ibaba). UTF-8 ang encoding; sa Excel, i-save bilang "CSV UTF-8".
- Ang row na may code na umiiral na ay **nag-a-update** ng rekord na iyon. Hindi gumagawa ng duplicate ang muling pag-import ng parehong file.
- Ang mga column na bawat wika ay nasa anyong `column_language`: `name_en`, `name_ko`, `name_zh_CN`. Kinakailangan ng mga bagong row ang pangalan sa Ingles (`name_en`). Hindi binubura ng mga walang lamang cell ang mga umiiral na salin.
- Isinusulat ang mga petsa bilang `2026-03-01`; ang oo/hindi bilang `1`/`0` o `true`/`false`.
- Tumatakbo sa background ang pag-import. Kapag tapos na, dumarating ang resulta bilang notification sa kanang itaas ng panel ng administrasyon; kung may mga row na nabigo, ang **I-download ang impormasyon tungkol sa mga hindi na-import na row** ay nagbibigay ng CSV na may dahilan sa bawat row. Ayusin ito at i-import muli ang file na iyon lamang.
- Kung iba ang mga pangalan ng column sa screen, maaari mong itugma nang mano-mano ang mga column sa dialog ng pag-import.

## Mga format ng column

| Uri | Mga kinakailangang column | Mga opsiyonal na column |
|---|---|---|
| Mga yunit ng organisasyon | `code`, `type` (code ng uri ng yunit ng organisasyon), `name_en`, `effective_start_date` | `parent` (code ng magulang na yunit), `name_ko` atbp., `short_name_*`, `effective_end_date`, `sort_order` |
| Mga programa | `code`, `org_unit` (code ng yunit ng organisasyon), `degree_level`, `name_en`, `effective_start_date` | `degree_title_*`, `standard_duration_years`, `effective_end_date`, `allows_second_major`, `allows_minor` |
| Mga gusali | `code`, `name_en` | `name_*`, `address`, `is_active` |
| Mga silid | `building` (code ng gusali), `code` (numero ng silid) | `name_*`, `room_type` (code ng uri ng silid), `org_unit`, `capacity`, `is_schedulable`, `is_active` |
| Mga halaga ng code | `type` (code ng listahan), `code`, `name_en` | `name_*`, `sort_order`, `is_active` |

Mga halaga ng `degree_level`: `associate`, `bachelor`, `master`, `doctorate`, `certificate`, `non_degree`

Para sa mga yunit ng organisasyon, kailangang mauna ang mga magulang na yunit (isaayos bilang unibersidad → kolehiyo → departamento).

## Halimbawa: mga yunit ng organisasyon

```csv
code,type,parent,name_en,name_ko,effective_start_date
UNI,university,,Sample University,샘플대학교,1990-03-01
ENG,college,UNI,College of Engineering,공과대학,1990-03-01
CSE,department,ENG,Computer Science,컴퓨터공학과,1995-03-01
```

Ang mga pag-import para sa mga mag-aaral at instruktor (Phase 2) at mga asignatura (Phase 4) ay idinadagdag kasabay ng mga feature na iyon.

## Mga mag-aaral at instruktor

Itinutugma at ina-update ang mga rekord ng mag-aaral ayon sa `student_number`, ang mga instruktor ayon sa `employee_number`. Parehong gumagamit ang dalawang uri ng mga column ng tao.

| Uri | Mga kinakailangang column para sa bagong row | Mga opsiyonal na column |
|---|---|---|
| Mga rekord ng mag-aaral | `student_number`, `program` (code ng programa), `admission_date`, `family_name` | `catalog_year` (taon ng admisyon kapag walang laman), `year_level`, `status`, `graduation_date`, `institutional_email` |
| Mga instruktor | `employee_number`, `org_unit` (code ng yunit ng organisasyon), `employment_type`, `family_name` | `academic_title_*`, `hire_date`, `end_date` |
| Mga column ng tao (magkasalo) | — | `given_name`, mga pangalan bawat wika gaya ng `name_ko`, `birth_date`, `gender`, `email`, `phone`, `national_id` |

- Mga halaga ng `status`: `enrolled`, `on_leave`, `withdrawn`, `dismissed`, `graduated` (kinakailangan ang `graduation_date` para sa graduated). Ang katayuang binago sa pag-import ay itinuturing na **pagwawasto ng datos** at hindi gumagawa ng kasaysayan ng pagbabago ng katayuan. Ang mga tunay na pagbabago ay itinatala sa screen ([kabanata 13](13-status-changes-and-advisors.md)).
- Mga halaga ng `employment_type`: `full_time`, `part_time`, `adjunct`, `visiting`, `emeritus`. Mga halaga ng `gender`: `female`, `male`, `other`, `undisclosed`.
- Kung tumugma ang `national_id` ng bagong rekord ng mag-aaral sa isang umiiral na tao, ikinakabit ang rekord sa taong iyon (muling pagtanggap). Kung hindi, gumagawa ng bagong tao. Sa pag-update, hindi binubura ng mga walang lamang cell ang mga umiiral na halaga.
- Walang account ang mga in-import na mag-aaral at instruktor. Padalhan sila ng imbitasyon gamit ang [mga imbitasyon sa account](14-merge-and-invitations.md#mga-imbitasyon-sa-account).

```csv
student_number,program,admission_date,year_level,status,family_name,given_name,name_ko,birth_date
202600001,B-CSE,2026-03-02,1,enrolled,Kim,Minsu,김민수,2007-05-01
```

## Mga asignatura

**Mag-import ng CSV** sa itaas ng listahan ng asignatura (pahintulot na `Pamahalaan ang mga asignatura, bersiyon at requisite`) o `reference:import courses`. Ang isang row ay isang bersiyon ng asignatura.

| Mga kinakailangang column | Mga opsiyonal na column |
|---|---|
| `code`, `effective_start_date`, `title_en`, `credits` (bagong bersiyon) | `org_unit` (kinakailangan para sa bagong asignatura; ang code ng namamahalang yunit ng organisasyon), `effective_end_date`, `title_*`, `lecture_hours`, `lab_hours`, `category` (code ng kategorya ng asignatura), `grading_basis` (`graded`, `pass_fail`), `counts_toward_credit_limit` |

- Itinutugma ang mga asignatura ayon sa `code`, ang mga bersiyon ayon sa `code` + `effective_start_date`. Ina-update ang mga ito kapag in-import muli ang parehong file.
- Maaaring i-load ang kasaysayan ng rebisyon mula sa iisang file: ilista ang mga row ng parehong code ayon sa panahon (lagyan ng `effective_end_date` ang naunang bersiyon).
- Nabibigo ang row na ang panahon ay sumasapaw sa ibang bersiyon. Kung nabigo ang row ng bagong asignatura, hindi rin nagagawa ang asignatura.
- Ang mga requisite, katumbas na asignatura at kinakailangan sa pagtatapos ay inilalagay sa screen ([kabanata 17](17-courses-and-curriculum.md)).

```csv
code,org_unit,effective_start_date,effective_end_date,title_en,title_ko,credits,category
CSE2010,CSE,2015-03-01,2026-02-28,Data Structures,자료구조,3,MAJ_REQ
CSE2010,,2026-03-01,,Data Structures,자료구조,4,MAJ_REQ
```

## Mga hold

**Mag-import ng CSV** sa itaas ng listahang **Pagpapatala > Mga hold** (pahintulot na `Maglagay at mag-alis ng mga hold, magbigay ng override sa panuntunan`) o `reference:import registration_holds`. Ginagamit para i-load ang lumang datos ng `SRDENY` o ang listahan ng tanggapan ng pananalapi ng mga mag-aaral na hindi pa nakababayad.

| Mga kinakailangang column | Mga opsiyonal na column |
|---|---|
| `student_number`, `hold_type` (`unregistered`, `financial`, `academic`, `administrative`, `other`), `reason` | `starts_at` (default: oras ng pag-import), `ends_at`, `blocks_registration` (default 1), `blocks_grades`, `blocks_certificates` |

- Gumagawa ng bagong hold ang bawat row (pinagmulang `import`). Kapag in-import nang dalawang beses ang parehong file, dalawang beses ding gagawin ang mga hold, kaya suriin ang listahan bago mag-import muli.
- Nabibigo ang mga row na may hindi kilalang student number. Inaalis ang mga hold sa screen kasama ang dahilan ([kabanata 20](20-registration.md)).

```csv
student_number,hold_type,reason,ends_at
202312345,financial,2027-1 등록금 미납,
202254321,academic,학사경고 상담 필요,2027-03-15
```
