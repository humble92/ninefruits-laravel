# Référence des commandes Artisan

- Public : exploitants informatiques
- Périmètre : toutes les commandes applicatives enregistrées dans ce dépôt. Toute nouvelle commande doit aussi être ajoutée ici, sinon la CI échoue (`tests/Feature/Tooling/CommandDocumentationTest.php`).

## Conventions communes

Toutes les commandes s’exécutent avec `php artisan {command}`. Dans l’environnement de développement : `./vendor/bin/sail artisan {command}`.

| Élément | Règle |
|---|---|
| Enregistrement d’audit | Les commandes qui modifient des données (« Audit : enregistré » ci-dessous) écrivent à chaque exécution la commande, les arguments, les options, l’utilisateur du système d’exploitation, le code de sortie et le résultat dans le journal d’activité (journal `command`). Consultez-les dans **Audit > Journal d’activité** du panneau d’administration |
| Identifiant d’exécution | Chaque exécution reçoit un identifiant commençant par `cmd-` à la place de l’identifiant de requête. Le journal d’activité, les modifications de données effectuées par cette exécution et les tâches de file d’attente qu’elle a lancées portent toutes le même identifiant : la question « quelle commande a fait cette modification ? » se résout donc par l’identifiant. `--actor` est une valeur fournie par l’exploitant ; elle n’est donc pas reportée dans la colonne utilisateur des modifications de données (l’acteur réel est l’utilisateur du système d’exploitation) |
| `--actor=<email>` | Commun à toutes les commandes auditées. Enregistre le compte de l’administrateur responsable. Une adresse e-mail inconnue interrompt l’exécution |
| `--dry-run` | Affiche le résultat sans rien modifier. Disponible pour les opérations difficiles à annuler |
| `--force` | S’exécute sans demande de confirmation (pour les planifications et les scripts de déploiement) |
| `--reason=` | Disponible pour les commandes qui exigent un motif |
| Codes de sortie | 0 succès, 1 échec ou annulation, 2 une commande de contrôle a détecté un problème |
| Contexte de base de données | Pendant l’exécution de la commande, l’acteur de la session de base de données est défini à `system:command:{command}` et apparaît dans les modifications de données (déclencheur) |

## Installation et démonstration

### `setup:install`

- Objet : nouvelles installations et mises à jour. Exécute successivement les migrations, les données essentielles, les valeurs par défaut de l’établissement, le profil d’installation et l’administrateur initial. Peut être relancée sans risque (les données existantes et les valeurs modifiées par les administrateurs sont conservées).
- Quand : à la première installation et à chaque déploiement.
- Options : `--institution-name=`, `--locale=` (langue par défaut), `--timezone=` (par exemple `Asia/Seoul`), `--profile=*` (clé du profil d’installation), `--grade-scale=` (barème de notation `4.5` ou `4.3`. Non modifiable une fois défini. S’il est vide, le barème du premier profil s’applique), `--admin-email=`, `--admin-name=`, `--force` (exécution sans questions. Le mot de passe de l’administrateur est lu sur l’entrée standard).
- Audit : enregistré
- Exemples

  ```bash
  php artisan setup:install
  echo "$ADMIN_PASSWORD" | php artisan setup:install --force --locale=ko --timezone=Asia/Seoul \
      --institution-name="Demo University" --admin-email=admin@example.ac.kr --admin-name="Administrator"
  ```

- Lecture de la sortie : `DONE`/`FAIL` pour chaque étape. `Administrator exists` signifie qu’une attribution d’administrateur système existe déjà ; la création a donc été ignorée.
- En cas d’échec : si l’erreur signale une clé manquante, placez les valeurs générées par `key:generate` et `pii:generate-key` dans la configuration d’environnement. Un échec de migration signifie généralement que les rôles de base de données ne sont pas préparés (voir le manuel d’installation).

### `setup:demo`

