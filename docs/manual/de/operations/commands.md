# Artisan-Befehlsreferenz

- Zielgruppe: IT-Betrieb
- Umfang: alle in diesem Repository registrierten Anwendungsbefehle. Wird ein Befehl hinzugefügt, muss er auch hier ergänzt werden, sonst schlägt die CI fehl (`tests/Feature/Tooling/CommandDocumentationTest.php`).

## Allgemeine Konventionen

Jeder Befehl wird als `php artisan {command}` ausgeführt. In der Entwicklungsumgebung `./vendor/bin/sail artisan {command}`.

| Punkt | Regel |
|---|---|
| Protokolleintrag | Befehle, die Daten ändern (unten „Protokoll: wird aufgezeichnet“), schreiben bei jedem Lauf Befehl, Argumente, Optionen, Betriebssystembenutzer, Exit-Code und Ergebnis in das Aktivitätsprotokoll (Log `command`). Einsehbar unter **Protokoll > Aktivitätsprotokoll** in der Verwaltungsoberfläche |
| Lauf-ID | Jeder Lauf erhält anstelle der Request-ID eine ID, die mit `cmd-` beginnt. Das Aktivitätsprotokoll, die von diesem Lauf vorgenommenen Datenänderungen und die von ihm ausgelösten Queue-Jobs tragen alle dieselbe ID, sodass sich die Frage „Welcher Befehl hat diese Änderung vorgenommen?“ über die ID beantworten lässt. `--actor` ist ein vom Betreiber angegebener Wert und wird daher nicht in die Benutzerspalte der Datenänderungen übernommen (der tatsächliche Akteur ist der Betriebssystembenutzer) |
| `--actor=<email>` | Gemeinsam für alle protokollierten Befehle. Hält das verantwortliche Administrationskonto fest. Eine unbekannte E-Mail-Adresse bricht den Lauf ab |
| `--dry-run` | Gibt das Ergebnis aus, ohne etwas zu ändern. Verfügbar bei schwer rückgängig zu machenden Vorgängen |
| `--force` | Läuft ohne Bestätigungsabfragen (für Zeitpläne und Deployment-Skripte) |
| `--reason=` | Verfügbar bei Befehlen, die eine Begründung verlangen |
| Exit-Codes | 0 Erfolg, 1 Fehler oder abgebrochen, 2 ein Prüfbefehl hat ein Problem gefunden |
| DB-Kontext | Während der Befehl läuft, ist der Akteur der DB-Sitzung auf `system:command:{command}` gesetzt und erscheint in den Datenänderungen (Trigger) |

## Installation und Demo

### `setup:install`

- Zweck: Neuinstallationen und Updates. Führt nacheinander Migrationen, Basisdaten, Standardwerte der Hochschule, das Installationsprofil und den ersten Administrator aus. Kann gefahrlos erneut ausgeführt werden (vorhandene Daten und von Administratoren geänderte Werte bleiben erhalten).
- Wann: bei der Erstinstallation und bei jedem Deployment.
- Optionen: `--institution-name=`, `--locale=` (Standardsprache), `--timezone=` (zum Beispiel `Asia/Seoul`), `--profile=*` (Schlüssel des Installationsprofils), `--grade-scale=` (Notenskala `4.5` oder `4.3`. Kann nach dem Festlegen nicht mehr geändert werden. Leer: die Skala des ersten Profils), `--admin-email=`, `--admin-name=`, `--force` (Ausführung ohne Fragen. Das Administratorpasswort wird von der Standardeingabe gelesen).
- Protokoll: wird aufgezeichnet
- Beispiele

  ```bash
  php artisan setup:install
  echo "$ADMIN_PASSWORD" | php artisan setup:install --force --locale=ko --timezone=Asia/Seoul \
      --institution-name="Demo University" --admin-email=admin@example.ac.kr --admin-name="Administrator"
  ```

- Ausgabe lesen: `DONE`/`FAIL` je Schritt. `Administrator exists` bedeutet, dass bereits eine Zuweisung der Rolle Systemadministration besteht und die Anlage daher übersprungen wurde.
- Bei Fehlern: Meldet der Fehler einen fehlenden Schlüssel, tragen Sie die von `key:generate` und `pii:generate-key` erzeugten Werte in die Umgebungskonfiguration ein. Ein Migrationsfehler bedeutet meist, dass die DB-Rollen nicht vorbereitet sind (siehe Installationshandbuch).

### `setup:demo`

- Zweck: erzeugt mit Factories die Daten einer fiktiven Universität. Gibt die festen Demokonten je Rolle aus (`registrar@demo.test` usw.).
- Wann: Entwicklungs-, Schulungs- und Vorführumgebungen. **In der Produktion (`APP_ENV=production`) läuft er nicht, auch nicht mit `--force`.**
- Optionen: `--size=tiny|small|medium|large` (etwa 20/100/2.000/20.000 Studierende. `tiny` ist für Tests und schnelle Prüfungen), `--seed=` (derselbe Wert erzeugt dieselben Daten), `--force`.
- `--student-accounts`: legt für alle aktiven Studierenden ein Konto an (`student{Matrikelnummer}@demo.test`, das Demopasswort) und schreibt sie nach `storage/app/private/load-accounts.csv`. Für Lasttests ([Lasttests](load-testing.md)).
- Protokoll: wird aufgezeichnet
- Beispiel: `php artisan setup:demo --size=small --seed=42`
- Bei Fehlern: Fehlen die Basisdaten, haben die Demokonten keine Rollen und werden übersprungen. Führen Sie zuerst `setup:install` aus.

