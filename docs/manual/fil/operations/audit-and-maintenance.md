# Integridad ng audit at regular na maintenance

## Hash chain ng audit

Ang mga table ng audit (`audit.record_changes` para sa mga pagbabago ng datos, `audit.activity_log` para sa aktibidad ng operasyon, `audit.security_events` para sa mga pangyayaring pangseguridad) ay append-only (ipinapatupad ng mga trigger). Bukod dito, may mekanismong **naglalantad** ng anumang pakikialam.

- Bawat row ay binibigyan ng SHA-256 ng nilalaman nito (`row_hash`) kapag ipinasok.
- Ang `audit:seal` (naka-iskedyul araw-araw nang 03:30) ay nag-uugnay ng mga bagong row sa chain ayon sa pagkakasunod-sunod ng ID: `chain_hash = SHA-256(previous chain_hash || row_hash)`.
- Kinukuwenta muli ng `audit:verify-chain` ang hash ng nilalaman at ang chain para sa bawat row. Ang row na nagbago ang nilalaman ay iniuulat bilang "row mismatch", ang nawawala o napalitan ng pagkakasunod-sunod na row bilang "chain mismatch", at nagbabalik ang command ng exit code 2.

Mga dapat tandaan:

- Ang row na binago bago ito na-seal (sa loob ng parehong araw) ay hindi mahuhuli ng chain. Kaya araw-araw tumatakbo ang pag-seal, at tumatakbo ang beripikasyon bago ang mga backup at sa mga pagsusuring pangseguridad.
- Kapag inalis ng pruning (`audit:prune`) ang pinakalumang mga row, hindi sinusuri ang link ng unang natitirang row (iniingatan ang inalis na saklaw sa rekord ng audit ng prune command).
- Ang DB administrator (may-ari ng schema) lamang ang makapagtatakda ng `app.audit_maintenance = on`. Walang pribilehiyong UPDATE o DELETE ang role ng application.

## Pagpapanatili at pruning

1. Pagpasyahan ang mga panahon ng pagpapanatili (halimbawa, 3 taon para sa mga pagbabago ng datos, 5 taon para sa aktibidad ng operasyon, 1 taon para sa mga pangyayaring pangseguridad).
2. Mag-back up bago mag-prune ([Backup at restore](backup-restore.md)) at kumpirmahing buo ang chain gamit ang `audit:verify-chain`.
3. Suriin ang bilang gamit ang `audit:prune --before=YYYY-MM-DD --table=record_changes --dry-run`, saka patakbuhin ito nang walang `--dry-run`. Hindi kailanman binubura ang mga row na hindi pa na-seal, kaya patakbuhin muna ang `audit:seal`.
4. Patakbuhing muli ang `audit:verify-chain` pagkatapos ng pruning.

Itinatala rin ang pruning mismo: bawat pagpapatakbo ay nagsusulat sa log ng aktibidad (log na `command`) ng petsang hangganan, mga option, actor at OS user, at para sa bawat table ang **bilang ng mga row na binura, ang saklaw ng mga binurang ID at ang chain_hash sa hangganan**; ang aktuwal na pagbura ay itinatala rin bilang pangyayaring pangseguridad na `audit.pruned` (**Audit > Mga pangyayaring pangseguridad**). Ang row ng rekord na ito ay iniuugnay rin sa chain sa susunod na pag-seal. Ang hangganang ito mismo ang dahilan kung bakit hindi sinusuri ng `audit:verify-chain` ang link ng unang natitirang row; sa paghahambing ng hash sa hangganan at ng rekord ng prune, makukumpirma mong tumutugma ang binurang saklaw sa isang naitalang prune.

## Paglilinis ng mga file

Inililista ng `media:prune-orphans --dry-run` ang mga media row na wala nang may-ari at ang mga directory na walang row; kapag walang `--dry-run`, binubura nito ang mga ito. Saklaw nito ang mga larawan ng tao, mga dokumento ng aplikasyon, mga PDF ng sertipiko at ebidensiya ng pagsusulit. Patakbuhin ito buwan-buwan o pagkatapos ng maramihang pagbura.

## Health check

Sinusuri ng `system:health-check` ang configuration at mga key, ang mga role ng DB at RLS, ang mga migration, ang katalogo ng pahintulot, ang Redis at ang queue (Horizon), ang Gotenberg, ang storage at ang heartbeat ng scheduler (isinusulat sa cache bawat minuto), at nagbabalik ng exit code 2 kapag may mali. Gamitin ang `--json` upang ikonekta ito sa monitoring.
