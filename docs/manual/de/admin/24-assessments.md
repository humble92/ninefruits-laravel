# 24. Prüfungen

Menü: **Prüfungen > Prüfungstermine**, **Prüfungsergebnisse**, **Prüfungsarten**. Studierende melden sich im Portal unter **Prüfungen** an und sehen dort ihre Ergebnisse.

| Berechtigung | Was sie erlaubt |
|---|---|
| `Studierende anmelden, Ergebnisse eintragen und veröffentlichen, externe Ergebnisse erfassen` | Studierende anmelden, Anmeldungen stornieren und als abwesend markieren, Ergebnisse eintragen, CSV importieren, Ergebnisse veröffentlichen, externe Ergebnisse erfassen und entfernen, das Ergebnisregister einsehen (innerhalb des Geltungsbereichs für das Anzeigen von Studierendenakten) |
| `Prüfungsarten, Bereiche und Termine verwalten` | Arten, Bereiche und Termine anlegen und bearbeiten |

Standardrollen: Studierendensekretariat und Lehrplanung haben beide.

## Unterschiede zum Altsystem

- **Keine festen Spalten pro Art.** Das Altsystem (YUSTIC) hatte die vier Spalten Grammatik, Schreiben, Hörverstehen und Leseverstehen fest in `CET` verdrahtet, einen P/N-Buchstaben pro Fach für die Fachprüfung (`SSCORE_ADD`) und noch eine weitere Tabelle für die Computerprüfung (`COMEXAM`). Das neue System definiert Bereiche und ein Bewertungsverfahren bei der **Prüfungsart** und behandelt jede Prüfung mit derselben Struktur aus Termin, Anmeldung und Ergebnis.
- **Entscheidung über das Bestehen.** Im Altsystem galt eine Summe der vier Bereiche ab der Grenze als bestanden, ohne Mindestwert pro Bereich. Das neue System entscheidet anhand der Bestehensgrenze der Art (oder des Termins) und eines **Mindestwerts pro Bereich**. Bei Arten mit Bestanden/nicht bestanden entscheiden die Mitarbeitenden.
- **Anmeldezeitraum und Kapazität.** Das Altsystem hatte keine. Ein Termin hat einen Anmeldebeginn und einen Anmeldeschluss sowie eine Kapazität.
- **Veröffentlichung der Ergebnisse.** Im Altsystem sahen Studierende Ergebnisse, sobald sie eingetragen waren, und nur vom letzten Termin. Im neuen System sehen Studierende Ergebnisse nach **Ergebnisse veröffentlichen** für den Termin, und die Ergebnisse aller Termine bleiben erhalten.
- **Externe Ergebnisse.** Im Altsystem gab es keinen Ort, um externe Tests wie TOEFL oder TOPIK zu erfassen (das HSK-Feld im Prüfblatt für den Studienabschluss war eine leere Beschriftung). Externe Ergebnisse werden mit einer Nachweisdatei erfasst.
- **Verbindung zu den Abschlussvoraussetzungen.** Im Altsystem las eine Person das Prüfblatt für den Studienabschluss und entschied. Die Anforderungsart **Bestandene Prüfung** bringt dies in den Studienplan ([Kapitel 17](17-courses-and-curriculum.md)).

## Prüfungsarten und Bereiche

**Prüfungsarten**: Code, Name (3 Sprachen), Kategorie (Sprachprüfung, Fachprüfung, Abschlussarbeit, Externer Test), Bewertung, Höchstpunktzahl, Bestehensgrenze, externe Ergebnisse erlaubt, aktiv.

| Bewertung | Eingabe | Entscheidung |
|---|---|---|
| Punkte | Punkte pro Bereich (die Gesamtpunktzahl, wenn es keine Bereiche gibt) | Gesamt ≥ Bestehensgrenze und jeder Bereich ≥ seinem Mindestwert. Ohne Bestehensgrenze wählen die Mitarbeitenden |
| Bestanden/nicht bestanden | Bestanden oder nicht | Die Mitarbeitenden wählen |

**Bereiche** (unten auf dem Bearbeitungsbildschirm der Art): Code (Spaltenname in der CSV), Name, Höchstpunktzahl, Mindestpunktzahl (optional). Mit Bereichen ist die Gesamtpunktzahl die Summe der Bereichspunkte.

Vom Profil für koreanische Universitäten angelegte Arten:

