# 20. Pagpapatala

Menu: **Pagpapatala > Mga pagpapatala**, **Mga hold**, **Mga override sa panuntunan**, **Mga panuntunan sa pagpapatala**, **Mga limitasyon ng units**, **Mga pagtatangkang magpatala**, at ang mga tab na **Roster** at **Waitlist** ng screen ng pag-edit sa **Mga seksiyon**

| Pahintulot | Ano ang pinapayagan nito |
|---|---|
| `Tingnan ang mga pagpapatala at roster` | Tingnan ang mga pagpapatala, roster at pagtatangkang magpatala |
| `Magpatala, mag-drop at mag-withdraw ng mga mag-aaral para sa kanila (may dahilan)` | Pagpapatala ng kawani, pag-drop at pag-withdraw, pag-alis sa waitlist at agarang pag-akyat mula rito. Kapag itinalaga na may saklaw na yunit ng organisasyon, ang mga seksiyong iniaalok lamang ng yunit na iyon |
| `Maglagay at mag-alis ng mga hold, magbigay ng override sa panuntunan` | Mga hold, override sa panuntunan. Kapag itinalaga na may saklaw, ang mga mag-aaral lamang sa saklaw na iyon |
| `Pamahalaan ang mga set ng panuntunan sa pagpapatala at limitasyon ng units` | Mga set ng panuntunan bawat termino, limitasyon ng units |

Mga default ng tungkulin: nasa Registrar ang lahat ng apat na pahintulot, nasa Academic Affairs ang pagtingin pati ang mga panuntunan at limitasyon ng units, nasa Katuwang ng Departamento ang pagtingin at ang mga pagsasaayos ng pagpapatala (itinalaga na may saklaw ng departamento).

## Mga pagkakaiba sa lumang sistema

- Ang mga panuntunang naka-hard-code sa mga digit ng student number o sa mga code ng departamento sa lumang sistema (YUSTIC), gaya ng limitasyon ng units o ng hindi pagsasama ng mga asignatura sa credit bank, ay naging **mga setting bawat termino**. Muling binubuo ang mga lumang panuntunan ng YUST ng profile ng pag-install na `yust-legacy` (sa ibaba).
- Kapag tinanggihan ang isang kahilingan, **ipinapakita nang sabay-sabay ang lahat ng dahilan** (humihinto ang luma sa unang dahilan). Itinatago sa **Mga pagtatangkang magpatala** ang bawat pagtatangka at ang resulta bawat panuntunan.
- Hindi inuulit ang lumang bug sa limitasyon ng units para sa fakultad ng nursing na may student number mula 2014 (18 units ang ipinatupad kung saan dapat ay 21). 21 units ang ipinatutupad.
- Walang pagbili ng aklat sa panahon ng pagpapatala.

## Paghahanda ng termino

1. **Mga takdang panahon ng termino** ([kabanata 10](10-terms.md)): cart ng pagpapatala, pagpapatala (pasunud-sunod na pagbubukas bawat antas ng taon), add/drop, withdrawal. Itinatakda bawat sesyon ang mga takdang panahon ng add/drop at withdrawal.
2. **Mga panuntunan sa pagpapatala > Ihanda ang mga panuntunan para sa isang termino**: piliin ang termino at isang naunang terminong kokopyahin. Iwanang blangko para magsimula sa mga default. Ang terminong walang set ng panuntunan ay gumagamit ng mga default.
3. **Mga limitasyon ng units**: mga limitasyon bawat uri ng termino at grupo ng mga mag-aaral (sa ibaba).
4. **Mga seksiyon**: kapasidad, mga nakareserbang upuan (mga nakareserbang upuan at oras ng pagbubukas ng mga ito), **Laki ng waitlist** ([kabanata 18](18-sections-and-timetables.md)). Dapat **Nakalathala** ang seksiyon para tumanggap ng pagpapatala.
5. **Mga hold**: ilagay ang listahan ng mga mag-aaral na may hold, hal. hindi bayad na matrikula (posible ang pag-import ng CSV, [kabanata 9](09-csv-import.md)).
6. Pagsusuri: `php artisan registration:preflight 2027-SP` ([mga command sa operasyon](../operations/commands.md)). Iniuulat nito ang mga takdang panahon, set ng panuntunan, kapasidad at ang bilang ng mga mag-aaral na walang limitasyon ng units.

