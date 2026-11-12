# 9. Import CSV

Utilisez le bouton **Importer un CSV** en haut des écrans des unités organisationnelles, des formations, des bâtiments, des salles et des nomenclatures (autorisation `Importer des données de référence depuis un CSV`). Les listes des dossiers étudiants et des enseignants disposent du même bouton (autorisations `Créer et modifier des dossiers étudiants` et `Gérer les enseignants` respectivement). Les fichiers très volumineux peuvent être importés de la même manière par l’exploitant informatique avec la commande `reference:import`.

## Règles communes

- La première ligne contient les noms des colonnes (tableaux ci-dessous). L’encodage est UTF-8 ; dans Excel, enregistrez au format « CSV UTF-8 ».
- Une ligne dont le code existe déjà **met à jour** cet enregistrement. Importer à nouveau le même fichier ne crée pas de doublons.
- Les colonnes par langue prennent la forme `colonne_langue` : `name_en`, `name_ko`, `name_zh_CN`. Les nouvelles lignes exigent le nom en anglais (`name_en`). Les cellules vides n’effacent pas les traductions existantes.
- Les dates s’écrivent `2026-03-01` ; les valeurs oui/non `1`/`0` ou `true`/`false`.
- Les imports s’exécutent en arrière-plan. À la fin, le résultat arrive sous forme de notification en haut à droite du panneau d’administration ; si des lignes ont échoué, **Télécharger les informations sur les lignes qui ont échoué** fournit un CSV avec le motif pour chaque ligne. Corrigez-le et importez uniquement ce fichier.
- Si les noms de colonnes à l’écran diffèrent, vous pouvez faire correspondre les colonnes manuellement dans la boîte de dialogue d’import.

## Formats des colonnes

| Type | Colonnes obligatoires | Colonnes facultatives |
|---|---|---|
| Unités organisationnelles | `code`, `type` (code du type d’unité organisationnelle), `name_en`, `effective_start_date` | `parent` (code du parent), `name_ko` etc., `short_name_*`, `effective_end_date`, `sort_order` |
| Formations | `code`, `org_unit` (code de l’unité organisationnelle), `degree_level`, `name_en`, `effective_start_date` | `degree_title_*`, `standard_duration_years`, `effective_end_date`, `allows_second_major`, `allows_minor` |
| Bâtiments | `code`, `name_en` | `name_*`, `address`, `is_active` |
| Salles | `building` (code du bâtiment), `code` (numéro de salle) | `name_*`, `room_type` (code du type de salle), `org_unit`, `capacity`, `is_schedulable`, `is_active` |
| Valeurs de code | `type` (code de la liste), `code`, `name_en` | `name_*`, `sort_order`, `is_active` |

Valeurs de `degree_level` : `associate`, `bachelor`, `master`, `doctorate`, `certificate`, `non_degree`

Pour les unités organisationnelles, les unités parentes doivent venir en premier (triez dans l’ordre université → faculté → département).

## Exemple : unités organisationnelles

```csv
code,type,parent,name_en,name_ko,effective_start_date
UNI,university,,Sample University,샘플대학교,1990-03-01
ENG,college,UNI,College of Engineering,공과대학,1990-03-01
CSE,department,ENG,Computer Science,컴퓨터공학과,1995-03-01
```

Les imports des étudiants et des enseignants (phase 2) et des cours (phase 4) sont ajoutés en même temps que ces fonctions.

## Étudiants et enseignants

Les dossiers étudiants sont rapprochés et mis à jour par `student_number`, les enseignants par `employee_number`. Les deux types partagent les colonnes relatives à la personne.

| Type | Colonnes obligatoires pour les nouvelles lignes | Colonnes facultatives |
|---|---|---|
| Dossiers étudiants | `student_number`, `program` (code de la formation), `admission_date`, `family_name` | `catalog_year` (année d’admission si vide), `year_level`, `status`, `graduation_date`, `institutional_email` |
| Enseignants | `employee_number`, `org_unit` (code de l’unité organisationnelle), `employment_type`, `family_name` | `academic_title_*`, `hire_date`, `end_date` |
| Colonnes relatives à la personne (communes) | — | `given_name`, noms par langue tels que `name_ko`, `birth_date`, `gender`, `email`, `phone`, `national_id` |

