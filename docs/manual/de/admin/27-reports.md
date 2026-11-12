# 27. Berichte und reporting-Views

Menü: **Berichte > Berichte** (Berechtigung `Standardberichte anzeigen und herunterladen`). Standardrollen: Studierendensekretariat, Lehrplanung, Institutssekretariat (im Geltungsbereich).

## Standardberichte

Wählen Sie einen Bericht und seine Filter: Bis zu 500 Zeilen werden auf dem Bildschirm angezeigt, und **CSV herunterladen** liefert alle (UTF-8 mit BOM, lässt sich direkt in Excel öffnen). Downloads werden im Protokollereignis `reports.exported` erfasst. Werte, die mit `=`, `+`, `-` oder `@` beginnen, werden mit einem vorangestellten einfachen Anführungszeichen (`'`) exportiert, damit Excel sie nicht als Formeln ausführt.

| Bericht | Filter | Inhalt |
|---|---|---|
| Studierendenliste | Organisationseinheit (einschließlich untergeordneter Einheiten), Status, Fachsemester | Matrikelnummer, Name, Studiengang, Organisationseinheit, Fachsemester, Status, Aufnahmedatum, Abschlussdatum, Abschlussnummer |
| Studierendenzahl nach Einheit, Studiengang, Studienjahr und Status | Organisationseinheit, Status | Zahl der Studierenden |
| Semesterübersicht der Noten | Semester, Studienstatus | Leistungspunkte und Notendurchschnitt des Semesters und kumuliert, Zahl der Studienwarnungen, Rang der Auszeichnung ([Kapitel 21](21-grades.md)) |
| Abschlusskandidat/innen | Erfüllt oder nicht | Neueste Prüfung der Abschlussvoraussetzungen pro Studierende/n und Studiengang ([Kapitel 26](26-graduation.md)) |
| Statusänderungen | Semester, Zeitraum | Art der Änderung, Status vorher und nachher, Wirksamkeitsdatum, Antragsstatus |
| Teilnehmende je Kurs | Semester, Organisationseinheit | Kurs, Lehrpersonen, Kapazität, eingeschrieben, wartend |
| Studierende ohne Belegung in einem Semester | Semester, Organisationseinheit | Eingeschriebene Studierende ohne Belegung in diesem Semester |

Was sichtbar ist, richtet sich nach dem Geltungsbereich der Berechtigungen der betrachtenden Person (Geltungsbereich für das Anzeigen von Studierendenakten u. Ä.). Die Gebühren- und Studiengebührenberichte des Altsystems (YUSTIC) (unbezahlte Studiengebühren u. Ä.) liegen außerhalb des Umfangs und existieren nicht.

## reporting-Views

Berichte lesen die Views des Schemas `reporting` in der Datenbank. BI-Werkzeuge und SQL-Benutzer fragen dieselben Views mit der schreibgeschützten Rolle `app_reporting` ab.

| View | Inhalt |
|---|---|
| `reporting.students` | Studierendenakte + Name der Person + Namen von Studiengang und Organisationseinheit |
| `reporting.headcount` | Studierendenzahl nach Organisationseinheit, Studiengang, Fachsemester und Status |
| `reporting.term_records` | Notenübersicht pro Semester |
| `reporting.course_grades` | Noten pro Lehrveranstaltung (endgültig oder nicht, Note, bestanden, Wiederholung) |
| `reporting.section_enrollments` | Eingeschriebene und Wartende pro Kurs, Lehrpersonen |
| `reporting.section_meetings` | Kurszeiten und Räume |
| `reporting.status_changes` | Statusänderungen |
| `reporting.degree_audits` | Neueste Prüfung der Abschlussvoraussetzungen pro Studierende/n und Rolle im Studium |

- Übersetzte Spalten (`program_name` u. Ä.) richten sich nach der Locale der Sitzung: `SET app.locale = 'ko';`. Die rohen jsonb-Spalten (`program_name_i18n` u. Ä.) stehen ebenfalls zur Verfügung.
- Die Views sind `security_invoker`, daher gilt die Row Level Security der abfragenden Person unverändert. Mit dem Konto einer/eines Studierenden gelesen, liefern sie nur deren/dessen Zeilen.
- Die View-Definitionen liegen in `database/sql/views/reporting.sql`; nach einer Änderung werden sie über eine Migration erneut angewendet.

Die alten `AV_*`-Views (etwa 90) wurden nicht genutzt und nicht portiert.