- Objet : crée, à l’aide de factories, les données d’une université fictive. Affiche les comptes de démonstration fixes par rôle (`registrar@demo.test`, etc.).
- Quand : environnements de développement, de formation et de démonstration. **Elle ne s’exécute pas en production (`APP_ENV=production`), même avec `--force`.**
- Options : `--size=tiny|small|medium|large` (environ 20/100/2 000/20 000 étudiants. `tiny` sert aux tests et aux vérifications rapides), `--seed=` (une même valeur produit les mêmes données), `--force`.
- `--student-accounts` : crée un compte pour chaque étudiant actif (`student{numéro d’étudiant}@demo.test`, mot de passe de démonstration) et les écrit dans `storage/app/private/load-accounts.csv`. Pour les tests de charge ([Tests de charge](load-testing.md)).
- Audit : enregistré
- Exemple : `php artisan setup:demo --size=small --seed=42`
- En cas d’échec : si les données essentielles manquent, les comptes de démonstration n’ont pas de rôle et sont ignorés. Exécutez d’abord `setup:install`.

## Données de référence et semestres

### `reference:import`

- Objet : importe des données de référence depuis un CSV. Applique les mêmes règles d’import que **Importer un CSV** dans le panneau d’administration (les lignes existantes sont rapprochées par code et mises à jour, les cellules de traduction vides conservent la valeur existante, seules les lignes en échec sont enregistrées à part).
- Arguments : `{type}` parmi `org_units`, `programs`, `buildings`, `rooms`, `lookup_values`, `students`, `instructors`, `courses`, `registration_holds` / `{file}` un CSV UTF-8 avec ligne d’en-tête.
- Options : `--actor=` (obligatoire. L’enregistrement de l’import et la notification de fin sont adressés à ce compte), `--chunk=100` (lignes par tâche de file d’attente), `--dry-run` (contrôle de l’en-tête et comptage des lignes uniquement).
- Audit : enregistré (type, nom du fichier, nombre de lignes, nombre de lignes en échec)
- Format des colonnes : [Manuel d’administration, chapitre 9](../admin/09-csv-import.md).
- Codes de sortie : si la file d’attente est synchrone, le résultat s’affiche immédiatement et le code de sortie vaut 2 en présence de lignes en échec. Si elle est asynchrone (Horizon), l’import est seulement accepté et le code de sortie vaut 0. Le résultat arrive sous la forme d’un CSV des lignes en échec dans les notifications du panneau d’administration du compte `--actor`.
- Exemple : `php artisan reference:import org_units storage/app/org-units.csv --actor=admin@example.ac.kr --dry-run`

### `term:inspect`

- Objet : affiche le semestre en cours à un instant donné et les périodes de gestion ouvertes (nature, semestre, critères de population, heure de fin). À utiliser pour vérifier la configuration des semestres et des périodes ou pour répondre à des questions comme « pourquoi l’inscription n’est-elle pas ouverte ? ».
- Options : `--at=` (par défaut maintenant. Par exemple `"2027-02-15 09:00"`. Sans fuseau horaire, la valeur est interprétée dans le fuseau du serveur (UTC). Un fuseau peut être indiqué comme dans `"2027-02-15 09:00 Asia/Seoul"`).
- Audit : non enregistré
- Lecture de la sortie : les décisions de date utilisent le fuseau horaire de l’établissement. Les périodes des semestres au statut planifié ou archivé ne s’ouvrent jamais.

## Sécurité

### `security:create-administrator`

- Objet : crée un compte doté du rôle d’administrateur système. Généralement appelée par `setup:install`.
- Options : `--email=`, `--name=`, `--password-stdin` (mot de passe lu sur l’entrée standard. Obligatoire pour les exécutions non interactives).
- Audit : enregistré (identifiant de l’utilisateur créé)
- Exemple : `php artisan security:create-administrator --email=admin@example.ac.kr --name="Administrator"`
- En cas d’échec : `system_administrator role is missing` signifie que `setup:install` ou `db:seed` doit être exécutée d’abord. Le mot de passe doit comporter au moins 12 caractères.

### `security:sync-permissions`

- Objet : aligne la liste des autorisations en base de données sur le catalogue d’autorisations du code. Les nouvelles autorisations sont créées ; celles absentes du catalogue sont supprimées (et retirées de tous les rôles). Les rôles système reçoivent toujours toutes les autorisations.
- Quand : après le déploiement (`setup:install` le fait lors de l’étape des données essentielles).
- Options : `--dry-run` (affiche seulement la liste des créations et suppressions), `--force` (ignore la confirmation de suppression).
- Audit : enregistré (autorisations créées/supprimées)
- Lecture de la sortie : lignes `create` et `delete`. Une demande de confirmation apparaît en cas de suppressions.
- En cas d’échec : répondre « non » à la confirmation termine avec le code 1 sans rien modifier.