## Stammdaten und Semester

### `reference:import`

- Zweck: importiert Stammdaten aus CSV. Verwendet dieselben Importregeln wie **CSV importieren** in der Verwaltungsoberfläche (vorhandene Zeilen werden über den Code zugeordnet und aktualisiert, leere Übersetzungszellen behalten den bisherigen Wert, nur fehlgeschlagene Zeilen werden gesondert festgehalten).
- Argumente: `{type}` einer von `org_units`, `programs`, `buildings`, `rooms`, `lookup_values`, `students`, `instructors`, `courses`, `registration_holds` / `{file}` eine UTF-8-CSV-Datei mit Kopfzeile.
- Optionen: `--actor=` (Pflicht. Der Importeintrag und die Abschlussbenachrichtigung gehen an dieses Konto), `--chunk=100` (Zeilen je Queue-Job), `--dry-run` (nur Prüfung der Kopfzeile und Zeilenanzahl).
- Protokoll: wird aufgezeichnet (Typ, Dateiname, Zeilenanzahl, Anzahl fehlgeschlagener Zeilen)
- Spaltenformat: [Administratorhandbuch Kapitel 9](../admin/09-csv-import.md).
- Exit-Codes: Läuft die Queue synchron, wird das Ergebnis sofort ausgegeben, und der Exit-Code ist 2, wenn es fehlgeschlagene Zeilen gibt. Läuft sie asynchron (Horizon), wird der Import nur angenommen, und der Exit-Code ist 0. Das Ergebnis kommt als CSV der fehlgeschlagenen Zeilen in den Benachrichtigungen der Verwaltungsoberfläche des `--actor`-Kontos an.
- Beispiel: `php artisan reference:import org_units storage/app/org-units.csv --actor=admin@example.ac.kr --dry-run`

### `term:inspect`

- Zweck: zeigt das zu einem bestimmten Zeitpunkt laufende Semester und die offenen Bearbeitungszeiträume (Art, Semester, Zielkriterien, Endzeitpunkt). Damit lassen sich die Konfiguration von Semestern und Bearbeitungszeiträumen prüfen oder Fragen wie „Warum ist die Belegung nicht geöffnet?“ beantworten.
- Optionen: `--at=` (Standard: jetzt. Zum Beispiel `"2027-02-15 09:00"`. Ohne Zeitzone wird der Wert in der Zeitzone des Servers (UTC) interpretiert. Eine Zone kann wie in `"2027-02-15 09:00 Asia/Seoul"` angegeben werden).
- Protokoll: wird nicht aufgezeichnet
- Ausgabe lesen: Datumsentscheidungen verwenden die Zeitzone der Hochschule. Bearbeitungszeiträume von Semestern im Status „Geplant“ oder „Archiviert“ öffnen sich nie.

## Sicherheit

### `security:create-administrator`

- Zweck: legt ein Konto mit der Rolle Systemadministration an. Wird normalerweise von `setup:install` aufgerufen.
- Optionen: `--email=`, `--name=`, `--password-stdin` (Passwort von der Standardeingabe. Erforderlich bei nicht interaktiven Läufen).
- Protokoll: wird aufgezeichnet (ID des angelegten Benutzers)
- Beispiel: `php artisan security:create-administrator --email=admin@example.ac.kr --name="Administrator"`
- Bei Fehlern: `system_administrator role is missing` bedeutet, dass zuerst `setup:install` oder `db:seed` laufen muss. Das Passwort muss mindestens 12 Zeichen lang sein.

### `security:sync-permissions`

- Zweck: gleicht die Berechtigungsliste in der DB mit dem Berechtigungskatalog im Code ab. Neue Berechtigungen werden angelegt; im Katalog fehlende Berechtigungen werden gelöscht (und aus allen Rollen entfernt). Systemrollen erhalten immer alle Berechtigungen.
- Wann: nach dem Deployment (`setup:install` erledigt dies im Schritt Basisdaten).
- Optionen: `--dry-run` (gibt nur die Liste der Anlagen/Löschungen aus), `--force` (überspringt die Bestätigung der Löschung).
- Protokoll: wird aufgezeichnet (angelegte/gelöschte Berechtigungen)
- Ausgabe lesen: Zeilen `create` und `delete`. Gibt es Löschungen, erscheint eine Bestätigungsabfrage.
- Bei Fehlern: Wird die Bestätigung mit „Nein“ beantwortet, endet der Befehl mit Code 1 und ändert nichts.

### `security:reset-mfa`

