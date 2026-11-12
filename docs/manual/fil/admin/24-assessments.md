# 24. Mga pagsusulit

Menu: **Mga pagsusulit > Mga sesyon ng pagsusulit**, **Mga resulta ng pagsusulit**, **Mga uri ng pagsusulit**. Nagpaparehistro at tumitingin ng kanilang mga resulta ang mga mag-aaral sa portal sa ilalim ng **Mga pagsusulit**.

| Pahintulot | Ano ang pinapayagan nito |
|---|---|
| `Iparehistro ang mga mag-aaral, magpasok at maglathala ng resulta, magtala ng panlabas na resulta` | Iparehistro, kanselahin at markahang liban ang mga mag-aaral, magpasok ng resulta, mag-import ng CSV, maglathala ng resulta, magtala at mag-alis ng panlabas na resulta, tingnan ang talaan ng resulta (sa loob ng saklaw ng pagtingin sa rekord ng mag-aaral) |
| `Pamahalaan ang mga uri, bahagi at sesyon ng pagsusulit` | Gumawa at mag-edit ng mga uri, bahagi at sesyon |

Mga default ng tungkulin: nasa Registrar at Academic Affairs ang dalawa.

## Mga pagkakaiba sa lumang sistema

- **Walang nakapirming column bawat uri.** Naka-hard-wire sa lumang sistema (YUSTIC) ang apat na column na gramatika, pagsulat, pakikinig at pagbasa sa `CET`, isang P/N na titik bawat paksa para sa komprehensibong pagsusulit (`SSCORE_ADD`), at isa pang table para sa pagsusulit sa kompyuter (`COMEXAM`). Itinatakda ng bagong sistema ang mga bahagi at ang paraan ng pagmamarka sa **uri ng pagsusulit** at hinahawakan ang bawat pagsusulit gamit ang parehong estruktura ng sesyon, pagpaparehistro at resulta.
- **Pasya sa pagpasa.** Sa luma, ang kabuuan ng apat na bahagi na nasa o lampas sa cut-off ay nangangahulugang pasado, nang walang pinakamababa bawat bahagi. Nagpapasya ang bagong sistema gamit ang iskor na pasado ng uri (o ng sesyon) at ang **pinakamababa bawat bahagi**. Ang mga uring pasado/bagsak ay pinagpapasyahan ng mga kawani.
- **Takdang panahon ng pagpaparehistro at kapasidad.** Wala nito ang luma. May oras ng pagbubukas at pagsasara ng pagpaparehistro at kapasidad ang isang sesyon.
- **Paglalathala ng resulta.** Sa luma, nakikita ng mga mag-aaral ang mga resulta sa sandaling maipasok ang mga ito, at ang pinakahuling sesyon lamang. Sa bagong sistema, nakikita ng mga mag-aaral ang mga resulta matapos ang **Ilathala ang mga resulta** ng sesyon, at itinatago ang mga resulta ng bawat sesyon.
- **Mga panlabas na resulta.** Walang lugar ang luma para itala ang mga panlabas na pagsusulit gaya ng TOEFL o TOPIK (ang kahon ng HSK sa graduation audit sheet ay walang lamang label). Itinatala ang mga panlabas na resulta kasama ang file ng patunay.
- **Ugnayan sa mga kinakailangan sa pagtatapos.** Sa luma, binabasa ng isang tao ang graduation audit sheet at nagpapasya. Inilalagay ito sa kurikulum ng uri ng kinakailangan na **Pumasang pagsusulit** ([kabanata 17](17-courses-and-curriculum.md)).

## Mga uri at bahagi ng pagsusulit

**Mga uri ng pagsusulit**: code, pangalan (3 wika), kategorya (Wika, Komprehensibo, Tesis, Panlabas na pagsusulit), pagmamarka, pinakamataas na iskor, iskor na pasado, pinapayagan ang panlabas na resulta, aktibo.

| Pagmamarka | Input | Pasya |
|---|---|---|
| Iskor | Iskor bawat bahagi (ang kabuuan kapag walang bahagi) | Kabuuan ≥ iskor na pasado at bawat bahagi ≥ pinakamababa nito. Kapag walang iskor na pasado, ang mga kawani ang pumipili |
| Pasado/bagsak | Pumasa o hindi | Ang mga kawani ang pumipili |

**Mga bahagi** (sa ibaba ng screen ng pag-edit ng uri): code (pangalan ng column sa CSV), pangalan, pinakamataas na iskor, pinakamababa para pumasa (opsiyonal). Kapag may mga bahagi, ang kabuuan ay ang suma ng mga iskor ng bahagi.