### `security:reset-mfa`

- Objet : réinitialise la double authentification d’un utilisateur (application d’authentification, codes par e-mail, codes de récupération, appareils mémorisés). L’utilisateur s’enrôle de nouveau à sa prochaine connexion. À utiliser lorsqu’aucun administrateur ne peut se connecter au panneau d’administration (par exemple, l’unique administrateur a perdu son téléphone). Sinon, utilisez **Utilisateurs > Réinitialiser la double authentification** dans le panneau d’administration.
- Arguments/options : `{user}` adresse e-mail de l’utilisateur, `--reason=` (obligatoire), `--actor=` (obligatoire. L’administrateur qui a décidé de la réinitialisation).
- Audit : enregistré (dans le journal d’activité et dans le journal de sécurité, avec le motif)
- Exemple : `php artisan security:reset-mfa admin@example.ac.kr --reason="Phone lost, identity verified by phone call" --actor=it-lead@example.ac.kr`

### `security:unlock-user`

- Objet : débloque, avant la fin de la période de verrouillage, un compte verrouillé à la suite de mots de passe erronés répétés. Équivaut à **Utilisateurs > Débloquer la connexion** dans le panneau d’administration.
- Arguments/options : `{user}` adresse e-mail de l’utilisateur, `--reason=` (obligatoire).
- Audit : enregistré (également dans le journal de sécurité)
- Lecture de la sortie : `The account is not locked.` signifie que rien n’a été modifié.
- Remarque : les seuils de verrouillage (tentatives échouées, durée du verrouillage) se règlent dans **Paramètres > Méthodes de connexion** du panneau d’administration.

## Personnes et dossiers étudiants

### `people:find-duplicates`

- Objet : liste les personnes qui semblent être une même personne. Les critères sont un numéro d’identification national identique (empreinte de recherche), ou un nom romanisé et une date de naissance identiques. Les personnes fusionnées sont exclues.
- Options : `--limit=100` (nombre de groupes à afficher).
- Audit : non enregistré (lecture seule)
- Codes de sortie : 2 en présence de doublons potentiels.
- Suite à donner : examinez chaque groupe dans **Personnes** du panneau d’administration et, s’il s’agit de la même personne, lancez **Fusionner un doublon** depuis la page de la personne à conserver. Une fusion est irréversible ([Manuel d’administration, chapitre 14](../admin/14-merge-and-invitations.md)).

## Admissions

### `admission:matriculate`

- Objet : crée les dossiers étudiants (nouveaux numéros d’étudiant) des candidats dont l’inscription est confirmée dans une campagne d’admission. Équivaut à l’action groupée **Créer le dossier étudiant** dans **Admissions > Candidatures** du panneau d’administration. Chaque candidat est traité séparément ; un échec n’arrête donc pas les autres.
- Arguments/options : `{cycle}` code de la campagne d’admission, `--term=` (obligatoire. Code du semestre d’admission, par exemple `2027-SP`. La date d’admission est le premier jour du semestre), `--actor=` (obligatoire. Un compte disposant de l’autorisation « Créer les dossiers étudiants des candidats admis ». Seuls les candidats compris dans le périmètre de cette autorisation pour ce compte sont traités), `--dry-run` (liste des candidats concernés uniquement).
- Audit : enregistré (campagne, semestre, nombre de candidats concernés, nombre de dossiers créés, numéros des candidatures en échec). Un enregistrement d’activité (`application.matriculated`) est également écrit pour chaque candidat.
- Codes de sortie : 2 si un candidat a échoué.
- Exemple : `php artisan admission:matriculate DEMO-2027-REG --term=2027-SP --actor=admissions@example.ac.kr --dry-run`
- En cas d’échec : `not ready` signifie qu’un dossier étudiant existe déjà ou que le candidat n’est pas au statut inscription confirmée ; `program is not in effect` signifie que la formation n’est pas en vigueur à la date d’admission (vérifiez la période de validité dans Données de référence > Formations).

### `admission:expire-drafts`