- Zweck: setzt die Zwei-Faktor-Authentifizierung eines Benutzers zurück (Authenticator-App, E-Mail-Codes, Wiederherstellungscodes, gemerkte Geräte). Der Benutzer richtet sie bei der nächsten Anmeldung neu ein. Verwenden Sie den Befehl, wenn sich kein Administrator mehr in der Verwaltungsoberfläche anmelden kann (zum Beispiel weil der einzige Administrator sein Telefon verloren hat). Andernfalls verwenden Sie **Benutzer > Zwei-Faktor-Authentifizierung zurücksetzen** in der Verwaltungsoberfläche.
- Argumente/Optionen: `{user}` E-Mail-Adresse des Benutzers, `--reason=` (Pflicht), `--actor=` (Pflicht. Der Administrator, der über das Zurücksetzen entschieden hat).
- Protokoll: wird aufgezeichnet (sowohl im Aktivitätsprotokoll als auch im Sicherheitsprotokoll, mit der Begründung)
- Beispiel: `php artisan security:reset-mfa admin@example.ac.kr --reason="Phone lost, identity verified by phone call" --actor=it-lead@example.ac.kr`

### `security:unlock-user`

- Zweck: entsperrt ein durch wiederholte falsche Passwörter gesperrtes Konto vor Ablauf der Sperrfrist. Entspricht **Benutzer > Anmeldung entsperren** in der Verwaltungsoberfläche.
- Argumente/Optionen: `{user}` E-Mail-Adresse des Benutzers, `--reason=` (Pflicht).
- Protokoll: wird aufgezeichnet (auch im Sicherheitsprotokoll)
- Ausgabe lesen: `The account is not locked.` bedeutet, dass nichts geändert wurde.
- Hinweis: Die Sperrschwellen (Fehlversuche, Sperrdauer) werden unter **Einstellungen > Anmeldeverfahren** in der Verwaltungsoberfläche festgelegt.

## Personen und Studierendenakten

### `people:find-duplicates`

- Zweck: listet Personen auf, bei denen es sich offenbar um dieselbe Person handelt. Kriterien sind eine identische Ausweisnummer (Such-Hash) oder ein identischer romanisierter Name zusammen mit identischem Geburtsdatum. Bereits zusammengeführte Personen sind ausgenommen.
- Optionen: `--limit=100` (Anzahl der auszugebenden Gruppen).
- Protokoll: wird nicht aufgezeichnet (nur lesend)
- Exit-Codes: 2, wenn es Dublettenkandidaten gibt.
- Weiteres Vorgehen: Prüfen Sie jede Gruppe unter **Personen** in der Verwaltungsoberfläche und führen Sie, wenn es dieselbe Person ist, auf der Seite der zu behaltenden Person **Dublette zusammenführen** aus. Eine Zusammenführung kann nicht rückgängig gemacht werden ([Administratorhandbuch Kapitel 14](../admin/14-merge-and-invitations.md)).

## Zulassung

### `admission:matriculate`

- Zweck: legt für die Bewerbenden einer Bewerbungsrunde mit bestätigter Einschreibung Studierendenakten (neue Matrikelnummern) an. Entspricht der Sammelaktion **Studierendenakte anlegen** unter **Zulassung > Bewerbungen** in der Verwaltungsoberfläche. Jede Bewerbung wird einzeln verarbeitet, sodass ein Fehler die übrigen nicht aufhält.
- Argumente/Optionen: `{cycle}` Code der Bewerbungsrunde, `--term=` (Pflicht. Code des Aufnahmesemesters, zum Beispiel `2027-SP`. Das Aufnahmedatum ist der erste Tag des Semesters), `--actor=` (Pflicht. Ein Konto mit der Berechtigung `Studierendenakten für zugelassene Bewerbende anlegen`. Es werden nur Bewerbende im Geltungsbereich der Berechtigung dieses Kontos verarbeitet), `--dry-run` (nur Liste der Betroffenen).
- Protokoll: wird aufgezeichnet (Bewerbungsrunde, Semester, Anzahl der Betroffenen, Anzahl der Anlagen, Nummern der fehlgeschlagenen Bewerbungen). Je Bewerbung wird zusätzlich ein Aktivitätseintrag (`application.matriculated`) geschrieben.
- Exit-Codes: 2, wenn bei einer Bewerbung ein Fehler aufgetreten ist.
- Beispiel: `php artisan admission:matriculate DEMO-2027-REG --term=2027-SP --actor=admissions@example.ac.kr --dry-run`
- Bei Fehlern: `not ready` bedeutet, dass bereits eine Studierendenakte existiert oder die Bewerbung nicht im Status „Einschreibung bestätigt“ ist; `program is not in effect` bedeutet, dass der Studiengang am Aufnahmedatum nicht gültig ist (prüfen Sie den Gültigkeitszeitraum unter Stammdaten > Studiengänge).

### `admission:expire-drafts`

- Zweck: markiert Bewerbungsentwürfe abgeschlossener Bewerbungsrunden sowie Entwürfe, die während des eingestellten Zeitraums nicht bearbeitet wurden (**Einstellungen > Zulassung > Unbearbeitete Entwürfe verfallen nach (Tagen)**, Standard 90 Tage), als **Verfallen**. Verfallene Bewerbungen verhindern keine neue Bewerbung in derselben Bewerbungsrunde.
- Wann: zeitgesteuert (täglich um 03:10, [Zeitgesteuerte Jobs](scheduled-jobs.md)).
- Optionen: `--dry-run` (nur Anzahl).
- Protokoll: wird aufgezeichnet (Anzahl der verfallenen Entwürfe)

