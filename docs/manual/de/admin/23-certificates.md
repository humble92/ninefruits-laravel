# 23. Bescheinigungen

Menü: **Bescheinigungen > Ausgestellte Bescheinigungen**, **Bescheinigungsarten**, **Einstellungen > Bescheinigungseinstellungen**. Bescheinigungen werden mit der Schaltfläche **Bescheinigung ausstellen** auf dem Bildschirm der Studierendenakte ausgestellt (**Studierende und Personal > Studierendenakten > Anzeigen**). Studierende laden die PDFs ihrer eigenen Bescheinigungen im Portal unter **Bescheinigungen** herunter. Jede/r kann eine Bescheinigung auf der Seite `/verify/{code}` prüfen.

| Berechtigung | Was sie erlaubt |
|---|---|
| `Bescheinigungen ausstellen, neu erzeugen und widerrufen` | Ausstellen, neu erzeugen, widerrufen, die ausgestellten Bescheinigungen einsehen (innerhalb des Geltungsbereichs für das Anzeigen von Studierendenakten) |
| `Bescheinigungsarten, Vorlagen und Einstellungen verwalten` | Arten und ihre Vorlagen pro Sprache, Muster der Seriennummer, Unterzeichnende |

Standardrollen: Bescheinigungsstelle stellt aus, Studierendensekretariat hat beide, Lehrplanung verwaltet Arten und Vorlagen.

## Unterschiede zum Altsystem

- **Register der ausgestellten Bescheinigungen.** Das Altsystem (YUSTIC) druckte direkt vom Bildschirm (`aca3/cti_*`), und sein Register (`SRCERT`) diente nur der Nummernvergabe, sodass es weder zeigte, wer eine Bescheinigung ausgestellt hatte, noch ob sie widerrufen war. Das neue System erfasst bei jeder Ausstellung Seriennummer, Prüfcode, Mitarbeitende/n, Verwendungszweck, Zahl der Exemplare, PDF-Datei und Prüfsumme, und eine widerrufene Bescheinigung behält ihre Zeile.
- **Prüfung.** Das Altsystem hatte keine. Jede Bescheinigung trägt einen Prüfcode und einen QR-Code, und eine öffentliche Seite zeigt, dass sie ausgestellt wurde, ihre Art, das Ausstellungsdatum und die maskierte Inhaberin bzw. den maskierten Inhaber (erster und letzter Buchstabe von Familien- und Vorname, die ersten 4 Ziffern der Matrikelnummer).
- **Vorlagen.** Das Altsystem hatte für jede Art und Sprache ein eigenes PDF-Skript. Das neue System bearbeitet für jede Art auf dem Bildschirm eine Vorlage pro Sprache (Titel, Text, Fußzeile, Unterzeichnende/r).
- **Sperren.** Das Altsystem stellte Bescheinigungen unabhängig von der Liste der Belegungsverweigerungen aus. Das neue System stellt Studierenden mit einer Sperre von Bescheinigungen keine Bescheinigung aus ([Kapitel 20](20-registration.md)).
- Gebühren werden nicht erfasst (das Altsystem erfasste nur den Betrag).
- Es gibt keinen Online-Antrag durch Studierende (Portierungsplan 17.5). Bescheinigungen stellen Mitarbeitende aus.

## Arten und Vorlagen

**Bescheinigungsarten**: Code (wird in der Seriennummer verwendet), Name (3 Sprachen), Zielgruppe (jede Studierendenakte / Studierende / Absolvent/innen), mit Noten, mit Foto, aktiv, Reihenfolge. Das Profil für koreanische Universitäten legt drei Arten an.

| Code | Art | Zielgruppe | Hinweis |
|---|---|---|---|
| `ENR` | Immatrikulationsbescheinigung | Studierende (ohne Abschluss) | |
| `GRD` | Abschlussbescheinigung | Absolvent/innen | Abschlussdatum, Abschlussnummer |
| `TRN` | Notenspiegel | Jede Studierendenakte | Tabelle der endgültigen Noten nach Semester, Gesamtdurchschnitt und erworbene Leistungspunkte |

**Vorlagen** (unten auf dem Bearbeitungsbildschirm der Art): eine pro Sprache. Titel, Text (Absätze durch eine Leerzeile getrennt), Fußzeile, Unterzeichnende/r. Der Text verwendet Platzhalter in geschweiften Klammern.

| Platzhalter | Wert |
|---|---|
| `{institution}` | Name der Hochschule (in dieser Sprache) |
| `{student_name}`, `{student_number}` | Name (in der Form dieser Sprache, sonst in lateinischer Schreibweise), Matrikelnummer |
| `{program}`, `{org_unit}` | Studiengang, Organisationseinheit |
| `{admission_date}`, `{graduation_date}`, `{degree_number}` | Aufnahmedatum, Abschlussdatum, Abschlussnummer |
| `{status}`, `{year_level}` | Studierendenstatus, Fachsemester |
| `{birth_date}`, `{gender}`, `{nationality}` | Geburtsdatum, Geschlecht, Staatsangehörigkeit |
| `{issue_date}`, `{serial_number}`, `{purpose}`, `{copies}` | Ausstellungsdatum, Seriennummer, Verwendungszweck, Zahl der Exemplare |

Datumsangaben werden im Format der jeweiligen Sprache gedruckt. Ein fehlender Wert (z. B. das Abschlussdatum) bleibt leer. Eine Tabelle mit persönlichen Angaben (Name, Matrikelnummer, Geburtsdatum, Geschlecht, Staatsangehörigkeit, Studiengang, Aufnahmedatum, danach bei Absolvent/innen das Abschlussdatum, sonst Status und Fachsemester) wird automatisch über dem Text eingefügt.

