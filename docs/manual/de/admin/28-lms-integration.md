# 28. LMS-Anbindung

Menü: **LMS-Anbindung > LMS-Plattformen**, **Kursverknüpfungen**, **Synchronisationsprotokolle** (Berechtigung `LMS-Plattformen, Verknüpfungen und Synchronisation verwalten`; Standard: Lehrplanung, neben Systemadministration und Studierendensekretariat). Lehrpersonen verwenden die LMS-Links unter **Meine Lehre** und in den Kursdetails sowie **Punkte aus dem LMS laden** auf dem Bildschirm der Noteneingabe. Das Laden unterliegt denselben Bedingungen wie die Noteneingabe (Lehrpersonen innerhalb des Zeitraums der Noteneingabe, Mitarbeitende innerhalb ihres Geltungsbereichs für die Verwaltung von `Noten`).

Details für Entwickler/innen stehen im [Leitfaden zur LMS-Anbindung](../../../lms-integration-guide.md).

## Was wohin geht

| SIS → LMS | Wann |
|---|---|
| Kurs (Anlegen des Kurses) + Lehrpersonen | Wenn der Kurs **veröffentlicht** wird, und mit `lms:sync` |
| Bestätigte Belegung → Einschreibung | Sofort bei Belegung, Nachrücken von der Warteliste oder Belegung durch Mitarbeitende |
| Abmeldung oder Rücktritt → Austragung | Sofort |
| Vollständiger Abgleich | Täglich um 02:30 Uhr (`lms:reconcile`) |

| LMS → SIS | Wann |
|---|---|
| Endpunktzahlen (Entwurf) | Wenn die Lehrperson auf dem Bildschirm der Noteneingabe auf **Punkte aus dem LMS laden** klickt. Speichern und Einreichen übernimmt die Lehrperson |

Endgültige Noten werden nicht an das LMS gesendet. Kursmaterialien, Aufgaben und Quizze liegen im LMS.

## Plattformen

**LMS-Plattformen**: Code, Name, Treiber, Basis-URL, Zugangsdaten (Schlüssel/Wert, verschlüsselt gespeichert, bleiben erhalten, wenn sie beim Bearbeiten leer gelassen werden), aktiv, Teilnehmerlisten synchronisieren, Endpunktzahlen abrufen.

- Treiber **Keiner (nur protokollieren)**: ruft nichts auf und protokolliert, was gesendet würde. Verwenden Sie ihn, um den Ablauf zu prüfen oder für Vorführungen vor der Anbindung. Die Plattform `DEMO` in den Demodaten verwendet ihn.
- `Moodle`, `Open edX`: noch kein Konnektor (als noch nicht implementiert markiert). Bei Auswahl werden Synchronisationen als fehlgeschlagen protokolliert.
- Wird **Aktiv** eingeschaltet, fließen Veröffentlichungen von Kursen und Belegungsänderungen ab dann sofort, und die Plattform wird in den nächtlichen Abgleich einbezogen.

## Kursverknüpfungen und Synchronisationsprotokolle

- **Kursverknüpfungen**: welcher Kurs mit welchem Kurs der Plattform verknüpft ist (ID, URL), Status (Ausstehend/Verknüpft/Fehlgeschlagen), letzte Synchronisation und Fehler. **Erneut synchronisieren** überträgt Kurs, Lehrpersonen und Teilnehmerliste erneut.
- **Synchronisationsprotokolle**: Ergebnis und Meldung pro Schritt (Kurs, Mitglied hinzufügen und entfernen, Punkte abrufen, Abgleich). Hier finden Sie die Ursache eines Fehlers.
- Befehle: `lms:sync {platform} --term={term}` (vollständige Übertragung, `--dry-run`), `lms:reconcile` (Abgleich).

## Portal

- Der Link zum Kurs auf der Plattform wird in den Kursdetails für Studierende und Lehrpersonen sowie in der Liste **Meine Lehre** der Lehrperson angezeigt (nur verknüpfte Kurse).
- Noteneingabe: Wenn bei der Plattform **Endpunktzahlen abrufen** eingeschaltet und der Kurs verknüpft ist, erscheint die Schaltfläche **Punkte aus dem LMS laden**. Sie füllt nur die Punktespalte und speichert nicht.

## Häufige Fragen

- **Ein/e Studierende/r fehlt im LMS.** Für die Synchronisation braucht die Person ein Konto (Benutzer). Fehlt es, laden Sie sie ein ([Kapitel 14](14-merge-and-invitations.md)); der nächste Abgleich fügt sie hinzu.
- **Eine Synchronisation bleibt fehlgeschlagen.** Lesen Sie die Meldung im Synchronisationsprotokoll. Abgelaufene Zugangsdaten sind häufig. Ändern Sie die Werte auf dem Bearbeitungsbildschirm der Plattform und klicken Sie auf **Erneut synchronisieren**.
- **Wir haben die Plattform gewechselt.** Legen Sie die neue Plattform an und schalten Sie bei der alten Aktiv aus. Verknüpfungen werden pro Plattform getrennt angelegt.