## Kurse

### `section:copy`

- Zweck: beginnt die Planung eines neuen Semesters mit den Kursen eines früheren Semesters. Entspricht **Kurse > Aus anderem Semester kopieren** in der Verwaltungsoberfläche. Kopiert die nicht abgesagten Kurse als Entwürfe, zusammen mit den Lehrpersonen (sofern noch beschäftigt), Kurszeiten und Räumen, Platzkontingenten (Freigabezeitpunkte um die Differenz der Semesterbeginne verschoben) und dem Kursplan (als nicht öffentlicher Entwurf). Die Version der Lehrveranstaltung wird durch die zu Beginn des neuen Kurszeitraums gültige Version ersetzt.
- Argumente/Optionen: `{from-term}`, `{to-term}` Semestercodes (zum Beispiel `2026-FA`), `--org-unit=*` (nur Kurse mit diesem Code der Organisationseinheit), `--dry-run` (nur Ergebnisse, nichts wird gespeichert), `--actor=` (Pflicht. Es werden nur Kurse im Geltungsbereich der Berechtigung `Kurse planen` dieses Kontos kopiert).
- Protokoll: wird aufgezeichnet (Anzahl kopiert, übersprungen und zu prüfen)
- Ausgabe lesen: `skipped` bedeutet, dass das Zielsemester bereits denselben Kurscode hat oder keine gültige Version der Lehrveranstaltung existiert; `note` bedeutet, dass der Kurs kopiert, aber etwas weggelassen wurde (ohne Raum kopiert, weil der Raum bereits belegt ist, eine ausgeschiedene Lehrperson usw.). Kurszeiträume werden über den Code zugeordnet; gibt es keinen passenden, kommt der Kurs in den Kurszeitraum des gesamten Semesters.
- Beispiel: `php artisan section:copy 2026-FA 2027-FA --actor=academic@example.ac.kr --dry-run`

### `schedule:check-conflicts`

- Zweck: prüft die Kurse eines Semesters vor der Veröffentlichung des Stundenplans. Doppelbelegungen von Räumen und Lehrpersonen verhindert bereits die DB; dieser Befehl findet die Probleme, die die DB zulässt: Kapazität über der Raumkapazität, eine zum Beginn des Kurszeitraums nicht gültige Version der Lehrveranstaltung, eine ausgeschiedene Lehrperson, Platzkontingente über der Kapazität, Termindaten außerhalb des Kurszeitraums. Außerdem prüft er Raumkonflikte in Daten, die ohne Constraints hereingekommen sind, etwa durch eine Wiederherstellung aus einer Sicherung.
- Argumente: `{term}` Semestercode.
- Protokoll: wird nicht aufgezeichnet (nur lesend)
- Exit-Codes: 2, wenn es Probleme gibt. Kurse, die noch keine Lehrperson oder keinen Raum haben, werden nur zur Information angezeigt.

## Belegung

### `registration:preflight`

- Zweck: prüft ein Semester vor dem Öffnen der Belegung. Zeigt die Bearbeitungszeiträume für Belegung und Belegungsänderung, das Regelwerk, die Anzahl veröffentlichter Kurse, Kurse mit Kapazität 0, Platzkontingente über der Kapazität, zum Beginn des Kurszeitraums nicht gültige Versionen von Lehrveranstaltungen, die Anzahl aktiver Studierender ohne Leistungspunkte-Obergrenze und die Anzahl der Studierenden mit Belegungssperre.
- Argumente: `{term}` Semestercode (zum Beispiel `2027-SP`).
- Protokoll: wird nicht aufgezeichnet (nur lesend)
- Exit-Codes: 2, wenn es Probleme gibt. Ein fehlendes Regelwerk wird zur Information angezeigt, nicht als Problem (es gelten die Standardwerte der Regeln).
- Beispiel: `php artisan registration:preflight 2027-SP`

### `registration:simulate`

- Zweck: zeigt Regel für Regel, was geschähe, wenn die/der Studierende den Kurs jetzt (oder zu einem angegebenen Zeitpunkt) belegen würde. Es wird nichts gespeichert und kein Versuch aufgezeichnet. Verwenden Sie den Befehl für Fragen wie „Warum kann ich nicht belegen?“.
- Argumente: `{student-number}` Matrikelnummer, `{section}` `Veranstaltungsnummer-Kurscode` (zum Beispiel `CSE2010-01`).
- Optionen: `--term=` (Semestercode. Standard ist das jüngste Semester, in dem es diesen Kurs gibt), `--at=` (Zeitpunkt der Auswertung. Zum Beispiel `"2027-02-10 10:00 Asia/Seoul"`).
- Protokoll: wird nicht aufgezeichnet (nur lesend)
- Ausgabe lesen: je Regel bestanden/nicht bestanden, blockieren/warnen und die Meldung. Eine durch eine Regelausnahme abgedeckte Regel erscheint als nicht bestanden, blockiert aber nicht. Die Kapazität wird gesondert unterhalb der Regeltabelle angezeigt.
- Exit-Codes: 2, wenn die Belegung abgelehnt würde.

