# Adding a language

Korean 한국어: [adding-a-language.ko.md](adding-a-language.ko.md)

Ninefruits ships in Korean, English, Simplified Chinese and Lao. Nothing in the code lists the languages: a language exists when `lang/{locale}/` exists and the locale is enabled in the institution settings. This page is the whole workflow for adding one more, written for a developer or a translator who has never worked on the project. Budget: two to four days for the UI strings and the manual with machine-assisted translation, plus a native review.

## 0. Pick the locale code

- Use the ISO 639-1 code (`lo`, `vi`, `th`), with `_REGION` only when the script or spelling differs by region (`zh_CN`, `pt_BR`). The code is the directory name under `lang/` and the value stored in `users.locale`.
- Check that ICU knows it: `php -r 'echo Locale::getDisplayName("lo", "lo");'` must print the language name in that language (it is shown in the language menu). Carbon (dates) and `Illuminate\Support\Number` (numbers) use the same code.

## 1. UI strings (`lang/{locale}/`)

The English files in `lang/en/` are the source; every other language must have the same files and keys. Two ways to produce them.

**With the translator JSON (recommended for a translator who does not edit PHP)**

```bash
mkdir -p lang/lo
./vendor/bin/sail artisan i18n:export lo storage/app/lo.json
# send storage/app/lo.json to the translator: {"group.key": {"source": "English", "translation": ""}}
./vendor/bin/sail artisan i18n:import lo storage/app/lo.json --dry-run   # lists what would be written
./vendor/bin/sail artisan i18n:import lo storage/app/lo.json
```

`i18n:import` writes `lang/lo/*.php`, keeps only keys that exist in English and skips empty translations. Re-export with `--missing` to get a file of what is still untranslated.

**Directly in PHP**: copy each `lang/en/*.php` to `lang/lo/` and translate the values. Keep keys, placeholders (`:name`, `:count`), pluralization (`one|many`, `{0} none|[1,*] some`) and HTML untouched.

Then:

- `lang/{locale}.json`: strings of Laravel and Filament themselves (validation defaults, "Save", pagination). Copy `lang/ko.json`, translate the values. The keys are the English strings.
- Filament's own panel strings (Dashboard, Search, Sign out, table pagination, action buttons) come from the Filament packages, which ship many languages but not all. If the admin panel still shows English words in the new language, publish and translate them under `lang/vendor/filament*/{locale}/` (`php artisan vendor:publish --tag=filament-translations`, then translate the published files). Lao does not have them yet.
- Check: `./vendor/bin/sail artisan i18n:missing lo --strict` must report `0 missing`. CI runs this for every directory under `lang/`, so a new language cannot be merged half done.
- Glossary: `docs/development/naming.md` section 6 lists the domain terms (student record, term, section, enrollment …). Decide the translation of each term once, before translating, and write it down for the manual.

## 2. Enable the locale

- For one installation: **Institution settings > Supported languages** in the admin panel (or `institution.supported_locales` in the settings table). Users then see the language in the user menu and `/locale` accepts it. Browser language negotiation also picks it up.
- When the language ships with the application, add a settings migration so new installs get it by default. Example: `database/settings/2026_10_23_008010_add_lao_locale.php` appends `lo` when the list is still the shipped default.

## 3. Seeded names (optional, recommended)

Reference data created by `setup:install` has translatable names stored as JSON per locale, for example `['en' => 'University', 'ko' => '대학교', 'zh_CN' => '大学', 'lo' => 'ມະຫາວິທະຍາໄລ']`. A missing locale falls back to English, so this step is not required, but a university using the language will want its own words for:

- `database/seeders/Essential/`: org unit types, role templates, status change types, lookup types.
- `database/seeders/Profiles/`: the rule sets (course categories, grade scales, requirement names).

Countries and languages (`CountrySeeder`, `LanguageSeeder`) get their names from ICU for every installed language automatically. Existing installations get the new names on the next deploy because the essential seeders run again and only fill locales that are empty.

## 4. Documents

| What | Where | Notes |
|---|---|---|
| Administrator manual | `docs/manual/{locale}/admin/` | 28 chapters plus `README.md` (chapter index). Same file names as `docs/manual/ko/admin/` so links work across languages |
| Operations manual | `docs/manual/{locale}/operations/` | 9 files. `commands.md` keeps every `### \`command\`` heading and option name; only descriptions are translated |
| Manual index | `docs/manual/{locale}/README.md` | Title, audience, links to the two parts |
| Project README | `README.{locale}.md` | Same sections as `README.md`; demo accounts and commands unchanged |
| Language table | Top of every `README*.md` | One row per language: README link and manual directory. Add the new row to all of them |
| Screenshots | `docs/screenshots/{locale}/{admin,instructor,student}.png` | See below |

Korean is the source of the manuals (`docs/manual/ko/`). Screen names in bold must match the UI labels of that language, so finish step 1 before the manual.

**Screenshots** of the three first screens come from the demo data (never from real data):

```bash
./vendor/bin/sail artisan migrate:fresh --force
./vendor/bin/sail artisan setup:install --force --profile=korean-university --grade-scale=4.5
./vendor/bin/sail artisan setup:demo --size=small --seed=42
python3 tools/screenshots.py lo docs/screenshots/lo
```

The script signs in as the demo system administrator, instructor and student, switches the account language and takes the pictures through the Gotenberg container (`/forms/chromium/screenshot/url`).

## 5. PDFs and fonts

Certificates, transcripts and attendance sheets are rendered by Gotenberg (Chromium). Its image ships the Noto fonts, which cover Lao, Thai, Khmer, Arabic, Devanagari and CJK. For a script not covered, add the font to the Gotenberg image (`compose.yaml`) and to the PDF stylesheet in `resources/views/pdf/`. `./vendor/bin/sail artisan pdf:sample` renders a mixed-script table to check the result.

## 6. Demo data (optional)

`database/factories/Person/PersonFactory.php` mixes Faker locales (`en_US`, `ko_KR`, `zh_CN`) for names and stores a name variant per script. Add a Faker locale when one exists for the language and names in that script matter for demonstrations.

## 7. Checklist before the pull request

- [ ] `lang/{locale}/` complete: `i18n:missing {locale} --strict` reports 0 missing
- [ ] `lang/{locale}.json` present
- [ ] Settings migration (if shipped) or institution settings updated
- [ ] Seeded names translated (or consciously left to fall back to English)
- [ ] `docs/manual/{locale}/` (admin 28 chapters + index, operations 9 files, index) and `README.{locale}.md`
- [ ] Language table updated in every `README*.md`
- [ ] Screenshots in `docs/screenshots/{locale}/`
- [ ] `./vendor/bin/sail composer check` passes (Pint, PHPStan, naming, translations, tests)
- [ ] A native speaker reviewed the UI strings and at least the first manual chapters; note the reviewer in the pull request

Lao was added on 2026-10-23 as the first language after the original three; its pull request is the worked example.
