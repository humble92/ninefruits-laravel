# 28. Intégration LMS

Menu : **Intégration LMS > Plateformes LMS**, **Liens de cours**, **Journaux de synchronisation** (autorisation `Gérer les plateformes LMS, les liens et la synchronisation` ; par défaut : Affaires pédagogiques, en plus d’Administrateur système et de Scolarité). Les enseignants utilisent les liens LMS sous **Mes enseignements** et dans le détail du groupe, ainsi que **Charger les scores depuis le LMS** sur l’écran de saisie des notes. Le chargement obéit aux mêmes conditions que la saisie des notes (enseignants pendant la période de saisie des notes, personnel dans son périmètre de gestion des `Notes`).

Les informations destinées aux développeurs se trouvent dans le [guide d’intégration LMS](../../../lms-integration-guide.md).

## Ce qui va où

| SIS → LMS | Quand |
|---|---|
| Groupe (création du cours) + enseignants | Lorsque le groupe est **publié**, et avec `lms:sync` |
| Inscription confirmée → inscription au cours | Immédiatement lors de l’inscription, de l’attribution d’une place depuis la liste d’attente ou de l’inscription par le personnel |
| Désinscription ou abandon → désinscription du cours | Immédiatement |
| Rapprochement complet | Chaque jour à 02:30 (`lms:reconcile`) |

| LMS → SIS | Quand |
|---|---|
| Scores finaux (brouillon) | Lorsque l’enseignant clique sur **Charger les scores depuis le LMS** sur l’écran de saisie des notes. L’enregistrement et la soumission sont faits par l’enseignant |

Les notes définitives ne sont pas envoyées au LMS. Les supports de cours, devoirs et quiz se trouvent dans le LMS.

## Plateformes

**Plateformes LMS** : code, nom, pilote, URL de base, identifiants (clé/valeur, stockés chiffrés, conservés s’ils sont laissés vides lors de la modification), active, synchroniser les listes d’étudiants, récupérer les scores finaux.

- Pilote **Aucun (enregistrement seul)** : n’appelle rien et enregistre ce qui serait envoyé. À utiliser pour vérifier le flux ou pour des démonstrations avant l’intégration. La plateforme `DEMO` des données de démonstration l’utilise.
- `Moodle`, `Open edX` : pas encore de connecteur (marqués comme non encore implémentés). Lorsqu’ils sont choisis, les synchronisations sont enregistrées en échec.
- L’activation de **Active** fait transiter immédiatement, à partir de ce moment, les publications de groupes et les changements d’inscription, et inclut la plateforme dans le rapprochement nocturne.

## Liens de cours et journaux de synchronisation

- **Liens de cours** : quel groupe est lié à quel cours de la plateforme (identifiant, URL), statut (En attente/Lié/Échec), dernière synchronisation et erreur. **Synchroniser à nouveau** pousse à nouveau le groupe, les enseignants et la liste des étudiants.
- **Journaux de synchronisation** : résultat et message par étape (cours, ajout et retrait de membre, récupération des scores, rapprochement). C’est ici qu’il faut chercher la cause d’un échec.
- Commandes : `lms:sync {platform} --term={term}` (envoi complet, `--dry-run`), `lms:reconcile` (rapprochement).

## Portail

- Le lien vers le cours de la plateforme est affiché dans le détail du cours pour les étudiants et les enseignants, ainsi que dans la liste **Mes enseignements** de l’enseignant (groupes liés uniquement).
- Saisie des notes : lorsque **Récupérer les scores finaux** est activé pour la plateforme et que le groupe est lié, le bouton **Charger les scores depuis le LMS** apparaît. Il ne fait que remplir la colonne des scores et n’enregistre rien.

## Questions fréquentes

- **Un étudiant manque dans le LMS.** L’étudiant doit avoir un compte (utilisateur) pour être synchronisé. S’il n’en a pas, invitez-le ([chapitre 14](14-merge-and-invitations.md)) ; le rapprochement suivant l’ajoutera.
- **Une synchronisation reste en échec.** Lisez le message dans le journal de synchronisation. Les identifiants expirés sont une cause fréquente. Modifiez les valeurs dans l’écran de modification de la plateforme puis cliquez sur **Synchroniser à nouveau**.
- **Nous avons changé de plateforme.** Créez la nouvelle plateforme et désactivez Active sur l’ancienne. Les liens sont créés séparément pour chaque plateforme.