### `waitlist:promote`

- Zweck: lässt Studierende von der Warteliste in Kursen mit freien Plätzen der Reihe nach nachrücken. Beim Nachrücken werden alle Regeln erneut geprüft; Wartelisteneinträge, die eine Regel nicht erfüllen, werden von der Warteliste entfernt (mit festgehaltenem Grund). Wartelisten von Kursen, deren Bearbeitungszeiträume für Belegung und Belegungsänderung beide geschlossen sind, verfallen. Die Studierenden erhalten das Ergebnis per E-Mail.
- Argumente/Optionen: `{term?}` Semestercode (Standard: alle Semester mit Wartelisteneinträgen), `--actor=`.
- Protokoll: wird aufgezeichnet (Anzahl Kurse, nachgerückt, entfernt, verfallen)
- Wann: Bei einer Abmeldung, einem Rücktritt oder der Freigabe eines Platzkontingents wird der betroffene Kurs sofort durch einen Queue-Job verarbeitet. Dieser Befehl ist das stündliche zeitgesteuerte Sicherheitsnetz für alles, was dabei übersehen wurde.
- Beispiel: `php artisan waitlist:promote 2027-SP --actor=registrar@example.ac.kr`

### `registration:release-reserved-seats`

- Zweck: markiert Platzkontingente, deren Freigabezeitpunkt verstrichen ist (zum Beispiel für Studierende des Fachs reservierte Plätze), als verarbeitet und stellt den Job für das Nachrücken von der Warteliste für diese Kurse in die Queue. Reservierte Plätze zählen in der Berechnung bereits ab dem Freigabezeitpunkt als allgemeine Plätze; dieser Befehl übergibt die Plätze also nur an die Warteliste.
- Protokoll: wird nicht aufgezeichnet (läuft alle 5 Minuten. Die Ergebnisse des Nachrückens stehen in den Belegungsdaten)
- Wann: zeitgesteuert (alle 5 Minuten).

## Noten

### `term:close`

- Zweck: schließt ein Semester ab. Alle Noten des Semesters müssen freigegeben sein. Für jede/n Studierende/n werden die Semesterbilanz (Leistungspunkte, Semester- und Gesamtdurchschnitt) berechnet, der Studienstatus (ordentlicher Status, Studienwarnung, Prüfung der Exmatrikulation) und die Berechtigung für eine Auszeichnung bestimmt (Queue-Batch); anschließend werden die Auszeichnungen je Studiengang gereiht, die Semesterbilanzen als amtliche Daten abgeschlossen und das Semester auf **Noten abgeschlossen** gesetzt. Entspricht **Noten > Semesterbilanzen > Semester abschließen** in der Verwaltungsoberfläche.
- Argumente/Optionen: `{term}` Semestercode, `--dry-run` (prüft nur, ob das Semester abgeschlossen werden kann), `--reason=` (im Eintrag festgehaltene Begründung), `--actor=`.
- Protokoll: wird aufgezeichnet (Anzahl der Studierenden, Anzahl der Kurse mit nicht freigegebenen Noten). Nach Abschluss enthält der Protokolleintrag `term.closed` die Anzahl der Studienwarnungen, der Prüfungen der Exmatrikulation und der Auszeichnungen.
- Exit-Codes: 2, wenn einige Kurse nicht freigegebene Noten haben (die Anzahl je Kurs wird ausgegeben). 1, wenn das Semester nicht im Status „Veröffentlicht“ ist.
- Wann: nach abgeschlossener Noteneingabe und -freigabe. Verfolgen Sie den Fortschritt des Batches in Horizon. Ohne Queue-Worker ändert sich der Semesterstatus nicht.
- Beispiele: `php artisan term:close 2026-FA --dry-run`, `php artisan term:close 2026-FA --reason="Fall 2026 grades closed" --actor=registrar@example.ac.kr`

### `grades:recalculate`

- Zweck: berechnet Semesterbilanzen (Leistungspunkte, Semester- und Gesamtdurchschnitt) aus freigegebenen Noten neu. Damit wird eine geänderte Bewertungsrichtlinie (Nachkommastellen des Notendurchschnitts, Behandlung von Wiederholungen) auf frühere Semester angewendet. Studienstatus und Auszeichnungen behalten die beim Semesterabschluss festgelegten Werte. Eine genehmigte Notenänderung berechnet die betreffende Person automatisch neu; dafür ist dieser Befehl nicht nötig.
- Optionen: `--term=` (Studierende mit Noten in diesem Semester, ab diesem Semester), `--student-number=` (eine/n Studierende/n), `--reason=` (Pflicht), `--actor=`.
- Protokoll: wird aufgezeichnet (Anzahl der Studierenden, Begründung)
- Beispiel: `php artisan grades:recalculate --term=2026-SP --reason="GPA changed to three decimal places"`

### `grades:remind-missing`