Mga uring sine-seed ng profile ng unibersidad sa Korea:

| Code | Uri | Pasya | Tala |
|---|---|---|---|
| `ENG_COMP` | Komprehensibong pagsusulit sa Ingles | Iskor, pasado sa 60 | Pakikinig, pagbasa, pagsulat, gramatika, 25 puntos bawat isa |
| `COMP_EXAM` | Komprehensibong pagsusulit sa kompyuter | Pasado/bagsak | |
| `THESIS` | Tesis sa pagtatapos | Pasado/bagsak | |
| `TOPIK` | TOPIK (Test of Proficiency in Korean) | Pasado/bagsak, panlabas | Itinatala ang antas at numero ng sertipiko |

## Mga sesyon

**Mga sesyon ng pagsusulit**: uri, petsa (natatangi bawat uri), lugar, kapasidad, pagbubukas at pagsasara ng pagpaparehistro, iskor na pasado (blangko: ang value ng uri). Nasa ibaba ng screen ng pag-edit ng sesyon ang listahan ng **Mga nagparehistro**.

- **Iparehistro ang mag-aaral** (ilagay ang student number): makapagpaparehistro ng mga mag-aaral ang mga kawani anuman ang takdang panahon ng pagpaparehistro, hanggang sa petsa ng pagsusulit. Ipinatutupad ang kapasidad. Walang maipaparehistro sa sesyong lumipas na ang petsa. Ipinapasok lamang ang mga resulta para sa mga nakarehistrong mag-aaral; iniuulat bilang error at nilalaktawan ang mga row sa CSV ng mga hindi nakarehistrong mag-aaral.
- Nagpaparehistro at nagkakansela ang mga mag-aaral sa portal sa panahon ng takdang panahon ng pagpaparehistro. Ang mga mag-aaral na naka-enroll at naka-leave lamang ang makagagawa nito.
- **Ipasok ang resulta**: iskor bawat bahagi (o kabuuan), pumasa o hindi kung kailangan, tala. Bago ang paglalathala, pinapalitan ito ng muling pagpasok (audit event `assessment.result_recorded`). **Matapos ang paglalathala, naka-lock ito** (tingnan ang mga kahilingan sa pagwawasto sa ibaba).
- **Liban**: nakarehistro pero hindi kumuha ng pagsusulit. Kapag nagpasok ng resulta, ibinabalik sa nakarehistro ang pagpaparehistro.
- **Kanselahin ang pagpaparehistro**: hindi makakansela ang pagpaparehistrong may resulta na.
- **Ilathala ang mga resulta**: mula ngayon, nakikita ng mga mag-aaral ang mga resulta ng sesyong ito. Matapos ang paglalathala, hindi na direktang mai-edit o mai-delete ang mga resulta; nagbabago ang mga ito sa pamamagitan ng **kahilingan sa pagwawasto** (desisyon noong 2026-10-23). Katulad ng command na `assessment:publish-results`.
- Ang **iskor na pasado** ng sesyon (pumapalit sa sa uri): ginagamit kapag ibinaba ng isang pulong ang cut-off dahil mahirap ang pagsusulit. Mababago ito **bago ang paglalathala**, at sa sandaling magbago ito, ang bawat resultang naipasok na para sa sesyong iyon ay **muling hinahatulan** mula sa mga nakaimbak na iskor (ang kabuuan laban sa bagong hangganan, ang mga pinakamababa bawat bahagi ayon sa itinakda sa uri). May dahilan, at itinatago ng audit event na `assessment.pass_score_changed` ang luma at bagong hangganan kasama ang mga student number na nagbago ang pasya. **Matapos ang paglalathala**, naka-lock ito (mga kahilingan sa pagwawasto lamang). Hindi mababago sa sesyon ang pinakamababa bawat bahagi (setting ng uri).
- **Mag-import ng mga resulta (CSV)**: format sa ibaba. Nilalaktawan bilang error ang mga row ng hindi nakarehistrong mag-aaral. Katulad ng command na `assessment:import-results`.

Format ng CSV (header row, UTF-8):

| Column | Nilalaman |
|---|---|
| `student_number` | Student number (kinakailangan) |
| Code ng bahagi (`LISTENING`, …) | Iskor ng bahagi. Hindi sensitibo sa malaki o maliit na titik |
| `total` | Kabuuan para sa mga uring walang bahagi |
| `passed` | `Y`/`N`. Kinakailangan para sa mga uring pasado/bagsak o mga uring walang iskor na pasado |
| `absent` | Minamarkahang liban ng `Y` ang mag-aaral |
| `note` | Tala |