| Code | Art | Entscheidung | Hinweis |
|---|---|---|---|
| `ENG_COMP` | Englisch-Gesamtprüfung | Punkte, bestanden ab 60 | Hörverstehen, Leseverstehen, Schreiben, Grammatik, je 25 Punkte |
| `COMP_EXAM` | Informatik-Gesamtprüfung | Bestanden/nicht bestanden | |
| `THESIS` | Abschlussarbeit | Bestanden/nicht bestanden | |
| `TOPIK` | TOPIK (Koreanisch-Sprachtest) | Bestanden/nicht bestanden, extern | Niveau und Zertifikatsnummer werden erfasst |

## Termine

**Prüfungstermine**: Art, Datum (pro Art eindeutig), Ort, Kapazität, Anmeldebeginn und Anmeldeschluss, Bestehensgrenze (leer: der Wert der Art). Die Liste **Angemeldete** befindet sich unten auf dem Bearbeitungsbildschirm des Termins.

- **Studierende/n anmelden** (Matrikelnummer eingeben): Mitarbeitende können Studierende unabhängig vom Anmeldezeitraum bis zum Prüfungsdatum anmelden. Die Kapazität gilt. Für einen Termin, dessen Datum vorbei ist, kann niemand angemeldet werden. Ergebnisse werden nur für angemeldete Studierende eingetragen; CSV-Zeilen nicht angemeldeter Studierender werden als Fehler gemeldet und übersprungen.
- Studierende melden sich während des Anmeldezeitraums im Portal an und stornieren dort. Nur eingeschriebene und beurlaubte Studierende können dies.
- **Ergebnis eintragen**: Punkte pro Bereich (oder gesamt), bei Bedarf bestanden oder nicht, Anmerkung. Vor der Veröffentlichung überschreibt eine erneute Eingabe das Ergebnis (Protokollereignis `assessment.result_recorded`). **Nach der Veröffentlichung ist es gesperrt** (siehe Korrekturanträge unten).
- **Abwesend**: angemeldet, aber nicht zur Prüfung erschienen. Die Eingabe eines Ergebnisses setzt die Anmeldung wieder auf angemeldet.
- **Anmeldung stornieren**: Eine Anmeldung mit Ergebnis kann nicht storniert werden.
- **Ergebnisse veröffentlichen**: Ab jetzt sehen Studierende die Ergebnisse dieses Termins. Nach der Veröffentlichung können Ergebnisse nicht mehr direkt bearbeitet oder gelöscht werden; sie ändern sich über einen **Korrekturantrag** (Entscheidung vom 23.10.2026). Entspricht dem Befehl `assessment:publish-results`.
- Die **Bestehensgrenze** des Termins (ersetzt die der Art): wird verwendet, wenn eine Sitzung die Grenze senkt, weil die Prüfung schwer war. Sie kann **vor der Veröffentlichung** geändert werden, und sobald sie sich ändert, werden alle bereits eingetragenen Ergebnisse dieses Termins anhand der gespeicherten Punkte **neu beurteilt** (die Gesamtpunktzahl gegenüber der neuen Grenze, die Mindestwerte der Bereiche wie bei der Art festgelegt). Mit Begründung hält das Protokollereignis `assessment.pass_score_changed` die alte und die neue Grenze zusammen mit den Matrikelnummern fest, deren Entscheidung sich geändert hat. **Nach der Veröffentlichung** ist sie gesperrt (nur Korrekturanträge). Der Mindestwert pro Bereich kann beim Termin nicht geändert werden (Einstellung der Art).
- **Ergebnisse importieren (CSV)**: Format siehe unten. Zeilen nicht angemeldeter Studierender werden als Fehler übersprungen. Entspricht dem Befehl `assessment:import-results`.

CSV-Format (Kopfzeile, UTF-8):

| Spalte | Inhalt |
|---|---|
| `student_number` | Matrikelnummer (Pflicht) |
| Bereichscode (`LISTENING`, …) | Punkte des Bereichs. Groß-/Kleinschreibung egal |
| `total` | Gesamtpunktzahl für Arten ohne Bereiche |
| `passed` | `Y`/`N`. Pflicht für Arten mit Bestanden/nicht bestanden oder Arten ohne Bestehensgrenze |
| `absent` | `Y` markiert die/den Studierende/n als abwesend |
| `note` | Anmerkung |

