# 25. Salle de classe de l’enseignant et présences

Menu : les enseignants utilisent **Mes enseignements > Liste et présences** dans le portail, les étudiants **Présences** dans le portail. Le personnel utilise **Séances** en bas de l’écran **Cours et groupes > Groupes > Modifier**.

| Qui | Ce qu’ils peuvent faire |
|---|---|
| Enseignants affectés | Liste des étudiants (avec photos) et CSV, feuille d’émargement PDF, génération, ajout et annulation de séances, saisie des présences |
| Personnel disposant de `Planifier les groupes (enseignants, horaires, salles)` (dans son périmètre) | Comme les enseignants + séances dans le panneau d’administration |
| Personnel disposant de `Consulter les groupes` (dans son périmètre) | Liste des étudiants, feuille d’émargement, consultation des présences |
| Étudiants | Leurs propres présences |

Les fonctions de salle de classe telles que les annonces, les devoirs et les supports sont laissées au LMS (plan de portage, section 12).

## Différences avec l’ancien système

- **L’ancien système (YUSTIC) n’enregistrait pas les présences.** Sa « feuille d’émargement » était un PDF qui imprimait des cases vides (de 27 à 34, selon la copie) à côté de la liste des étudiants, sans dates ni totaux. Le nouveau système crée des **séances** à partir des horaires, enregistre présent, en retard, absent ou excusé pour chaque séance, et imprime les relevés et les totaux sur la feuille d’émargement PDF.
- **Liste des étudiants.** L’écran enseignant de l’ancien système n’affichait que les numéros d’étudiant et les noms (et exposait le numéro d’enregistrement de résident et l’adresse dans une fenêtre contextuelle) et ne permettait pas de téléchargement Excel. Le nouveau système n’affiche que le numéro d’étudiant, le nom, la photo, la formation, l’année d’études et l’e-mail de contact, et permet le téléchargement en CSV. Les téléchargements sont enregistrés dans le journal d’audit.
- **Accès des enseignants à leurs étudiants.** Une politique RLS a été ajoutée pour que les enseignants puissent voir les dossiers étudiants et les personnes des étudiants de leurs groupes (auparavant, ils ne voyaient que leurs étudiants suivis, ce qui laissait les noms vides même sur l’écran de saisie des notes).

## Séances

- **Générer** : une séance pour chaque occurrence des horaires hebdomadaires (jour, heure, créneau). Les enseignants utilisent le bouton **Générer à partir des horaires** ; le personnel utilise **Séances > Générer les séances** dans le panneau d’administration ou la commande `attendance:generate-sessions {term}` (semestre entier). Les séances et présences existantes ne sont pas modifiées ; la génération peut donc être relancée après une modification des horaires.
- **Ajouter une séance** : un cours en dehors des horaires, comme un cours de rattrapage. Uniquement à des dates comprises dans la session.
- **Annuler** : annule la séance avec un motif. La séance est conservée mais exclue des totaux. **Rétablir** la réactive.
- Il n’y a pas de calendrier des jours fériés. Annulez les séances tombant un jour férié (voir la section 13.2 ; cela deviendra automatique une fois un calendrier des semestres ajouté).

## Saisie des présences

Sous **Présences** de la séance, choisissez présent, en retard, absent ou excusé pour chaque étudiant et ajoutez une remarque (200 caractères). Le plus rapide est d’utiliser **Tous présents** puis de modifier les exceptions.

- Les présences peuvent être saisies à partir du jour de la séance (pas pour les séances futures).
- Un nouvel enregistrement n’enregistre que les étudiants modifiés, et l’événement d’audit (`attendance.recorded`) conserve l’ancienne et la nouvelle valeur.
- Effacer le statut remet l’étudiant à l’état « non saisie ».

## Totaux

| Élément | Définition |
|---|---|
| Séances tenues | Séances non annulées dont la date est antérieure ou égale à aujourd’hui |
| Taux de présence | (présent + en retard + excusé) ÷ séances tenues × 100 |
| Non saisie | Séances tenues sans enregistrement |

Il n’existe pas de règle convertissant un nombre de retards en absence ni attribuant un F au-delà d’un tiers d’absences. Cela diffère d’une université à l’autre ; les enseignants tiennent donc compte eux-mêmes des présences dans la note (point de revue 13.5).

## Feuille d’émargement PDF et liste des étudiants CSV

Les deux téléchargements sont enregistrés dans le journal d’audit (`attendance.sheet_exported` ; la liste des étudiants CSV comme auparavant). Les valeurs CSV commençant par `=`, `+`, `-` ou `@` sont exportées précédées d’une apostrophe simple afin de ne pas être exécutées comme des formules.

- **Feuille d’émargement (PDF)** : A4 paysage. Les étudiants en lignes, les séances en colonnes (séances annulées exclues), marques O/△/X/E et totaux par étudiant. Les séances à venir restent vides, la feuille sert donc aussi à la saisie manuscrite.
- **Liste des étudiants (CSV)** : numéro d’étudiant, nom, formation, année d’études, e-mail de contact, statut d’inscription. UTF-8 avec BOM, il s’ouvre donc directement dans Excel. Chaque téléchargement est enregistré dans l’événement d’audit `attendance.roster_exported`.

## Portail étudiant

**Présences** : par semestre et par cours, le nombre de présences, de retards, d’absences et d’absences excusées, le taux de présence et le relevé par séance (uniquement ce que l’enseignant a saisi).

## Procédure d’exploitation

1. Avant le début du semestre, arrêtez les horaires des groupes ([chapitre 18](18-sections-and-timetables.md)).
2. Créez les séances avec `php artisan attendance:generate-sessions {term} --actor=...` (les enseignants peuvent aussi les créer à l’écran).
3. Relancez la commande lorsque les horaires changent. Les séances des horaires supprimés ne sont pas supprimées automatiquement ; annulez-les.