Nilalaktawan ang mga row na may error at iniuulat kasama ang numero ng linya sa abiso ng resulta (screen) o sa output (command).

## Mga pagwawasto ng resulta

Ang pamamaraan para baguhin ang mga resulta ng nakalathalang sesyon. Gumagana ito tulad ng mga pagbabago ng marka.

1. **Humiling ng pagwawasto** mula sa listahan ng mga nagparehistro sa sesyon: ang mga bagong iskor (o pumasa o hindi) at ang dahilan. Isang nakabinbing kahilingan bawat resulta. Audit event `assessment.correction_requested`.
2. Sa ilalim ng **Mga pagsusulit > Mga pagwawasto ng resulta**, kini-click ng may hawak ng pahintulot na `Pamahalaan ang mga uri, bahagi at sesyon ng pagsusulit` ang **Aprubahan** (opsiyonal ang tala) o **Tanggihan** (kailangan ng tala). **Walang sinumang makapagpapasya sa sarili niyang kahilingan.**
3. Sa pag-apruba, agad na nagbabago ang resulta sa mga bagong value at nakikita ito ng mag-aaral. Mga audit event `assessment.correction_approved`, `assessment.result_corrected`. Makakansela ng humiling ang kahilingan hanggang sa mapagpasyahan ito.

Ipinapakita ng listahan ang mga nakabinbing kahilingan bilang default, kasama ang kasalukuyang mga value at ang mga iminumungkahing value nang magkatabi.

## Talaan ng resulta

**Mga resulta ng pagsusulit**: bawat resulta (mga sesyon at panlabas). I-filter ayon sa uri, pinagmulan at pumasa. Kapareho ng sa mga rekord ng mag-aaral ang saklaw ng pagtingin.

- **Magtala ng panlabas na resulta**: student number, uri (ang mga uring pinapayagan lamang ang panlabas na resulta), petsa ng pagkuha (hindi sa hinaharap), kabuuan, antas, pumasa o hindi, numero ng sertipiko, file ng patunay (PDF, JPG, PNG, 5 MB), tala. Audit event `assessment.external_recorded`.
- **Patunay**: binubuksan ang file. Mabubuksan ng mga mag-aaral ang sa kanila.
- **Alisin**: kailangan ng dahilan, audit event `assessment.result_removed`. Para sa pag-delete at muling pagtatala ng maling naipasok na panlabas na resulta o ng resulta bago ang paglalathala. Hindi maaalis ang mga resulta ng nakalathalang sesyon (kahilingan sa pagwawasto).

## Mga kinakailangan sa pagtatapos

Sa editor ng **Mga kinakailangan sa pagtatapos**, piliin ang uri ng kinakailangan na **Pumasang pagsusulit** at tukuyin ang uri ng pagsusulit. Natutugunan ito ng mag-aaral kapag may kahit isang pasadong resulta (sesyon o panlabas) ng uring iyon. Ipinapakita sa itaas ng pahinang **Mga pagsusulit** sa portal ang mga uring kinakailangan ng kurikulum ng mag-aaral at kung naipasa na ang mga ito. Binabasa ng degree audit (Phase 11) ang kinakailangang ito.

## Portal ng mag-aaral

**Mga pagsusulit**: mga kinakailangan ng kurikulum at kung naipasa na, mga sesyong bukas para sa pagpaparehistro (magparehistro, magkansela), aking mga pagpaparehistro, aking mga resulta (mga nakalathalang sesyon at panlabas na resulta, mga iskor ng bahagi, patunay).

## Mga madalas itanong

- **Kailangan kong iparehistro ang isang mag-aaral sa labas ng takdang panahon ng pagpaparehistro.** Iparehistro siya ng mga kawani sa ilalim ng **Mga nagparehistro > Iparehistro ang mag-aaral** sa sesyon.
- **Gusto kong baguhin ang iskor na pasado para sa sesyong ito lamang.** Ilagay ang iskor na pasado ng sesyon bago mailathala ang mga resulta. Agad na muling hinahatulan ang lahat ng resultang naipasok na batay sa mga nakaimbak na iskor (nananatili ang pinakamababang iskor ng bawat bahagi mula sa uri). Pagkatapos ng paglalathala, hindi na ito mababago; gumamit ng kahilingan sa pagwawasto.
- **Mali ang naipasok kong iskor.** Bago ang paglalathala, **Ipasok ang resulta** muli para palitan ito. Matapos ang paglalathala, maghain ng **kahilingan sa pagwawasto** at ipaapruba ito sa ibang kawani.
- **Estadistika ng mga liban.** I-filter ang listahan ng mga nagparehistro ayon sa katayuan.
