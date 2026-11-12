# 28. Integrasyon ng LMS

Menu: **Integrasyon ng LMS > Mga platform ng LMS**, **Mga link ng asignatura**, **Mga log ng sync** (pahintulot na `Pamahalaan ang mga platform, link at pag-sync ng LMS`; default: Academic Affairs, bukod sa Administrator ng Sistema at Registrar). Ginagamit ng mga instruktor ang mga link ng LMS sa ilalim ng **Aking pagtuturo** at sa detalye ng seksiyon, at ang **Kunin ang mga iskor mula sa LMS** sa screen ng pagpasok ng marka. Sumusunod ang pagkuha sa parehong mga kondisyon ng pagpasok ng marka (mga instruktor sa loob ng takdang panahon ng pagpasok ng marka, mga kawani sa loob ng kanilang saklaw sa pamamahala ng `Mga marka`).

Nasa [gabay sa integrasyon ng LMS](../../../lms-integration-guide.md) ang mga detalye para sa developer.

## Ano ang napupunta saan

| SIS → LMS | Kailan |
|---|---|
| Seksiyon (paggawa ng course) + mga instruktor | Kapag **Nakalathala** na ang seksiyon, at gamit ang `lms:sync` |
| Kumpirmadong pagpapatala → enrollment | Agad sa pagpapatala, pag-akyat mula sa waitlist o pagpapatala ng kawani |
| Pag-drop o pag-withdraw → pag-unenroll | Agad |
| Buong reconcile | Araw-araw nang 02:30 (`lms:reconcile`) |

| LMS → SIS | Kailan |
|---|---|
| Mga pinal na iskor (draft) | Kapag kini-click ng instruktor ang **Kunin ang mga iskor mula sa LMS** sa screen ng pagpasok ng marka. Ang instruktor ang nagse-save at nagsusumite |

Hindi ipinapadala sa LMS ang mga pinal na marka. Nasa LMS ang mga materyales ng asignatura, takdang-aralin at quiz.

## Mga platform

**Mga platform ng LMS**: code, pangalan, driver, base URL, mga kredensyal (key/value, naka-encrypt na iniimbak, pinananatili kapag iniwang blangko sa pag-edit), aktibo, i-sync ang mga roster, kunin ang mga pinal na iskor.

- Driver na **Wala (nagtatala lamang)**: walang tinatawagan at itinatala ang ipadadala sana. Gamitin ito para suriin ang daloy o para sa mga demonstrasyon bago mag-integrate. Ginagamit ito ng platform na `DEMO` sa demo data.
- `Moodle`, `Open edX`: wala pang connector (minarkahang hindi pa naipatutupad). Kapag pinili, itinatala bilang nabigo ang mga sync.
- Kapag in-on ang **Aktibo**, agad nang dumadaloy simula noon ang mga paglalathala ng seksiyon at mga pagbabago sa pagpapatala at isinasama ang platform sa gabi-gabing reconcile.

## Mga link ng asignatura at log ng sync

- **Mga link ng asignatura**: aling seksiyon ang naka-link sa aling course ng platform (id, URL), katayuan (Nakabinbin/Naka-link/Nabigo), huling sync at error. Muling itinutulak ng **I-sync muli** ang seksiyon, mga instruktor at roster.
- **Mga log ng sync**: resulta at mensahe bawat hakbang (course, pagdaragdag at pag-alis ng miyembro, pagkuha ng iskor, reconcile). Dito hanapin ang sanhi ng pagkabigo.
- Mga command: `lms:sync {platform} --term={term}` (buong pagtulak, `--dry-run`), `lms:reconcile` (reconcile).

## Portal

- Ipinapakita ang link sa course ng platform sa detalye ng asignatura para sa mga mag-aaral at instruktor at sa listahan ng **Aking pagtuturo** ng instruktor (mga naka-link na seksiyon lamang).
- Pagpasok ng marka: kapag naka-on ang **Kunin ang mga pinal na iskor** para sa platform at naka-link ang seksiyon, lumalabas ang button na **Kunin ang mga iskor mula sa LMS**. Pinupunan lamang nito ang column ng iskor at hindi nagse-save.

## Mga madalas itanong

- **May mag-aaral na wala sa LMS.** Kailangan ng mag-aaral ng account (user) para ma-sync. Kung wala, imbitahan siya ([kabanata 14](14-merge-and-invitations.md)); idaragdag siya ng susunod na reconcile.
- **Nananatiling nabigo ang isang sync.** Basahin ang mensahe sa log ng sync. Karaniwan ang mga nag-expire na kredensyal. Baguhin ang mga value sa screen ng pag-edit ng platform at **I-sync muli**.
- **Lumipat kami ng platform.** Gumawa ng bagong platform at i-off ang Aktibo ng luma. Hiwalay na ginagawa ang mga link bawat platform.