Fehlerhafte Zeilen werden übersprungen und mit ihrer Zeilennummer in der Ergebnisbenachrichtigung (Bildschirm) bzw. der Ausgabe (Befehl) gemeldet.

## Ergebniskorrekturen

Das Verfahren, um die Ergebnisse eines veröffentlichten Termins zu ändern. Es funktioniert wie Notenänderungen.

1. **Korrektur beantragen** aus der Liste der Angemeldeten des Termins: die neuen Punkte (oder bestanden oder nicht) und die Begründung. Pro Ergebnis ein offener Antrag. Protokollereignis `assessment.correction_requested`.
2. Unter **Prüfungen > Ergebniskorrekturen** klickt eine Person mit der Berechtigung `Prüfungsarten, Bereiche und Termine verwalten` auf **Genehmigen** (Anmerkung optional) oder **Ablehnen** (Anmerkung erforderlich). **Niemand kann über einen eigenen Antrag entscheiden.**
3. Bei Genehmigung ändert sich das Ergebnis sofort auf die neuen Werte, und die/der Studierende sieht es. Protokollereignisse `assessment.correction_approved`, `assessment.result_corrected`. Die antragstellende Person kann den Antrag bis zur Entscheidung zurückziehen.

Die Liste zeigt standardmäßig offene Anträge, mit den aktuellen und den vorgeschlagenen Werten nebeneinander.

## Ergebnisregister

**Prüfungsergebnisse**: alle Ergebnisse (Termine und extern). Filtern nach Art, Quelle und bestanden. Der Geltungsbereich für das Anzeigen ist derselbe wie bei Studierendenakten.

- **Externes Ergebnis erfassen**: Matrikelnummer, Art (nur Arten, die externe Ergebnisse erlauben), Datum der Prüfung (nicht in der Zukunft), Gesamtpunktzahl, Niveau, bestanden oder nicht, Zertifikatsnummer, Nachweisdatei (PDF, JPG, PNG, 5 MB), Anmerkung. Protokollereignis `assessment.external_recorded`.
- **Nachweis**: öffnet die Datei. Studierende können ihre eigenen öffnen.
- **Entfernen**: Begründung erforderlich, Protokollereignis `assessment.result_removed`. Zum Löschen und erneuten Erfassen eines falsch eingetragenen externen Ergebnisses oder eines Ergebnisses vor der Veröffentlichung. Ergebnisse eines veröffentlichten Termins können nicht entfernt werden (Korrekturantrag).

## Abschlussvoraussetzungen

Wählen Sie im Editor **Abschlussvoraussetzungen** die Anforderungsart **Bestandene Prüfung** und geben Sie die Prüfungsart an. Studierende erfüllen sie mit mindestens einem bestandenen Ergebnis (Termin oder extern) dieser Art. Oben auf der Portalseite **Prüfungen** stehen die vom Studienplan der/des Studierenden verlangten Arten und ob sie bestanden sind. Die Prüfung der Abschlussvoraussetzungen (Phase 11) liest diese Anforderung.

## Portal der Studierenden

**Prüfungen**: Anforderungen des Studienplans und ob sie bestanden sind, zur Anmeldung offene Termine (anmelden, stornieren), meine Anmeldungen, meine Ergebnisse (veröffentlichte Termine und externe Ergebnisse, Bereichspunkte, Nachweis).

## Häufige Fragen

- **Ich muss eine/n Studierende/n außerhalb des Anmeldezeitraums anmelden.** Mitarbeitende melden sie beim Termin unter **Angemeldete > Studierende/n anmelden** an.
- **Ich möchte die Bestehensgrenze nur für diesen Termin ändern.** Tragen Sie die Bestehensgrenze des Termins vor der Veröffentlichung der Ergebnisse ein. Bereits eingetragene Ergebnisse werden sofort anhand der gespeicherten Punkte neu beurteilt (die Mindestpunktzahlen der Bereiche aus der Prüfungsart gelten weiter). Nach der Veröffentlichung ist die Grenze fest; nutzen Sie dann Korrekturanträge.
- **Ich habe Punkte falsch eingetragen.** Vor der Veröffentlichung tragen Sie das Ergebnis mit **Ergebnis eintragen** erneut ein, um es zu überschreiben. Nach der Veröffentlichung stellen Sie einen **Korrekturantrag** und lassen ihn von einer anderen Person genehmigen.
- **Statistik zu Abwesenden.** Filtern Sie die Liste der Angemeldeten nach Status.