- Zweck: sendet den Lehrpersonen von Kursen, deren Noten noch nicht eingereicht wurden (nicht eingetragen oder nur als Entwurf gespeichert), eine E-Mail. Die Kurse werden je Lehrperson in einer Nachricht zusammengefasst.
- Argumente/Optionen: `{term}` Semestercode, `--dry-run` (gibt die Empfänger aus, ohne zu senden), `--actor=`.
- Protokoll: wird aufgezeichnet (Kurse, Lehrpersonen, gesendete Nachrichten)
- Wann: einige Tage vor dem Ende des Bearbeitungszeitraums für die Noteneingabe. Bei Bedarf in den Zeitplan aufnehmen.

## Lehrevaluation

### `evaluation:remind`

- Zweck: sendet Studierenden, die noch nicht evaluierte Lehrveranstaltungen haben, während des offenen Bearbeitungszeitraums der Lehrevaluation eine E-Mail. Die verbleibenden Lehrveranstaltungen werden je Person in einer Nachricht zusammengefasst.
- Argumente/Optionen: `{term}` Semestercode, `--dry-run` (nur Anzahl der betroffenen Studierenden, nichts wird gesendet), `--actor=`.
- Protokoll: wird aufgezeichnet (betroffene Studierende, gesendete Nachrichten)
- Wann: ein- oder zweimal während des Evaluationszeitraums. Bei Bedarf in den Zeitplan aufnehmen.

### `evaluation:publish-results`

- Zweck: veröffentlicht die Ergebnisse der Lehrevaluation des Semesters für die Lehrpersonen. Entspricht **Lehrevaluation > Semesterevaluationen > Ergebnisse veröffentlichen** in der Verwaltungsoberfläche. Auch nach der Veröffentlichung sind Ergebnisse nur für Kurse sichtbar, die die Mindestanzahl an Antworten und die Mindest-Rücklaufquote erreicht haben.
- Argumente/Optionen: `{term}` Semestercode, `--actor=`.
- Protokoll: wird aufgezeichnet (Anzahl der Antworten, ob bereits veröffentlicht)
- Wann: nach Ende des Evaluationszeitraums und abgeschlossener Notenbearbeitung. Ein bereits veröffentlichtes Semester wird nicht verändert.

## Bescheinigungen

### `certificate:verify`

- Zweck: prüft einen Prüfcode einer Bescheinigung nach denselben Kriterien wie die öffentliche Prüfseite. Gibt Art, Seriennummer, Ausstellungsdatum und die maskierte Inhaberin bzw. den maskierten Inhaber aus.
- Argumente/Optionen: `{code}` Prüfcode.
- Protokoll: keines (nur lesend)
- Exit-Codes: 0, wenn gültig; 2 bei einem unbekannten Code, einer widerrufenen Bescheinigung oder abgelaufener Gültigkeit.

### `certificate:render`

- Zweck: erzeugt das PDF einer ausgestellten Bescheinigung mit den aktuellen Daten und der aktuellen Vorlage neu und ersetzt die archivierte Datei. Entspricht **Ausgestellte Bescheinigungen > Neu erzeugen** in der Verwaltungsoberfläche. Seriennummer und Prüfcode bleiben gleich; die Prüfsumme ändert sich.
- Argumente/Optionen: `{issue}` Seriennummer, `--reason=` (Pflicht), `--actor=`.
- Protokoll: wird aufgezeichnet (Seriennummer, neue Prüfsumme)
- Wann: um eine bestimmte Bescheinigung nach der Korrektur einer Vorlage oder der Schreibweise eines Namens neu zu erzeugen.

## Prüfungen

### `assessment:import-results`

- Zweck: trägt die Ergebnisse eines Prüfungstermins aus CSV ein. Gleiche Regeln wie **Prüfungstermine > Ergebnisse importieren (CSV)** in der Verwaltungsoberfläche (nur angemeldete Studierende, Bestehen/Nichtbestehen wird aus der Punktzahl bestimmt, erneutes Eintragen vor der Veröffentlichung ersetzt die Ergebnisse).
- Argumente/Optionen: `{type}` Code der Prüfungsart / `{held_date}` Prüfungsdatum `YYYY-MM-DD` / `{file}` eine UTF-8-CSV-Datei mit Kopfzeile, `--actor=` (Pflicht), `--dry-run` (nur Zeilenprüfung, nichts wird eingetragen).
- Spaltenformat: [Administratorhandbuch Kapitel 24](../admin/24-assessments.md).
- Protokoll: wird aufgezeichnet (Prüfungstermin, Dateiname, Zeilenanzahl, Anzahl eingetragen/abwesend/fehlerhaft)
- Exit-Codes: 2, wenn es fehlerhafte Zeilen gibt (die übrigen Zeilen werden eingetragen).
- Beispiel: `php artisan assessment:import-results ENG_COMP 2026-05-15 storage/app/eng.csv --actor=registrar@example.ac.kr --dry-run`

### `assessment:publish-results`

- Zweck: veröffentlicht die Ergebnisse eines Prüfungstermins für die Studierenden. Entspricht **Prüfungstermine > Ergebnisse veröffentlichen** in der Verwaltungsoberfläche.
- Argumente/Optionen: `{type}` Code der Prüfungsart / `{held_date}` Prüfungsdatum, `--actor=` (Pflicht).
- Protokoll: wird aufgezeichnet (Prüfungstermin, Anzahl der Ergebnisse)
- Wann: nach abgeschlossener Ergebniseingabe. Bei einem bereits veröffentlichten Prüfungstermin schlägt der Befehl fehl.