- Objet : marque comme **Expirée** les candidatures en brouillon des campagnes clôturées et celles restées inactives pendant la durée configurée (**Paramètres > Admissions > Faire expirer les brouillons inactifs après (jours)**, 90 jours par défaut). Les candidatures expirées n’empêchent pas une nouvelle candidature dans la même campagne.
- Quand : planifiée (tous les jours à 03:10, [Tâches planifiées](scheduled-jobs.md)).
- Options : `--dry-run` (comptage uniquement).
- Audit : enregistré (nombre de candidatures expirées)

## Groupes

### `section:copy`

- Objet : lance la planification d’un nouveau semestre à partir des groupes d’un semestre précédent. Équivaut à **Groupes > Copier depuis un autre semestre** dans le panneau d’administration. Copie comme brouillons les groupes non annulés, avec les enseignants (s’ils sont toujours en poste), les horaires et les salles, les places réservées (heures de libération décalées de l’écart entre les dates de début des semestres) et le syllabus (comme brouillon privé). La version du cours est remplacée par celle en vigueur au début de la nouvelle session.
- Arguments/options : `{from-term}`, `{to-term}` codes de semestre (par exemple `2026-FA`), `--org-unit=*` (uniquement les groupes de ce code d’unité organisationnelle), `--dry-run` (résultats uniquement, rien n’est enregistré), `--actor=` (obligatoire. Seuls les groupes compris dans le périmètre de l’autorisation « Planifier les groupes » de ce compte sont copiés).
- Audit : enregistré (nombre de groupes copiés, ignorés et à revoir)
- Lecture de la sortie : `skipped` signifie que le semestre cible possède déjà le même code de groupe ou qu’aucune version du cours n’est en vigueur ; `note` signifie que le groupe a été copié mais qu’un élément a été omis (copié sans salle parce que la salle est déjà occupée, enseignant parti à la retraite, etc.). Les sessions sont mises en correspondance par code ; à défaut, le groupe est placé dans la session couvrant tout le semestre.
- Exemple : `php artisan section:copy 2026-FA 2027-FA --actor=academic@example.ac.kr --dry-run`

### `schedule:check-conflicts`

- Objet : contrôle les groupes d’un semestre avant la publication de l’emploi du temps. La double réservation des salles et des enseignants est déjà empêchée par la base de données ; cette commande détecte les problèmes que la base autorise : capacité supérieure à celle de la salle, version du cours non en vigueur à la date de début de la session, enseignant parti à la retraite, places réservées dépassant la capacité, dates de séance hors de la session. Elle contrôle aussi les conflits de salles dans des données arrivées sans contraintes, par exemple lors d’une restauration de sauvegarde.
- Arguments : `{term}` code du semestre.
- Audit : non enregistré (lecture seule)
- Codes de sortie : 2 en présence de problèmes. Les groupes qui n’ont pas encore d’enseignant ou de salle sont affichés à titre d’information seulement.

## Inscription pédagogique

### `registration:preflight`

- Objet : contrôle un semestre avant l’ouverture des inscriptions. Affiche les périodes d’inscription et d’ajout/retrait, le jeu de règles, le nombre de groupes publiés, les groupes de capacité 0, les places réservées dépassant la capacité, les versions de cours non en vigueur à la date de début de la session, le nombre d’étudiants actifs non couverts par un plafond de crédits et le nombre d’étudiants faisant l’objet d’un blocage d’inscription.
- Arguments : `{term}` code du semestre (par exemple `2027-SP`).
- Audit : non enregistré (lecture seule)
- Codes de sortie : 2 en présence de problèmes. L’absence de jeu de règles est signalée à titre d’information et non comme un problème (les valeurs par défaut des règles s’appliquent).
- Exemple : `php artisan registration:preflight 2027-SP`

### `registration:simulate`

- Objet : montre, règle par règle, ce qui se passerait si l’étudiant s’inscrivait au groupe maintenant (ou à un instant donné). Rien n’est enregistré et aucune tentative n’est consignée. À utiliser pour les questions du type « pourquoi ne puis-je pas m’inscrire ? ».
- Arguments : `{student-number}` numéro d’étudiant, `{section}` `code du cours-code du groupe` (par exemple `CSE2010-01`).
- Options : `--term=` (code du semestre. Par défaut, le semestre le plus récent comportant ce groupe), `--at=` (instant d’évaluation. Par exemple `"2027-02-10 10:00 Asia/Seoul"`).
- Audit : non enregistré (lecture seule)
- Lecture de la sortie : pour chaque règle, réussite/échec, blocage/avertissement et le message. Une règle couverte par une dérogation apparaît en échec mais ne bloque pas. La capacité est affichée séparément sous le tableau des règles.
- Codes de sortie : 2 si l’inscription serait refusée.