## Mga panuntunan

Sinusuri ang lahat ng panuntunan ayon sa pagkakasunod-sunod sa ibaba. Laging ipinatutupad ang **Takdang panahon, bukas na seksiyon at upuan** at wala sa listahan.

| Panuntunan | Ano ang sinusuri nito | Mga parameter |
|---|---|---|
| Panahon ng pagpapatala | Dapat bukas ang takdang panahon ng termino na tumutugma sa pamantayan ng target ng mag-aaral. Kailangan ng pag-drop ang takdang panahon ng pagpapatala o ang takdang panahon ng add/drop ng sesyon ng asignatura; kailangan ng pag-withdraw ang takdang panahon ng withdrawal ng sesyon | Wala |
| Mga hold | Tinatanggihan kapag may hold na humaharang sa pagpapatala | Wala |
| Katayuan ng mag-aaral | Ang mga napiling katayuan ng mag-aaral lamang (default: naka-enroll) ang maaaring magpatala | Mga pinapayagang katayuan |
| Kwalipikasyon sa seksiyon | Ang pamantayan sa kwalipikasyon at pagbubukod ng seksiyon | Wala |
| Parehong asignatura nang dalawang beses | Isang beses lamang bawat termino ang isang asignatura | Wala |
| Conflict sa oras | Tinatanggihan kapag nagsasapawan ang oras sa ibang nakapatalang seksiyon. Hindi nagkakaroon ng conflict ang unang at ikalawang hati ng termino na hindi nagsasapawan ang mga petsa | Pinapayagang pagsasapawan (minuto) |
| Limitasyon ng units | Kabuuang nakapatalang units ≤ ang naaangkop na limitasyon ng units. Hindi binibilang ang mga asignaturang naka-off ang setting na "Ibinibilang sa limitasyon ng units" | Wala (itinatakda sa screen ng Mga limitasyon ng units) |
| Mga prerequisite | Naipasa ang mga prerequisite (binibilang ang mga katumbas na asignatura, pinakamababang marka); maaaring ipatala ang mga corequisite sa parehong termino | Wala (itinatakda sa screen ng asignatura) |
| Mga retake | Kung maaaring kunin muli ang naipasang asignatura | Pinapayagan ang retake, pinakamaraming pagkuha, sa ilalim lamang ng isang marka |

May sariling mga setting ang bawat panuntunan.

- **Naka-on**: kapag naka-off, hindi sinusuri ang panuntunan.
- **Kapag hindi natugunan**: Harangin o Babala lamang. Sa babala, natutuloy ang pagpapatala at mensahe lamang ang nakikita ng mag-aaral.
- **Pinapayagan ang override**: dapat naka-on para magkabisa ang mga override sa panuntunan at ang field na "Laktawan ang mga panuntunan" ng pagpapatala ng kawani. Naka-off ito bilang default para sa mga hold at sa parehong asignatura nang dalawang beses.

Awtomatikong natutukoy ang mga retake at minamarkahan ang pagpapatala bilang **Retake**. Ang paghawak ng marka para sa mga retake ay nasa Phase 6.

## Mga limitasyon ng units

Itakda ang uri ng termino (blangko: bawat uri ng termino), ang mga mag-aaral (antas ng taon, taon ng admisyon, programa, yunit ng organisasyon, academic standing), ang pinakamataas na units at ang prayoridad. Itinatakda ang academic standing kapag isinara ang mga marka ng termino ([kabanata 21](21-grades.md)). Halimbawa, para limitahan sa 12 units ang mga mag-aaral na nasa academic warning, piliin ang Academic warning sa ilalim ng academic standing at bigyan ang limitasyon ng mas mataas na prayoridad kaysa sa pangkalahatan (ganito naka-set up ang demo data). Kapag ilang limitasyon ang tumugma sa isang mag-aaral, **ang may pinakamataas na prayoridad** lamang ang ipinatutupad. Kapag walang tumugma, walang limitasyon (iniuulat ito ng `registration:preflight`).

Halimbawa: ang mga lumang panuntunan ng YUST (profile na `yust-legacy`)