- Valeurs de `status` : `enrolled`, `on_leave`, `withdrawn`, `dismissed`, `graduated` (`graduation_date` est obligatoire pour `graduated`). Un statut modifié par import est traité comme une **correction de données** et ne crée aucun historique de changement de statut. Les changements réels sont enregistrés à l’écran ([chapitre 13](13-status-changes-and-advisors.md)).
- Valeurs de `employment_type` : `full_time`, `part_time`, `adjunct`, `visiting`, `emeritus`. Valeurs de `gender` : `female`, `male`, `other`, `undisclosed`.
- Si le `national_id` d’un nouveau dossier étudiant correspond à une personne existante, le dossier est rattaché à cette personne (réadmission). Sinon, une nouvelle personne est créée. Lors d’une mise à jour, les cellules vides n’effacent pas les valeurs existantes.
- Les étudiants et enseignants importés n’ont pas de compte. Envoyez-leur une invitation avec les [invitations à créer un compte](14-merge-and-invitations.md#invitations-à-créer-un-compte).

```csv
student_number,program,admission_date,year_level,status,family_name,given_name,name_ko,birth_date
202600001,B-CSE,2026-03-02,1,enrolled,Kim,Minsu,김민수,2007-05-01
```

## Cours

**Importer un CSV** en haut de la liste des cours (autorisation `Gérer les cours, leurs versions et leurs conditions préalables`) ou `reference:import courses`. Une ligne correspond à une version de cours.

| Colonnes obligatoires | Colonnes facultatives |
|---|---|
| `code`, `effective_start_date`, `title_en`, `credits` (nouvelle version) | `org_unit` (obligatoire pour un nouveau cours ; code de l’unité organisationnelle gestionnaire), `effective_end_date`, `title_*`, `lecture_hours`, `lab_hours`, `category` (code de la catégorie de cours), `grading_basis` (`graded`, `pass_fail`), `counts_toward_credit_limit` |

- Les cours sont rapprochés par `code`, les versions par `code` + `effective_start_date`. Importer à nouveau le même fichier les met à jour.
- Un historique de révisions peut être chargé depuis un seul fichier : indiquez les lignes du même code pour chaque période (mettez une `effective_end_date` sur la version antérieure).
- Une ligne dont la période chevauche une autre version échoue. Si la ligne d’un nouveau cours échoue, le cours n’est pas créé non plus.
- Les conditions préalables, les cours équivalents et les conditions d’obtention du diplôme sont saisis à l’écran ([chapitre 17](17-courses-and-curriculum.md)).

```csv
code,org_unit,effective_start_date,effective_end_date,title_en,title_ko,credits,category
CSE2010,CSE,2015-03-01,2026-02-28,Data Structures,자료구조,3,MAJ_REQ
CSE2010,,2026-03-01,,Data Structures,자료구조,4,MAJ_REQ
```

## Blocages d’inscription

**Importer un CSV** en haut de la liste **Inscriptions pédagogiques > Blocages** (autorisation `Poser et lever des blocages, accorder des dérogations aux règles`) ou `reference:import registration_holds`. Sert à charger les données `SRDENY` de l’ancien système ou la liste des étudiants en défaut de paiement transmise par le service financier.

| Colonnes obligatoires | Colonnes facultatives |
|---|---|
| `student_number`, `hold_type` (`unregistered`, `financial`, `academic`, `administrative`, `other`), `reason` | `starts_at` (par défaut : heure de l’import), `ends_at`, `blocks_registration` (par défaut 1), `blocks_grades`, `blocks_certificates` |

- Chaque ligne crée un nouveau blocage (source `import`). Importer deux fois le même fichier crée les blocages deux fois ; vérifiez donc la liste avant de réimporter.
- Les lignes comportant un numéro d’étudiant inconnu échouent. Les blocages sont levés à l’écran avec un motif ([chapitre 20](20-registration.md)).

```csv
student_number,hold_type,reason,ends_at
202312345,financial,2027-1 등록금 미납,
202254321,academic,학사경고 상담 필요,2027-03-15
```
