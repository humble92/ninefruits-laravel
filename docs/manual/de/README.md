# Handbuch zum Studierendenverwaltungssystem (Deutsch)

Für die Mitarbeitenden, die das System im Alltag verwalten, und für den IT-Betrieb, der es installiert, ausrollt und wartet. Die Oberfläche ist auf Deutsch verfügbar (Benutzermenü > Sprache).

Weitere Sprachen: [한국어](../ko/README.md), [English](../en/README.md), [简体中文](../zh_CN/README.md), [ລາວ](../lo/README.md), [Filipino](../fil/README.md), [Français](../fr/README.md), [日本語](../ja/README.md), [Монгол](../mn/README.md), [Bahasa Melayu](../ms/README.md), [नेपाली](../ne/README.md), [Русский](../ru/README.md), [ไทย](../th/README.md), [Türkçe](../tr/README.md), [Oʻzbek](../uz/README.md), [Tiếng Việt](../vi/README.md). Die koreanische Fassung ist das Original; die übrigen Ausgaben sind Übersetzungen, die noch auf eine Prüfung durch Muttersprachler warten.

## Administratorhandbuch

[admin/README.md](admin/README.md) — Kapitel 1 bis 28, vom Einstieg sowie Benutzern und Rollen über die Einrichtung der Hochschule, Zulassung, Belegung, Noten und Bescheinigungen bis zu Studienabschluss und LMS-Anbindung.

## Betrieb

| Datei | Inhalt |
|---|---|
| [installation.md](operations/installation.md) | Voraussetzungen, Datenbankrollen, Umgebungskonfiguration, Erstinstallation und Updates |
| [deployment-checklist.md](operations/deployment-checklist.md) | PHP-Version und Härtung der php.ini, .env für die Produktion, Ablauf des Deployments, regelmäßige Prüfungen |
| [commands.md](operations/commands.md) | Referenz aller Artisan-Befehle: Optionen, Protokolleinträge, Exit-Codes, Beispiele |
| [scheduled-jobs.md](operations/scheduled-jobs.md) | Dauerhaft laufende Prozesse, zeitgesteuerte Jobs und Queue-Jobs, Erkennung von Fehlern |
| [backup-restore.md](operations/backup-restore.md) | Was gesichert wird, Beispiele für Dump und Wiederherstellung, Wiederherstellungsübungen |
| [security-keys.md](operations/security-keys.md) | Die drei geheimen Schlüssel, ihre Aufbewahrung, Wechsel des Schlüssels für personenbezogene Daten |
| [audit-and-maintenance.md](operations/audit-and-maintenance.md) | Hash-Kette des Protokolls, Aufbewahrung und Bereinigung, Bereinigung von Dateien, Systemprüfung |
| [load-testing.md](operations/load-testing.md) | k6-Lasttest für die Belegungsspitze und die Entscheidung über Octane |
| [troubleshooting.md](operations/troubleshooting.md) | Häufige Symptome, was zu prüfen ist, Nachverfolgung einzelner Requests |