| Uri ng termino | Mga mag-aaral | Pinakamataas | Prayoridad |
|---|---|---|---|
| Termino 1 at 2 | Tinanggap noong 2014 o pagkatapos, fakultad ng nursing (code ng yunit ng organisasyon `NURS`) | 21 | 40 |
| Termino 1 at 2 | Tinanggap noong 2014 o pagkatapos | 18 | 30 |
| Termino 1 at 2 | Tinanggap noong 2013 o bago nito, fakultad ng nursing | 20 | 20 |
| Termino 1 at 2 | Tinanggap noong 2013 o bago nito | 17 | 10 |
| Summer at winter | Tinanggap noong 2014 o pagkatapos, fakultad ng nursing / iba pa | 6 / 5 | 40 / 30 |
| Summer at winter | Tinanggap noong 2013 o bago nito | 6 | 10 |

Para sa mga asignatura sa credit bank na nagsisimula ang code sa `009` o `900`, i-off ang "Ibinibilang sa limitasyon ng units" sa asignatura. Ini-install ang profile gamit ang `setup:install --profile=yust-legacy` (kasama rito ang profile ng unibersidad sa Korea).

## Mga upuan at waitlist

- Sinisiguro ang upuan sa sandali ng pagpapatala sa pamamagitan ng pag-lock sa row ng seksiyon. Kahit maraming mag-aaral ang sabay-sabay na nagpapatala, hindi nalalampasan ang kapasidad.
- Ang **mga nakareserbang upuan** (hal. 30 upuan para sa mga major) ay ginagamit lamang ng kanilang mga mag-aaral hanggang sa oras ng pagbubukas. Ang mga natirang nakareserbang upuan ay nagiging bukas na upuan sa oras ng pagbubukas. Ipinapakita ang mga ito ng portal bilang "bukas, nakareserba, kabuuan".
- Kapag mas malaki sa 0 ang **Laki ng waitlist**, maaaring **Sumali sa waitlist** ang mga mag-aaral sa punong seksiyon. Kailangan din sa pagsali sa waitlist na maipasa ang bawat panuntunan maliban sa upuan.
- Kapag may napalayang upuan dahil sa pag-drop, pag-withdraw o pagpapalaya ng mga nakareserbang upuan, ang mga naghihintay na mag-aaral ay **awtomatikong iniaakyat** ayon sa pagkakasunod-sunod. Muling sinusuri ang bawat panuntunan sa pag-akyat. Ang mag-aaral na sa pagitan nito ay nagpatala sa ibang asignatura at ngayon ay lumalampas sa limitasyon ng units o may conflict sa oras ay inaalis sa waitlist at itinatala ang dahilan. Tumatanggap ng email ang mag-aaral.
- Kapag sarado na ang lahat ng takdang panahon ng pagpapatala at add/drop, nag-e-expire ang mga natitirang entry sa waitlist.
- Hindi maaaring bawasan ang kapasidad nang mas mababa sa bilang ng mga mag-aaral na nakapatala na.

Walang paraan para baguhin ang pagkakasunod-sunod ng waitlist. Para mauna ang isang partikular na mag-aaral, gamitin ang **Ipatala ang mag-aaral** (taasan ang kapasidad kung puno ang seksiyon).

## Ang screen ng mag-aaral

Sa portal sa ilalim ng **Pagpapatala**, pinipili ng mag-aaral ang termino, naghahanap ayon sa code ng asignatura o pamagat at nagpapatala, nagdaragdag sa cart o sumasali sa waitlist. Ipinapakita ng screen ang kalagayan ng panahon ng pagpapatala (bukas, cart lamang, oras ng pagbubukas), ang nakapatalang units laban sa limitasyon ng units at ang talaorasan.

- **Cart**: sa takdang panahon ng cart, nag-iipon ang mga mag-aaral ng mga asignatura at, kapag nagbukas ang pagpapatala, **Ipatala lahat**. Pinapatakbo nang maaga ng pagdaragdag sa cart ang mga panuntunan at iniuulat ang mga problema, pero ang mga pagsusuri lamang sa takdang panahon, bukas na seksiyon at parehong asignatura nang dalawang beses ang pumipigil sa pagdaragdag. Muling sinusuri ang mga panuntunan sa pagpapatala.
- Nagbabago ang mga button na i-drop at mag-withdraw ayon sa mga takdang panahon ng termino. Nananatili sa kasaysayan ang asignaturang ini-withdraw bilang **Nag-withdraw** (ang markang W ay nasa Phase 6).
- Hanggang 30 kahilingan bawat minuto ang maaaring gawin ng bawat tao.