## Anwesenheit

### `attendance:generate-sessions`

- Zweck: erzeugt für alle Kurse eines Semesters die Kurstermine aus ihren Kurszeiten (Wochentag, Uhrzeit, Zeitraum). Entspricht **Termine erzeugen** im Portal für Lehrpersonen und in der Verwaltungsoberfläche. Vorhandene Termine und ihre Anwesenheit bleiben erhalten (kann gefahrlos erneut ausgeführt werden).
- Argumente/Optionen: `{term}` Semestercode, `--section=` (Veranstaltungsnummer-Kurscode, zum Beispiel `CSE2010-01`, nur einer), `--dry-run` (nur Anzahl der betroffenen Kurse), `--actor=`.
- Protokoll: wird aufgezeichnet (Semester, Kurse, erzeugte Termine)
- Wann: vor Semesterbeginn, nachdem die Kurszeiten feststehen. Bei Änderungen der Kurszeiten erneut ausführen.

## Studienabschluss

### `degree-audit:run`

- Zweck: speichert für alle aktiven Studierenden eine Prüfung der Abschlussvoraussetzungen. Entspricht **Prüfungen der Abschlussvoraussetzungen > Prüfungen durchführen** in der Verwaltungsoberfläche. Die zuletzt gespeicherte Prüfung ist die Grundlage für die Bearbeitung des Studienabschlusses.
- Optionen: `--term=` (Semestercode, zu dem die Prüfung gehört), `--student-number=` (eine/n Studierende/n), `--min-year-level=` (nur ab diesem Studienjahr), `--actor=`.
- Protokoll: wird aufgezeichnet (Anzahl der Studierenden, Anzahl erfüllt)
- Wann: vor der Abschlussprüfung (Semesterende) sowie nach Änderungen an Anforderungssätzen oder äquivalenten Lehrveranstaltungen.

## LMS-Anbindung

### `lms:sync`

- Zweck: überträgt veröffentlichte Kurse an die Plattform (Anlegen und Verknüpfen des Kurses, Lehrpersonen, eingeschriebene Studierende). Erledigt für ein ganzes Semester, was **Kursverknüpfungen > Erneut synchronisieren** in der Verwaltungsoberfläche tut. Bereits verknüpfte Kurse werden nur erneut abgeglichen (idempotent).
- Argumente/Optionen: `{platform}` Plattformcode, `--term=` (Semestercode, Standard: alle noch nicht beendeten Semester), `--section=` (eine Veranstaltungsnummer-Kurscode-Angabe), `--dry-run` (hält nur fest, was als „skipped“ gesendet würde), `--actor=`.
- Protokoll: wird aufgezeichnet (Plattform, Kurse, Mitglieder, Fehler)
- Exit-Codes: 2, wenn bei einem Kurs ein Fehler aufgetreten ist.

### `lms:reconcile`

- Zweck: vergleicht die Teilnehmerlisten der Kurse auf der Plattform mit den Belegungslisten im Studierendenverwaltungssystem, fügt fehlende Mitglieder hinzu und entfernt überzählige. Unterschiede werden im Synchronisationsprotokoll (`reconcile`) festgehalten.
- Argumente/Optionen: `{platform?}` (ohne Angabe alle aktiven Plattformen), `--term=`, `--dry-run` (hält nur Unterschiede fest), `--actor=`.
- Protokoll: wird aufgezeichnet (Kurse je Plattform, Anzahl hinzugefügt/entfernt/fehlgeschlagen)
- Wann: zeitgesteuert täglich um 02:30. Nach einem Ausfall der Plattform manuell ausführen.

## Betriebsprüfungen und Bereinigung

### `system:health-check`

- Zweck: prüft Konfiguration und Schlüssel (APP_KEY, PII-Schlüssel, APP_DEBUG), die DB-Verbindung und die Rollen (runtime, migrator, reporting), RLS auf Tabellen mit personenbezogenen Daten, ausstehende Migrationen, die Synchronisation des Berechtigungskatalogs, Redis, die Queue (Horizon), Gotenberg, Schreibzugriffe auf den Medienspeicher und den Heartbeat des Schedulers.
- Optionen: `--json` (Ausgabe für das Monitoring).
- Protokoll: keines (nur lesend)
- Exit-Codes: 2, wenn eine Prüfung fehlschlägt.
- Wann: direkt nach dem Deployment, bei Verdacht auf eine Störung, regelmäßig aus dem Monitoring.

### `audit:seal`

- Zweck: verknüpft neue Zeilen der Protokolltabellen mit der Hash-Kette ([Integrität des Protokolls](audit-and-maintenance.md)).
- Optionen: `--table=` (eine von record_changes, activity_log, security_events; mehrfach angebbar), `--actor=`.
- Protokoll: wird aufgezeichnet (versiegelte Zeilen je Tabelle)
- Wann: zeitgesteuert täglich um 03:30.

### `audit:verify-chain`