### `waitlist:promote`

- Objet : inscrit dans l’ordre les étudiants en liste d’attente dans les groupes qui ont des places libres. Toutes les règles sont de nouveau contrôlées lors de la promotion ; les entrées de liste d’attente qui échouent à une règle en sont retirées (avec le motif enregistré). Les listes d’attente des groupes dont les périodes d’inscription et d’ajout/retrait sont toutes deux fermées expirent. Les étudiants reçoivent le résultat par e-mail.
- Arguments/options : `{term?}` code du semestre (par défaut : tous les semestres comportant des entrées de liste d’attente), `--actor=`.
- Audit : enregistré (nombre de groupes, de promotions, de retraits et d’expirations)
- Quand : lors d’une désinscription, d’un abandon ou d’une libération de places réservées, le groupe concerné est traité immédiatement par une tâche de file d’attente. Cette commande est le filet de sécurité planifié toutes les heures qui traite ce qui aurait été manqué.
- Exemple : `php artisan waitlist:promote 2027-SP --actor=registrar@example.ac.kr`

### `registration:release-reserved-seats`

- Objet : marque comme traitées les places réservées dont l’heure de libération est passée (par exemple les places réservées aux étudiants de la majeure) et met en file d’attente la tâche de promotion de la liste d’attente pour ces groupes. Les places réservées deviennent des places générales dans le calcul dès que l’heure de libération est passée ; cette commande ne fait donc que transmettre les places à la liste d’attente.
- Audit : non enregistré (exécutée toutes les 5 minutes. Les résultats des promotions figurent dans les enregistrements d’inscription)
- Quand : planifiée (toutes les 5 minutes).

## Notes

### `term:close`

- Objet : clôture un semestre. Toutes les notes du semestre doivent être définitives. Pour chaque étudiant, elle calcule le bilan semestriel (crédits, moyenne du semestre et moyenne cumulée), détermine la situation académique (situation normale, avertissement académique, examen pour exclusion) et l’éligibilité au tableau d’honneur (lot de file d’attente), puis établit le classement du tableau d’honneur par formation, fige les bilans semestriels comme documents officiels et fait passer le semestre au statut **Notes clôturées**. Équivaut à **Notes > Bilans semestriels > Clôturer le semestre** dans le panneau d’administration.
- Arguments/options : `{term}` code du semestre, `--dry-run` (vérifie seulement si le semestre peut être clôturé), `--reason=` (motif conservé dans l’enregistrement), `--actor=`.
- Audit : enregistré (nombre d’étudiants, nombre de groupes dont les notes ne sont pas définitives). À la fin de la clôture, l’enregistrement d’audit `term.closed` contient le nombre d’avertissements académiques, d’examens pour exclusion et d’inscriptions au tableau d’honneur.
- Codes de sortie : 2 si certains groupes ont des notes non définitives (le nombre de notes non définitives par groupe est affiché). 1 si le semestre n’est pas au statut publié.
- Quand : une fois la saisie et la validation des notes terminées. Suivez l’avancement du lot dans Horizon. Sans worker de file d’attente, le statut du semestre ne change pas.
- Exemples : `php artisan term:close 2026-FA --dry-run`, `php artisan term:close 2026-FA --reason="Fall 2026 grades closed" --actor=registrar@example.ac.kr`

### `grades:recalculate`

- Objet : recalcule les bilans semestriels (crédits, moyenne du semestre et moyenne cumulée) à partir des notes définitives. À utiliser pour appliquer une politique de notation modifiée (nombre de décimales de la moyenne, traitement des reprises) aux semestres passés. La situation académique et le tableau d’honneur conservent les valeurs décidées lors de la clôture du semestre. Une correction de note approuvée recalcule automatiquement l’étudiant concerné ; cette commande n’est donc pas nécessaire dans ce cas.
- Options : `--term=` (étudiants ayant des notes dans ce semestre, à partir de ce semestre), `--student-number=` (un seul étudiant), `--reason=` (obligatoire), `--actor=`.
- Audit : enregistré (nombre d’étudiants, motif)
- Exemple : `php artisan grades:recalculate --term=2026-SP --reason="GPA changed to three decimal places"`

