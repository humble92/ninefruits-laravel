<?php

return [
    'resources' => [
        'courses' => [
            'label' => 'Lehrveranstaltung',
            'plural_label' => 'Lehrveranstaltungen',
        ],
        'course_versions' => [
            'label' => 'Version der Lehrveranstaltung',
            'plural_label' => 'Versionen',
        ],
        'course_equivalencies' => [
            'label' => 'Äquivalenz',
            'plural_label' => 'Äquivalente Lehrveranstaltungen',
        ],
        'requirement_sets' => [
            'label' => 'Abschlussvoraussetzungen',
            'plural_label' => 'Abschlussvoraussetzungen',
        ],
    ],
    'sections' => [
        'course' => 'Lehrveranstaltung',
        'first_version' => 'Erste Version',
        'requirements' => 'Anforderungen',
    ],
    'fields' => [
        'assessment_type' => 'Prüfungsart',
        'code' => 'Veranstaltungsnummer',
        'managing_unit' => 'Verantwortliche Einheit',
        'title' => 'Titel',
        'description' => 'Beschreibung',
        'credits' => 'Leistungspunkte',
        'lecture_hours' => 'Vorlesungsstunden',
        'lab_hours' => 'Übungs-/Laborstunden',
        'category' => 'Veranstaltungskategorie',
        'grading_basis' => 'Bewertung',
        'counts_toward_credit_limit' => 'Zählt zur Leistungspunkte-Obergrenze',
        'is_repeatable' => 'Mehrfach mit Leistungspunkten belegbar',
        'requisites' => 'Voraussetzungen',
        'requisite_kind' => 'Art',
        'group_number' => 'Gruppe',
        'required_course' => 'Lehrveranstaltung',
        'minimum_grade' => 'Mindestnote',
        'allows_concurrent' => 'Im selben Semester belegbar',
        'effective_from' => 'Neue Version ab',
        'equivalent_course' => 'Äquivalente Lehrveranstaltung',
        'is_mutual' => 'Gegenseitig',
        'note' => 'Anmerkung',
        'program_role' => 'Rolle im Studium',
        'catalog_year_from' => 'Studienordnungsjahr von',
        'catalog_year_to' => 'Studienordnungsjahr bis',
        'total_credits' => 'Leistungspunkte gesamt',
        'requirement_kind' => 'Anforderung',
        'min_credits' => 'Mindest-Leistungspunkte',
        'min_courses' => 'Mindestanzahl Lehrveranstaltungen',
    ],
    'help' => [
        'counts_toward_credit_limit' => 'Ausschalten für Lehrveranstaltungen außerhalb der Leistungspunkte-Obergrenze des Semesters (z. B. Kurse einer Credit-Bank).',
        'is_repeatable' => 'Eine erneute Belegung bringt erneut Leistungspunkte (z. B. Seminare mit wechselnden Themen), statt als Wiederholung zu gelten.',
        'requisites' => 'Lehrveranstaltungen in derselben Gruppe sind Alternativen (eine genügt); jede Gruppe muss erfüllt sein.',
        'revise' => 'Die aktuelle Version endet am Vortag; die neue Version beginnt als Kopie davon und ihrer Voraussetzungen. Bereits angebotene Kurse behalten ihre Version.',
        'is_mutual' => 'Jede zählt für die andere. Aus: nur die äquivalente Lehrveranstaltung zählt für diese.',
        'catalog_year_to' => 'Leer: gilt auch für spätere Studienordnungsjahre, bis ein anderer Satz beginnt.',
    ],
    'actions' => [
        'revise' => 'Ab einem Datum überarbeiten',
        'add_requirement' => 'Anforderung hinzufügen',
    ],
    'notifications' => [
        'revised' => 'Neue Version erstellt.',
    ],
    'errors' => [
        'no_version_to_revise' => 'An diesem Datum gilt keine Version.',
        'revise_same_start' => 'An diesem Datum beginnt bereits eine Version. Bearbeiten Sie stattdessen diese.',
        'version_overlap' => 'Dieser Zeitraum überschneidet sich mit einer anderen Version der Lehrveranstaltung.',
        'requirement_set_overlap' => 'Diese Studienordnungsjahre überschneiden sich mit einem anderen Anforderungssatz für Studiengang und Rolle.',
    ],
    'import' => [
        'org_unit_required' => 'Neue Lehrveranstaltungen brauchen eine org_unit (Code der verantwortlichen Einheit).',
    ],
    'or' => 'oder',
    'grading_bases' => [
        'graded' => 'Benotet',
        'pass_fail' => 'Bestanden/nicht bestanden',
    ],
    'requisite_kinds' => [
        'prerequisite' => 'Voraussetzung',
        'corequisite' => 'Parallele Belegung',
    ],
    'program_roles' => [
        'primary' => 'Hauptstudiengang',
        'second_major' => 'Zweitfach',
        'minor' => 'Nebenfach',
    ],
    'requirement_kinds' => [
        'category_credits' => 'Leistungspunkte in einer Kategorie',
        'required_courses' => 'Pflichtveranstaltungen',
        'course_group' => 'Wahl aus Lehrveranstaltungen',
        'assessment_pass' => 'Bestandene Prüfung',
    ],
];