## Mga pagpapatala at pagsasaayos ng kawani

**Mga pagpapatala**: i-filter ayon sa termino at katayuan, maghanap ayon sa student number o pangalan. Bilang default, ipinapakita nito ang mga nakapatalang entry ng kasalukuyang (o susunod na) termino.

**Ipatala ang mag-aaral** (sa itaas ng Mga pagpapatala, o sa tab na **Roster** ng seksiyon): ilagay ang student number, ang seksiyon, ang mga panuntunang lalaktawan at ang dahilan.

- Ang parehong mga panuntunan gaya sa mga mag-aaral ang sinusuri. **Hindi ipinatutupad ang panahon ng pagpapatala sa pagpapatala ng kawani.**
- Gumagana lamang ang "Laktawan ang mga panuntunan" para sa mga panuntunang naka-on ang **Pinapayagan ang override**. Hindi mapipili ang bukas na seksiyon at upuan (taasan na lang ang kapasidad).
- Ipinapakita bilang abiso ang resulta (nakapatala, nasa waitlist, tinanggihan kasama ang mga dahilan). Itinatala ito sa log ng aktibidad kasama ang dahilan.

**I-drop at mag-withdraw**: mula sa row sa Mga pagpapatala o sa tab na Roster, na may dahilan. Posible anuman ang mga takdang panahon ng termino. Kapag may napalayang upuan, sumusunod ang pag-akyat mula sa waitlist.

## Mga hold at override sa panuntunan

**Mga hold** (lumang pagtanggi sa pagpapatala `SRDENY`): student number, uri ng hold (Hindi nagpatala sa termino, Pinansiyal, Akademiko, Administratibo, Iba pa), epekto (hinaharang ang pagpapatala / pagtingin ng marka / mga sertipiko), simula at wakas, dahilan. Naka-off bilang default ang paghaharang sa pagtingin ng marka at sa sertipiko. Bukod sa hindi bayad na matrikula, nagagamit ang mga ito sa mga kasong administratibo kung saan kailangang pigilin muna ang mga marka, gaya ng imbestigasyon sa academic misconduct. Kapag hinaharang ang pagtingin ng marka, hindi makikita ng mag-aaral ang screen ng marka sa portal o ang ulat ng marka ([kabanata 21](21-grades.md)). Hindi apektado ang mga screen ng kawani at ang pagpasok ng marka ng mga instruktor. Kapag hinaharang ang sertipiko, hindi makapag-iisyu ng mga sertipiko ang mga kawani ([kabanata 23](23-certificates.md)). Hindi dine-delete ang mga hold; **inaalis** ang mga ito na may dahilan. Ipinapakita ng default na listahan ang mga hold na may bisa.

**Mga override sa panuntunan** (lumang pagpapahintulot sa pagpapatala `SRALLOW`): mag-aaral, termino, panuntunan, kung saan ito ipinatutupad (blangko: ang buong termino, o isang partikular na seksiyon o asignatura), pag-expire, dahilan. Sa override, natutuloy ang pagpapatala kahit hindi pumasa sa panuntunang iyon (basta naka-on ang Pinapayagan ang override ng panuntunan). Maaari ring buksan ng override ang panuntunan sa panahon ng pagpapatala (hal. isang magtatapos na mag-aaral na hindi umabot sa takdang panahon). Hindi maaaring i-override ang bukas na seksiyon at upuan. **Bawiin** ang override kapag hindi na ito kailangan.

## Mga pagtatangkang magpatala

Itinatago ang bawat pagtatangka sa pagpapatala, pag-drop, pag-withdraw, waitlist, pag-akyat at cart kasama ang oras, kinalabasan, mga panuntunang hindi natugunan at mga mensahe, ang kawani o mag-aaral na gumawa nito, at ang request ID. Para sa mga tanong na "bakit hindi ako nakapagpatala", maghanap ayon sa student number. Masisilip kung ano ang mangyayari kung magpatala ngayon ang mag-aaral gamit ang `php artisan registration:simulate {student number} {course code-section number}`.
