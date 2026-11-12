# 9. CSV-Import

Verwenden Sie die Schaltfläche **CSV importieren** oben auf den Bildschirmen für Organisationseinheiten, Studiengänge, Gebäude, Räume und Codelisten (Berechtigung `Stammdaten aus CSV importieren`). Die Listen der Studierendenakten und der Lehrpersonen haben dieselbe Schaltfläche (Berechtigungen `Studierendenakten anlegen und bearbeiten` bzw. `Lehrpersonen verwalten`). Sehr große Dateien kann der IT-Betrieb auf dieselbe Weise mit dem Befehl `reference:import` importieren.

## Allgemeine Regeln

- Die erste Zeile enthält die Spaltennamen (Tabellen unten). Die Kodierung ist UTF-8; speichern Sie in Excel als „CSV UTF-8“.
- Eine Zeile, deren Code bereits existiert, **aktualisiert** diesen Datensatz. Wird dieselbe Datei erneut importiert, entstehen keine Duplikate.
- Sprachabhängige Spalten haben die Form `spalte_sprache`: `name_en`, `name_ko`, `name_zh_CN`. Neue Zeilen erfordern den englischen Namen (`name_en`). Leere Zellen löschen keine vorhandenen Übersetzungen.
- Datumsangaben werden als `2026-03-01` geschrieben, Ja/Nein als `1`/`0` oder `true`/`false`.
- Importe laufen im Hintergrund. Nach Abschluss erscheint das Ergebnis als Benachrichtigung oben rechts in der Verwaltungsoberfläche; sind Zeilen fehlgeschlagen, liefert **Informationen über die fehlgeschlagenen Zeilen herunterladen** eine CSV-Datei mit dem Grund je Zeile. Korrigieren Sie sie und importieren Sie nur diese Datei erneut.
- Weichen die Spaltennamen ab, können Sie die Spalten im Importdialog von Hand zuordnen.

## Spaltenformate

| Art | Pflichtspalten | Optionale Spalten |
|---|---|---|
| Organisationseinheiten | `code`, `type` (Code der Art der Organisationseinheit), `name_en`, `effective_start_date` | `parent` (Code der übergeordneten Einheit), `name_ko` usw., `short_name_*`, `effective_end_date`, `sort_order` |
| Studiengänge | `code`, `org_unit` (Code der Organisationseinheit), `degree_level`, `name_en`, `effective_start_date` | `degree_title_*`, `standard_duration_years`, `effective_end_date`, `allows_second_major`, `allows_minor` |
| Gebäude | `code`, `name_en` | `name_*`, `address`, `is_active` |
| Räume | `building` (Gebäudecode), `code` (Raumnummer) | `name_*`, `room_type` (Code der Raumart), `org_unit`, `capacity`, `is_schedulable`, `is_active` |
| Codes | `type` (Listencode), `code`, `name_en` | `name_*`, `sort_order`, `is_active` |

Werte für `degree_level`: `associate`, `bachelor`, `master`, `doctorate`, `certificate`, `non_degree`

Bei Organisationseinheiten müssen übergeordnete Einheiten zuerst kommen (sortiert als Universität → Fakultät → Institut).

## Beispiel: Organisationseinheiten

```csv
code,type,parent,name_en,name_ko,effective_start_date
UNI,university,,Sample University,샘플대학교,1990-03-01
ENG,college,UNI,College of Engineering,공과대학,1990-03-01
CSE,department,ENG,Computer Science,컴퓨터공학과,1995-03-01
```

Importe für Studierende und Lehrpersonen (Phase 2) und Lehrveranstaltungen (Phase 4) kommen zusammen mit diesen Funktionen hinzu.

## Studierende und Lehrpersonen

Studierendenakten werden über `student_number` zugeordnet und aktualisiert, Lehrpersonen über `employee_number`. Beide Arten teilen sich die Personenspalten.

| Art | Pflichtspalten für neue Zeilen | Optionale Spalten |
|---|---|---|
| Studierendenakten | `student_number`, `program` (Code des Studiengangs), `admission_date`, `family_name` | `catalog_year` (bei leerem Wert das Zulassungsjahr), `year_level`, `status`, `graduation_date`, `institutional_email` |
| Lehrpersonen | `employee_number`, `org_unit` (Code der Organisationseinheit), `employment_type`, `family_name` | `academic_title_*`, `hire_date`, `end_date` |
| Personenspalten (gemeinsam) | — | `given_name`, sprachabhängige Namen wie `name_ko`, `birth_date`, `gender`, `email`, `phone`, `national_id` |

