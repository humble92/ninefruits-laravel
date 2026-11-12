# 25. Silid-aralan ng instruktor at pagdalo

Menu: ginagamit ng mga instruktor ang **Aking pagtuturo > Roster at pagdalo** sa portal, ng mga mag-aaral ang **Pagdalo** sa portal. Ginagamit ng mga kawani ang **Mga pagkikita ng klase** sa ibaba ng screen na **Kurikulum at mga seksiyon > Mga seksiyon > I-edit**.

| Sino | Ano ang magagawa nila |
|---|---|
| Mga nakatalagang instruktor | Roster (may larawan) at CSV, PDF ng attendance sheet, pagbuo, pagdaragdag at pagkansela ng mga pagkikita ng klase, pagpasok ng pagdalo |
| Mga kawaning may `Planuhin ang mga seksiyon` (sa loob ng saklaw) | Gaya ng mga instruktor + mga pagkikita ng klase sa panel ng administrasyon |
| Mga kawaning may `Tingnan ang mga seksiyon` (sa loob ng saklaw) | Roster, attendance sheet, pagtingin sa pagdalo |
| Mga mag-aaral | Ang sarili nilang pagdalo |

Ipinauubaya sa LMS ang mga feature ng silid-aralan gaya ng mga anunsiyo, takdang-aralin at materyales (porting plan seksiyon 12).

## Mga pagkakaiba sa lumang sistema

- **Hindi nagtatala ng pagdalo ang lumang sistema (YUSTIC).** Ang "attendance sheet" nito ay isang PDF na nagpi-print ng mga walang lamang kahon (27 hanggang 34, iba-iba bawat kopya) sa tabi ng roster, nang walang petsa at walang kabuuan. Gumagawa ang bagong sistema ng **mga pagkikita ng klase** mula sa mga oras ng klase, nagtatala ng dumalo, huli, liban o may pahintulot bawat pagkikita, at ipinapakita ang mga rekord at kabuuan sa PDF ng attendance sheet.
- **Roster.** Ipinapakita lamang ng lumang screen ng instruktor ang mga student number at pangalan (at inilalantad ang resident registration number at address sa isang popup) at walang download sa Excel. Ipinapakita lamang ng bagong sistema ang student number, pangalan, larawan, programa, antas ng taon at email na pang-contact, at nagda-download bilang CSV. Itinatala sa log ng audit ang mga download.
- **Access ng mga instruktor sa kanilang mga mag-aaral.** Nagdagdag ng RLS policy para makita ng mga instruktor ang mga rekord ng mag-aaral at ang mga tao ng mga mag-aaral sa kanilang mga seksiyon (dati, ang kanilang mga advisee lamang ang nakikita nila, kaya blangko ang mga pangalan kahit sa screen ng pagpasok ng marka).

## Mga pagkikita ng klase

- **Bumuo**: isang pagkikita bawat paglitaw ng lingguhang oras ng klase (araw, oras, period). Ginagamit ng mga instruktor ang button na **Bumuo mula sa oras ng klase**; ginagamit ng mga kawani ang **Mga pagkikita ng klase > Bumuo ng mga pagkikita** sa panel ng administrasyon o ang command na `attendance:generate-sessions {term}` (buong termino). Hindi ginagalaw ang mga kasalukuyang pagkikita at pagdalo, kaya maaari itong patakbuhin muli matapos magbago ang mga oras ng klase.
- **Magdagdag ng pagkikita**: klaseng labas sa mga oras ng klase, gaya ng make-up class. Mga petsa lamang sa loob ng sesyon.
- **Kanselahin**: kinakansela ang pagkikita na may dahilan. Nananatili ang pagkikita pero hindi isinasama sa mga kabuuan. Ibinabalik ito ng **Ibalik**.
- Walang kalendaryo ng holiday. Kanselahin ang mga pagkikita sa mga pampublikong holiday (tingnan ang seksiyon 13.2; magiging awtomatiko ito kapag naidagdag ang kalendaryo ng termino).

## Pagpasok ng pagdalo

Sa ilalim ng **Pagdalo** ng pagkikita, piliin ang dumalo, huli, liban o may pahintulot para sa bawat mag-aaral at magdagdag ng tala (200 character). Ang pinakamabilis na paraan ay **Lahat ay dumalo** saka baguhin ang mga eksepsiyon.

- Maipapasok ang pagdalo simula sa araw ng pagkikita (hindi para sa mga pagkikita sa hinaharap).
- Kapag nag-save muli, ang mga mag-aaral lamang na nagbago ang itinatala, at itinatago ng audit event (`attendance.recorded`) ang luma at bagong value.
- Kapag binura ang katayuan, ibinabalik ang mag-aaral sa "hindi naitala".

## Mga kabuuan

| Item | Kahulugan |
|---|---|
| Mga naganap na pagkikita | Mga pagkikitang hindi kinansela at nasa o bago ang araw na ito |
| Antas ng pagdalo | (dumalo + huli + may pahintulot) ÷ mga naganap na pagkikita × 100 |
| Hindi naitala | Mga naganap na pagkikitang walang rekord |

Walang panuntunang nagko-convert ng bilang ng pagkahuli sa pagliban o nagbibigay ng F kapag lumampas sa ikatlong bahagi ang pagliban. Iba-iba ito sa bawat unibersidad, kaya ang mga instruktor mismo ang nagpapakita ng pagdalo sa marka (item ng pagsusuri 13.5).

## PDF ng attendance sheet at CSV ng roster

Itinatala sa log ng audit ang dalawang download (`attendance.sheet_exported`; ang CSV ng roster gaya ng dati). Ang mga value sa CSV na nagsisimula sa `=`, `+`, `-` o `@` ay ine-export na may nangungunang single quote para hindi patakbuhin bilang formula.

- **Attendance sheet (PDF)**: landscape na A4. Mga mag-aaral pababa, mga pagkikita pahalang (hindi kasama ang mga kinansela), mga markang O/△/X/E at mga kabuuan bawat mag-aaral. Blangko ang mga pagkikitang darating pa, kaya nagagamit din ito sa manu-manong pagtatala.
- **Roster (CSV)**: student number, pangalan, programa, antas ng taon, email na pang-contact, katayuan ng pagpapatala. UTF-8 na may BOM, kaya direktang nabubuksan sa Excel. Itinatala ang bawat download sa audit event na `attendance.roster_exported`.

## Portal ng mag-aaral

**Pagdalo**: bawat termino at asignatura, ang bilang ng dumalo, huli, liban at may pahintulot, ang antas ng pagdalo, at ang rekord bawat pagkikita (ang ipinasok lamang ng instruktor).

## Pamamaraan sa operasyon

1. Bago magsimula ang termino, tapusin ang mga oras ng klase ng mga seksiyon ([kabanata 18](18-sections-and-timetables.md)).
2. Gawin ang mga pagkikita gamit ang `php artisan attendance:generate-sessions {term} --actor=...` (magagawa rin ito ng mga instruktor sa screen).
3. Patakbuhin muli kapag nagbago ang mga oras ng klase. Hindi awtomatikong dine-delete ang mga pagkikita ng mga inalis na oras; kanselahin ang mga ito.