### `grades:remind-missing`

- Objet : envoie un e-mail aux enseignants des groupes dont les notes n’ont pas encore été soumises (non saisies ou enregistrées en brouillon). Les groupes sont regroupés par enseignant en un seul message chacun.
- Arguments/options : `{term}` code du semestre, `--dry-run` (affiche les destinataires sans envoyer), `--actor=`.
- Audit : enregistré (nombre de groupes, d’enseignants et de messages envoyés)
- Quand : quelques jours avant la fermeture de la période de saisie des notes. Ajoutez-la à la planification si nécessaire.

## Évaluation des enseignements

### `evaluation:remind`

- Objet : envoie un e-mail aux étudiants qui ont encore des cours non évalués pendant que la période d’évaluation des enseignements est ouverte. Les cours restants sont regroupés par étudiant en un seul message.
- Arguments/options : `{term}` code du semestre, `--dry-run` (nombre d’étudiants concernés uniquement, rien n’est envoyé), `--actor=`.
- Audit : enregistré (nombre d’étudiants concernés, de messages envoyés)
- Quand : une ou deux fois pendant la période d’évaluation. Ajoutez-la à la planification si nécessaire.

### `evaluation:publish-results`

- Objet : publie auprès des enseignants les résultats de l’évaluation des enseignements du semestre. Équivaut à **Évaluation des enseignements > Évaluations par semestre > Publier les résultats** dans le panneau d’administration. Même après publication, les résultats ne sont visibles que pour les groupes ayant atteint le nombre minimal de réponses et le taux de réponse.
- Arguments/options : `{term}` code du semestre, `--actor=`.
- Audit : enregistré (nombre de réponses, publication déjà effectuée ou non)
- Quand : une fois la période d’évaluation et le traitement des notes terminés. Un semestre déjà publié n’est pas modifié.

## Attestations

### `certificate:verify`

- Objet : contrôle un code de vérification d’attestation selon les mêmes critères que la page publique de vérification. Affiche le type, le numéro de délivrance, la date de délivrance et le titulaire masqué.
- Arguments/options : `{code}` code de vérification.
- Audit : aucun (lecture seule)
- Codes de sortie : 0 si l’attestation est valide, 2 pour un code inconnu, une attestation révoquée ou une durée de validité expirée.

### `certificate:render`

- Objet : génère à nouveau le PDF d’une attestation délivrée avec les données et le modèle actuels, en remplaçant le fichier archivé. Équivaut à **Attestations délivrées > Régénérer** dans le panneau d’administration. Le numéro de délivrance et le code de vérification restent inchangés ; la somme de contrôle change.
- Arguments/options : `{issue}` numéro de délivrance, `--reason=` (obligatoire), `--actor=`.
- Audit : enregistré (numéro de délivrance, nouvelle somme de contrôle)
- Quand : pour régénérer une attestation précise après la correction d’un modèle ou de l’orthographe d’un nom.

## Examens

### `assessment:import-results`

- Objet : saisit depuis un CSV les résultats d’une session d’examen. Mêmes règles que **Sessions d’examen > Importer les résultats (CSV)** dans le panneau d’administration (étudiants inscrits uniquement, réussite/échec déterminé à partir du score, une nouvelle saisie avant publication remplace les résultats).
- Arguments/options : `{type}` code du type d’examen / `{held_date}` date de l’examen `YYYY-MM-DD` / `{file}` un CSV UTF-8 avec ligne d’en-tête, `--actor=` (obligatoire), `--dry-run` (validation des lignes uniquement, rien n’est enregistré).
- Format des colonnes : [Manuel d’administration, chapitre 24](../admin/24-assessments.md).
- Audit : enregistré (session, nom du fichier, nombre de lignes, nombre de résultats saisis/absents/en erreur)
- Codes de sortie : 2 en présence de lignes en erreur (les autres lignes sont enregistrées).
- Exemple : `php artisan assessment:import-results ENG_COMP 2026-05-15 storage/app/eng.csv --actor=registrar@example.ac.kr --dry-run`

### `assessment:publish-results`