- Zweck: berechnet die Inhalts-Hashes und die Kette der Protokollzeilen neu, um Manipulationen, Löschungen und Umsortierungen zu finden.
- Optionen: `--table=`.
- Protokoll: keines (nur lesend)
- Exit-Codes: 2 bei einer Abweichung.
- Wann: vor Sicherungen, bei Sicherheitsüberprüfungen, nach dem Bereinigen.

### `audit:prune`

- Zweck: löscht **versiegelte** Protokollzeilen, deren Aufbewahrungsfrist abgelaufen ist. Läuft über die Verbindung des Schema-Eigentümers.
- Optionen: `--before=YYYY-MM-DD` (Pflicht), `--table=`, `--dry-run` (nur Anzahl), `--actor=`.
- Protokoll: wird aufgezeichnet (Stichtag, gelöschte Zeilen je Tabelle)
- Wann: gemäß der Aufbewahrungsrichtlinie, nach einer Sicherung.

### `media:prune-orphans`

- Zweck: löscht Medienzeilen, deren Eigentümer nicht mehr existiert, sowie Verzeichnisse ohne Zeile (Personenfotos, Bewerbungsunterlagen, Bescheinigungs-PDFs, Prüfungsnachweise).
- Optionen: `--dry-run` (nur Liste), `--actor=`.
- Protokoll: wird aufgezeichnet (Zeilen, Verzeichnisse)
- Wann: monatlich oder nach einer Massenlöschung.

## Schlüssel für die Verschlüsselung personenbezogener Daten

### `pii:generate-key`

- Zweck: gibt neue Schlüssel für `PII_ENCRYPTION_KEY` und `PII_HASH_KEY` aus. Speichert nichts.
- Protokoll: wird nicht aufgezeichnet
- Achtung: Eine Änderung von `PII_HASH_KEY` macht alle Such-Hashes ungültig. Das Verfahren zum Schlüsselwechsel steht in [security-keys.md](security-keys.md).

### `pii:reencrypt`

- Zweck: verschlüsselt nach einem Schlüsselwechsel personenbezogene Daten, die mit einem früheren Schlüssel verschlüsselt wurden, mit dem aktuellen Schlüssel neu. Läuft als Queue-Batch.
- Argumente: die Modellklasse (derzeit `App\\Models\\Person\\Person`).
- Optionen: `--chunk=500`, `--dry-run` (zählt nur Datensätze, die einen früheren Schlüssel verwenden), `--force`.
- Protokoll: wird aufgezeichnet (Batch-ID, Anzahl der Jobs / Anzahl in der Vorschau)
- Überprüfung: Verfolgen Sie den Fortschritt des Batches in Horizon (`/horizon`). Ist er beendet und meldet `--dry-run` 0, entfernen Sie den früheren Schlüssel aus `PII_PREVIOUS_KEYS`.

## Lokalisierung

### `i18n:missing`

- Zweck: zeigt je Sprache die Schlüssel, die im Vergleich zur Basissprache (en) fehlen, und die nicht mehr verwendeten Schlüssel.
- Argumente/Optionen: `{locale?}` (nur eine Sprache), `--strict` (Exit-Code 2, wenn Schlüssel fehlen. Wird in der CI verwendet).
- Protokoll: wird nicht aufgezeichnet

### `i18n:export`

- Zweck: erzeugt eine JSON-Datei zur Übergabe an Übersetzerinnen und Übersetzer. Format: `{"key": {"source": "English text", "translation": "translation"}}`.
- Argumente/Optionen: `{locale} {path}`, `--missing` (nur fehlende Schlüssel).
- Protokoll: wird nicht aufgezeichnet
- Beispiel: `php artisan i18n:export ko storage/app/ko.json --missing`

### `i18n:import`

- Zweck: übernimmt das von den Übersetzenden ausgefüllte JSON nach `lang/{locale}`. Es werden nur Schlüssel importiert, die in der Basissprache existieren; leere Übersetzungen werden ignoriert.
- Argumente/Optionen: `{locale} {path}`, `--dry-run` (gibt nur die Liste der Schlüssel aus, die geschrieben würden).
- Protokoll: wird aufgezeichnet
- Achtung: Übersetzungsdateien werden im Repository verwaltet. Committen Sie die Änderungen nach dem Import.

## Prüfungen der Codebasis

### `naming:check`

- Zweck: prüft die Tabellen- und Spaltennamen in Migrationen gegen die [Namenskonventionen](../../../development/naming.md).
- Optionen: `--path=*` (zu prüfende Verzeichnisse, Standard `database/migrations`).
- Protokoll: wird nicht aufgezeichnet
- Exit-Codes: 2, wenn es Probleme gibt.

### `pdf:sample`

- Zweck: erzeugt eine Tabelle mit gemischter Hangul-, CJK-, Lao-, Thai-, Devanagari-, kyrillischer und lateinischer Schrift als PDF, um die Schriftarten und die Verbindung zum PDF-Dienst (Gotenberg) zu prüfen.
- Argumente: `{path=pdf-sample.pdf}` (relativ zur lokalen Disk `storage/app/private`).
- Protokoll: wird nicht aufgezeichnet
- Bei Fehlern: Prüfen Sie bei einem Verbindungsfehler `GOTENBERG_URL` und den Zustand des Gotenberg-Containers.