- Werte für `status`: `enrolled`, `on_leave`, `withdrawn`, `dismissed`, `graduated` (für `graduated` ist `graduation_date` Pflicht). Ein per Import geänderter Status gilt als **Datenkorrektur** und erzeugt keinen Verlauf der Statusänderungen. Echte Änderungen werden auf dem Bildschirm erfasst ([Kapitel 13](13-status-changes-and-advisors.md)).
- Werte für `employment_type`: `full_time`, `part_time`, `adjunct`, `visiting`, `emeritus`. Werte für `gender`: `female`, `male`, `other`, `undisclosed`.
- Stimmt die `national_id` einer neuen Studierendenakte mit einer vorhandenen Person überein, wird die Akte dieser Person zugeordnet (Wiederaufnahme). Andernfalls wird eine neue Person angelegt. Bei Aktualisierungen löschen leere Zellen keine vorhandenen Werte.
- Importierte Studierende und Lehrpersonen haben kein Konto. Senden Sie ihnen eine Einladung über die [Kontoeinladungen](14-merge-and-invitations.md#kontoeinladungen).

```csv
student_number,program,admission_date,year_level,status,family_name,given_name,name_ko,birth_date
202600001,B-CSE,2026-03-02,1,enrolled,Kim,Minsu,김민수,2007-05-01
```

## Lehrveranstaltungen

**CSV importieren** oben in der Liste der Lehrveranstaltungen (Berechtigung `Lehrveranstaltungen, Versionen und Voraussetzungen verwalten`) oder `reference:import courses`. Eine Zeile entspricht einer Version einer Lehrveranstaltung.

| Pflichtspalten | Optionale Spalten |
|---|---|
| `code`, `effective_start_date`, `title_en`, `credits` (neue Version) | `org_unit` (Pflicht bei einer neuen Lehrveranstaltung; Code der verwaltenden Organisationseinheit), `effective_end_date`, `title_*`, `lecture_hours`, `lab_hours`, `category` (Code der Veranstaltungskategorie), `grading_basis` (`graded`, `pass_fail`), `counts_toward_credit_limit` |

- Lehrveranstaltungen werden über `code` zugeordnet, Versionen über `code` + `effective_start_date`. Ein erneuter Import derselben Datei aktualisiert sie.
- Ein Überarbeitungsverlauf kann aus einer Datei geladen werden: Führen Sie die Zeilen desselben Codes je Zeitraum auf (die frühere Version erhält ein `effective_end_date`).
- Eine Zeile, deren Zeitraum sich mit einer anderen Version überschneidet, schlägt fehl. Schlägt die Zeile einer neuen Lehrveranstaltung fehl, wird auch die Lehrveranstaltung nicht angelegt.
- Voraussetzungen, äquivalente Lehrveranstaltungen und Abschlussvoraussetzungen werden auf dem Bildschirm eingegeben ([Kapitel 17](17-courses-and-curriculum.md)).

```csv
code,org_unit,effective_start_date,effective_end_date,title_en,title_ko,credits,category
CSE2010,CSE,2015-03-01,2026-02-28,Data Structures,자료구조,3,MAJ_REQ
CSE2010,,2026-03-01,,Data Structures,자료구조,4,MAJ_REQ
```

## Belegungssperren

**CSV importieren** oben in der Liste **Belegung > Sperren** (Berechtigung `Sperren setzen und aufheben, Regelausnahmen gewähren`) oder `reference:import registration_holds`. Dient zum Laden der Altdaten aus `SRDENY` oder der Liste der Studierenden mit offenen Gebühren aus der Finanzabteilung.

| Pflichtspalten | Optionale Spalten |
|---|---|
| `student_number`, `hold_type` (`unregistered`, `financial`, `academic`, `administrative`, `other`), `reason` | `starts_at` (Standard: Zeitpunkt des Imports), `ends_at`, `blocks_registration` (Standard 1), `blocks_grades`, `blocks_certificates` |

- Jede Zeile erzeugt eine neue Sperre (Quelle `import`). Wird dieselbe Datei zweimal importiert, entstehen die Sperren doppelt; prüfen Sie daher die Liste, bevor Sie erneut importieren.
- Zeilen mit unbekannter Matrikelnummer schlagen fehl. Sperren werden auf dem Bildschirm mit einem Grund aufgehoben ([Kapitel 20](20-registration.md)).

```csv
student_number,hold_type,reason,ends_at
202312345,financial,2027-1 등록금 미납,
202254321,academic,학사경고 상담 필요,2027-03-15
```