- Objet : publie les résultats d’une session auprès des étudiants. Équivaut à **Sessions d’examen > Publier les résultats** dans le panneau d’administration.
- Arguments/options : `{type}` code du type d’examen / `{held_date}` date de l’examen, `--actor=` (obligatoire).
- Audit : enregistré (session, nombre de résultats)
- Quand : une fois la saisie des résultats terminée. Échoue pour une session déjà publiée.

## Assiduité

### `attendance:generate-sessions`

- Objet : crée les séances de chaque groupe d’un semestre à partir de ses horaires (jour de la semaine, heure, période). Équivaut à **Générer les séances** dans le portail enseignant et dans le panneau d’administration. Les séances existantes et leurs présences sont conservées (peut être relancée sans risque).
- Arguments/options : `{term}` code du semestre, `--section=` (code du cours-code du groupe, par exemple `CSE2010-01`, un seul), `--dry-run` (nombre de groupes concernés uniquement), `--actor=`.
- Audit : enregistré (semestre, nombre de groupes, de séances créées)
- Quand : avant le début du semestre, une fois les horaires confirmés. Relancez-la lorsque les horaires changent.

## Diplomation

### `degree-audit:run`

- Objet : enregistre une vérification des conditions d’obtention du diplôme pour chaque étudiant actif. Équivaut à **Vérifications du diplôme > Lancer les vérifications** dans le panneau d’administration. La dernière vérification enregistrée sert de base au traitement de la diplomation.
- Options : `--term=` (code du semestre auquel la vérification est rattachée), `--student-number=` (un seul étudiant), `--min-year-level=` (uniquement ce niveau d’année et au-delà), `--actor=`.
- Audit : enregistré (nombre d’étudiants, nombre de conditions remplies)
- Quand : avant l’examen de la diplomation (fin de semestre), et après la modification des ensembles de conditions ou des cours équivalents.

## Intégration LMS

### `lms:sync`

- Objet : envoie les groupes publiés vers la plateforme (création et liaison des cours, enseignants, étudiants inscrits). Fait pour tout un semestre ce que fait **Liens de cours > Synchroniser à nouveau** dans le panneau d’administration. Les groupes déjà liés sont seulement revérifiés (idempotent).
- Arguments/options : `{platform}` code de la plateforme, `--term=` (code du semestre, par défaut tous les semestres non terminés), `--section=` (un seul code du cours-code du groupe), `--dry-run` (enregistre seulement ce qui serait envoyé comme « ignoré »), `--actor=`.
- Audit : enregistré (plateforme, nombre de groupes, de membres, d’échecs)
- Codes de sortie : 2 si un groupe a échoué.

### `lms:reconcile`

- Objet : compare les listes de participants des cours de la plateforme avec les listes d’inscrits du système d’information étudiant, ajoute les membres manquants et retire les membres en trop. Les écarts sont conservés dans le journal de synchronisation (`reconcile`).
- Arguments/options : `{platform?}` (toutes les plateformes actives si omis), `--term=`, `--dry-run` (enregistre seulement les écarts), `--actor=`.
- Audit : enregistré (nombre de groupes par plateforme, nombre d’ajouts/de retraits/d’échecs)
- Quand : planifiée tous les jours à 02:30. Exécutez-la manuellement après une panne de la plateforme.

## Contrôles d’exploitation et nettoyage

### `system:health-check`

- Objet : contrôle la configuration et les clés (APP_KEY, clés PII, APP_DEBUG), la connexion et les rôles de base de données (runtime, migrator, reporting), la RLS sur les tables de données personnelles, les migrations en attente, la synchronisation du catalogue d’autorisations, Redis, la file d’attente (Horizon), Gotenberg, l’écriture dans le stockage des médias et le signal de vie du planificateur.
- Options : `--json` (sortie destinée à la supervision).
- Audit : aucun (lecture seule)
- Codes de sortie : 2 si un contrôle échoue.
- Quand : juste après un déploiement, en cas de panne suspectée, périodiquement depuis la supervision.

### `audit:seal`

- Objet : relie les nouvelles lignes des tables d’audit à la chaîne de hachage ([Intégrité de l’audit](audit-and-maintenance.md)).
- Options : `--table=` (parmi record_changes, activity_log, security_events ; répétable), `--actor=`.
- Audit : enregistré (lignes scellées par table)
- Quand : planifiée tous les jours à 03:30.

### `audit:verify-chain`