## Einstellungen

**Einstellungen > Bescheinigungseinstellungen**

- **Muster der Seriennummer**: Standard `{TYPE}-{YYYY}-{SEQ:6}` → `ENR-2026-000001`. `{SEQ:n}` ist Pflicht. Die laufende Nummer zählt für jedes unterschiedliche Präfix getrennt. `{TYPE}-{SEQ:6}` zählt pro Art über die Jahre hinweg, `{YYYY}{SEQ:5}` pro Jahr über die Arten hinweg. Gleichzeitige Ausstellungen kollidieren nie (der Zähler wird in der Datenbank atomar erhöht).
- **Prüfung gültig für (Tage)**: 0 bedeutet immer. Nach Ablauf des Zeitraums antwortet die Prüfseite mit „abgelaufen“. Die Ausstellung selbst bleibt im Register.
- **Unterzeichnende je Sprache**: der über dem Siegel gedruckte Name („Präsident/in, Ninefruits University“). Ein/e Unterzeichnende/r in der Vorlage hat Vorrang.

## Ausstellen

**Bescheinigung ausstellen** auf dem Bildschirm der Studierendenakte: Art, Sprache, Verwendungszweck, Exemplare (1 bis 20). Beim Speichern öffnet sich das PDF in einem neuen Tab.

Die Ausstellung wird abgelehnt, wenn:

- die Zielgruppe der Art nicht passt (eine Immatrikulationsbescheinigung für Absolvent/innen, eine Abschlussbescheinigung für aktuell Studierende)
- die Art inaktiv ist
- es für diese Sprache keine Vorlage gibt
- für die/den Studierende/n eine **Sperre von Bescheinigungen** gilt (eine Sperre, [Kapitel 20](20-registration.md)); heben Sie sie zuerst auf
- die Studierendenakte außerhalb des Geltungsbereichs für das Anzeigen liegt

Die Ausstellung wird im Aktivitätsprotokoll erfasst (`certificate.issued`: Matrikelnummer, Art, Sprache, Seriennummer, Exemplare, Verwendungszweck).

## Ausgestellte Bescheinigungen

**Bescheinigungen > Ausgestellte Bescheinigungen**: Ausstellungsdatum, Seriennummer, Art und Sprache, Matrikelnummer, Name, Verwendungszweck, Exemplare, Status, Mitarbeitende/r. Filtern nach Art und Status. Der Geltungsbereich für das Anzeigen ist derselbe wie bei Studierendenakten.

- **PDF**: öffnet die gespeicherte Datei. Sie wird nicht neu erzeugt und entspricht daher genau der Ausstellung.
- **Prüfseite**: öffnet die öffentliche Seite.
- **Neu erzeugen**: erzeugt aus den aktuellen Daten ein neues PDF, z. B. nach der Korrektur einer Vorlage, und ersetzt die Datei. Seriennummer und Prüfcode bleiben erhalten; die Prüfsumme ändert sich. Protokollereignis `certificate.rerendered`. Entspricht dem Befehl `certificate:render`.
- **Widerrufen**: Begründung erforderlich. Zeile und Datei bleiben erhalten, der Status wird zu widerrufen, und die Prüfseite antwortet mit „widerrufen“. Protokollereignis `certificate.revoked`. Eine widerrufene Bescheinigung wird nicht wiederhergestellt; stattdessen wird eine neue ausgestellt.

Zeilen des Registers können auf dem Bildschirm weder bearbeitet noch gelöscht werden.

## Prüfung

- Die Prüf-URL (`/verify/{code}`) und ein QR-Code werden unten auf die Bescheinigung gedruckt. Der Code ist eine zufällige Zeichenfolge aus 20 Zeichen.
- Die Seite öffnet sich ohne Anmeldung und ist auf 30 Anfragen pro Minute begrenzt. Sie zeigt, ob die Bescheinigung ausgestellt wurde, ihre Art, Seriennummer, das Ausstellungsdatum sowie den maskierten Namen und die maskierte Matrikelnummer. Widerrufene, abgelaufene und unbekannte Codes erhalten jeweils eine andere Antwort.
- Um eine eingereichte PDF-Datei selbst zu prüfen, vergleichen Sie die Prüfsumme (SHA-256) im Register mit dem Hash der Datei. Der Befehl `certificate:verify {code}` erledigt dies ebenfalls.

## Portal der Studierenden

**Bescheinigungen**: die auf die eigene Studierendenakte ausgestellten Bescheinigungen mit PDFs. Widerrufene werden mit ihrem Status aufgeführt, die/der Studierende kann das PDF einer widerrufenen Bescheinigung aber nicht herunterladen (die ausstellenden Mitarbeitenden können es zur Kontrolle des Vorgangs öffnen). Dateien anderer Studierender können nicht geöffnet werden (in der Anwendung und in der Datenbank erzwungen).

## Häufige Fragen

- **Der Name ist falsch geschrieben.** Korrigieren Sie die Namensvariante der Person (in dieser Sprache) und klicken Sie auf **Neu erzeugen**. Wurde die Bescheinigung bereits eingereicht, widerrufen Sie sie und stellen Sie eine neue aus.
- **Ich habe die Vorlage geändert; was ist mit bereits ausgestellten Bescheinigungen?** Sie ändern sich nicht. Bei Bedarf **Neu erzeugen**.
- **Eine Seriennummer wurde übersprungen.** Tritt bei der Ausstellung ein Fehler auf (z. B. wenn der PDF-Dienst ausgefallen ist), ist die Nummer verbraucht, und es bleibt möglicherweise keine Registerzeile zurück. Nummern sind garantiert eindeutig, aber nicht lückenlos.
