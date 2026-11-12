# 25. Lehrbereich und Anwesenheit

Menü: Lehrpersonen verwenden im Portal **Meine Lehre > Teilnehmerliste und Anwesenheit**, Studierende im Portal **Anwesenheit**. Mitarbeitende verwenden **Kurstermine** unten auf dem Bildschirm **Lehrangebot und Kurse > Kurse > Bearbeiten**.

| Wer | Was möglich ist |
|---|---|
| Zugewiesene Lehrpersonen | Teilnehmerliste (mit Fotos) und CSV, Anwesenheitsliste als PDF, Erzeugen, Hinzufügen und Absagen von Kursterminen, Eintragen der Anwesenheit |
| Mitarbeitende mit `Kurse planen` (im Geltungsbereich) | Wie Lehrpersonen + Kurstermine in der Verwaltungsoberfläche |
| Mitarbeitende mit `Kurse anzeigen` (im Geltungsbereich) | Teilnehmerliste, Anwesenheitsliste, Einsicht in die Anwesenheit |
| Studierende | Ihre eigene Anwesenheit |

Funktionen des Lehrbetriebs wie Ankündigungen, Aufgaben und Materialien bleiben dem LMS überlassen (Portierungsplan Abschnitt 12).

## Unterschiede zum Altsystem

- **Das Altsystem (YUSTIC) erfasste keine Anwesenheit.** Seine „Anwesenheitsliste“ war ein PDF, das neben der Teilnehmerliste leere Kästchen druckte (27 bis 34, je nach Exemplar unterschiedlich), ohne Daten und ohne Summen. Das neue System erzeugt **Kurstermine** aus den Kurszeiten, erfasst pro Termin anwesend, verspätet, abwesend oder entschuldigt und druckt die Einträge und Summen auf die Anwesenheitsliste als PDF.
- **Teilnehmerliste.** Der Bildschirm für Lehrpersonen im Altsystem zeigte nur Matrikelnummern und Namen (und legte in einem Pop-up die Nummer des Personalausweises und die Adresse offen) und bot keinen Excel-Download. Das neue System zeigt nur Matrikelnummer, Name, Foto, Studiengang, Fachsemester und Kontakt-E-Mail und bietet einen Download als CSV. Downloads werden im Protokoll erfasst.
- **Zugriff der Lehrpersonen auf ihre Studierenden.** Eine RLS-Policy wurde hinzugefügt, damit Lehrpersonen die Studierendenakten und Personen der Studierenden in ihren Kursen sehen können (vorher sahen sie nur ihre Mentees, wodurch selbst auf dem Bildschirm der Noteneingabe die Namen leer blieben).

## Kurstermine

- **Erzeugen**: ein Termin für jedes Auftreten der wöchentlichen Kurszeiten (Wochentag, Uhrzeit, Zeitblock). Lehrpersonen verwenden die Schaltfläche **Aus Kurszeiten erzeugen**; Mitarbeitende verwenden in der Verwaltungsoberfläche **Kurstermine > Termine erzeugen** oder den Befehl `attendance:generate-sessions {term}` (ganzes Semester). Bestehende Termine und Anwesenheiten bleiben unberührt, sodass der Vorgang nach einer Änderung der Kurszeiten erneut ausgeführt werden kann.
- **Termin hinzufügen**: eine Veranstaltung außerhalb der Kurszeiten, etwa ein Nachholtermin. Nur Daten innerhalb des Kurszeitraums.
- **Absagen**: sagt den Termin mit Begründung ab. Der Termin bleibt bestehen, wird aber bei den Summen nicht berücksichtigt. **Wiederherstellen** holt ihn zurück.
- Es gibt keinen Feiertagskalender. Sagen Sie die Termine an gesetzlichen Feiertagen ab (siehe Abschnitt 13.2; dies geschieht automatisch, sobald ein Semesterkalender hinzugefügt wird).

## Anwesenheit eintragen

Wählen Sie unter **Anwesenheit** des Termins für jede/n Studierende/n anwesend, verspätet, abwesend oder entschuldigt und fügen Sie eine Anmerkung hinzu (200 Zeichen). Am schnellsten geht es mit **Alle anwesend** und anschließendem Ändern der Ausnahmen.

- Die Anwesenheit kann ab dem Tag des Termins eingetragen werden (nicht für zukünftige Termine).
- Erneutes Speichern erfasst nur die geänderten Studierenden, und das Protokollereignis (`attendance.recorded`) hält alte und neue Werte fest.
- Das Leeren des Status setzt die/den Studierende/n auf „nicht erfasst“ zurück.

## Summen

| Element | Definition |
|---|---|
| Stattgefundene Termine | Termine, die nicht abgesagt sind und am oder vor dem heutigen Tag liegen |
| Anwesenheitsquote | (anwesend + verspätet + entschuldigt) ÷ stattgefundene Termine × 100 |
| Nicht erfasst | Stattgefundene Termine ohne Eintrag |

Es gibt keine Regel, die eine bestimmte Zahl von Verspätungen in eine Abwesenheit umwandelt oder ab einem Drittel Abwesenheiten ein F vergibt. Dies unterscheidet sich zwischen Hochschulen, daher berücksichtigen Lehrpersonen die Anwesenheit selbst in der Note (Prüfpunkt 13.5).

## Anwesenheitsliste als PDF und Teilnehmerliste als CSV

Beide Downloads werden im Protokoll erfasst (`attendance.sheet_exported`; die Teilnehmerliste als CSV wie bisher). CSV-Werte, die mit `=`, `+`, `-` oder `@` beginnen, werden mit einem vorangestellten einfachen Anführungszeichen exportiert, damit sie nicht als Formeln ausgeführt werden.

- **Anwesenheitsliste (PDF)**: A4 quer. Studierende untereinander, Termine nebeneinander (abgesagte ausgenommen), Markierungen O/△/X/E und Summen pro Studierende/n. Noch ausstehende Termine bleiben leer, sodass die Liste auch zum handschriftlichen Erfassen dient.
- **Teilnehmerliste (CSV)**: Matrikelnummer, Name, Studiengang, Fachsemester, Kontakt-E-Mail, Belegungsstatus. UTF-8 mit BOM, sodass sie sich direkt in Excel öffnen lässt. Jeder Download wird im Protokollereignis `attendance.roster_exported` erfasst.

## Portal der Studierenden

**Anwesenheit**: pro Semester und Lehrveranstaltung die Zahlen für anwesend, verspätet, abwesend und entschuldigt, die Anwesenheitsquote und der Eintrag pro Termin (nur was die Lehrperson eingetragen hat).

## Betriebsablauf

1. Legen Sie vor Semesterbeginn die Kurszeiten der Kurse endgültig fest ([Kapitel 18](18-sections-and-timetables.md)).
2. Erzeugen Sie die Termine mit `php artisan attendance:generate-sessions {term} --actor=...` (Lehrpersonen können sie auch auf dem Bildschirm erzeugen).
3. Führen Sie den Befehl erneut aus, wenn sich Kurszeiten ändern. Termine entfernter Zeiten werden nicht automatisch gelöscht; sagen Sie sie ab.