- Objet : recalcule les empreintes de contenu et la chaîne des lignes d’audit afin de détecter les falsifications, suppressions et réordonnancements.
- Options : `--table=`.
- Audit : aucun (lecture seule)
- Codes de sortie : 2 en cas de non-concordance.
- Quand : avant les sauvegardes, lors des revues de sécurité, après une purge.

### `audit:prune`

- Objet : supprime les lignes d’audit **scellées** ayant dépassé leur durée de conservation. S’exécute sur la connexion du propriétaire du schéma.
- Options : `--before=YYYY-MM-DD` (obligatoire), `--table=`, `--dry-run` (comptage uniquement), `--actor=`.
- Audit : enregistré (date limite, lignes supprimées par table)
- Quand : selon la politique de conservation, après une sauvegarde.

### `media:prune-orphans`

- Objet : supprime les lignes de médias dont le propriétaire n’existe plus et les répertoires sans ligne correspondante (photos des personnes, documents de candidature, PDF d’attestations, pièces justificatives d’examen).
- Options : `--dry-run` (liste uniquement), `--actor=`.
- Audit : enregistré (lignes, répertoires)
- Quand : tous les mois ou après une suppression en masse.

## Clés de chiffrement des données personnelles

### `pii:generate-key`

- Objet : affiche de nouvelles clés pour `PII_ENCRYPTION_KEY` et `PII_HASH_KEY`. N’enregistre rien.
- Audit : non enregistré
- Attention : modifier `PII_HASH_KEY` invalide toutes les empreintes de recherche. La procédure de rotation des clés figure dans [security-keys.md](security-keys.md).

### `pii:reencrypt`

- Objet : après une rotation de clé, rechiffre avec la clé actuelle les données personnelles chiffrées avec une clé précédente. S’exécute sous forme de lot de file d’attente.
- Arguments : la classe du modèle (actuellement `App\\Models\\Person\\Person`).
- Options : `--chunk=500`, `--dry-run` (compte seulement les enregistrements utilisant une clé précédente), `--force`.
- Audit : enregistré (identifiant du lot, nombre de tâches / nombre en aperçu)
- Vérification : suivez l’avancement du lot dans Horizon (`/horizon`). Lorsqu’il est terminé et que `--dry-run` indique 0, retirez la clé précédente de `PII_PREVIOUS_KEYS`.

## Localisation

### `i18n:missing`

- Objet : affiche, par langue, les clés manquantes par rapport à la langue de base (en) et les clés qui ne sont plus utilisées.
- Arguments/options : `{locale?}` (une seule langue), `--strict` (code de sortie 2 en cas de clés manquantes. Utilisé en CI).
- Audit : non enregistré

### `i18n:export`

- Objet : crée un fichier JSON à remettre aux traducteurs. Format : `{"key": {"source": "English text", "translation": "translation"}}`.
- Arguments/options : `{locale} {path}`, `--missing` (clés manquantes uniquement).
- Audit : non enregistré
- Exemple : `php artisan i18n:export ko storage/app/ko.json --missing`

### `i18n:import`

- Objet : applique à `lang/{locale}` le JSON complété par les traducteurs. Seules les clés existant dans la langue de base sont importées ; les traductions vides sont ignorées.
- Arguments/options : `{locale} {path}`, `--dry-run` (affiche seulement la liste des clés qui seraient écrites).
- Audit : enregistré
- Attention : les fichiers de traduction sont gérés dans le dépôt. Committez les modifications après l’import.

## Contrôles

### `naming:check`

- Objet : contrôle les noms de tables et de colonnes des migrations au regard des [conventions de nommage](../../../development/naming.md).
- Options : `--path=*` (répertoires à contrôler, par défaut `database/migrations`).
- Audit : non enregistré
- Codes de sortie : 2 en présence de problèmes.

### `pdf:sample`

- Objet : génère en PDF un tableau mêlant hangeul, sinogrammes (CJK), lao, thaï, devanagari, cyrillique et alphabet latin, afin de vérifier les polices et la connexion au service PDF (Gotenberg).
- Arguments : `{path=pdf-sample.pdf}` (relatif au disque local `storage/app/private`).
- Audit : non enregistré
- En cas d’échec : pour une erreur de connexion, vérifiez `GOTENBERG_URL` et l’état du conteneur Gotenberg.
