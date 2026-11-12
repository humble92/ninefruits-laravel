# Intégrité de l’audit et maintenance courante

## Chaîne de hachage de l’audit

Les tables d’audit (`audit.record_changes` pour les modifications de données, `audit.activity_log` pour l’activité métier, `audit.security_events` pour les événements de sécurité) sont en ajout seul (garanti par des déclencheurs). S’y ajoute un mécanisme qui **révèle** les falsifications.

- Chaque ligne reçoit, lors de son insertion, une empreinte SHA-256 de son contenu (`row_hash`).
- `audit:seal` (planifiée tous les jours à 03:30) relie les nouvelles lignes à la chaîne dans l’ordre des identifiants : `chain_hash = SHA-256(previous chain_hash || row_hash)`.
- `audit:verify-chain` recalcule l’empreinte de contenu et la chaîne de chaque ligne. Une ligne dont le contenu a changé est signalée comme « row mismatch », une ligne manquante ou réordonnée comme « chain mismatch », et la commande renvoie le code de sortie 2.

Points à garder à l’esprit

- Une ligne modifiée avant d’avoir été scellée (le même jour) ne peut pas être détectée par la chaîne. C’est pourquoi le scellement est quotidien et la vérification a lieu avant les sauvegardes et lors des revues de sécurité.
- Lorsqu’une purge (`audit:prune`) supprime les lignes les plus anciennes, le lien de la première ligne restante n’est pas vérifié (la plage purgée est conservée dans l’enregistrement d’audit de la commande de purge).
- Seul l’administrateur de la base de données (propriétaire du schéma) peut définir `app.audit_maintenance = on`. Le rôle applicatif ne dispose d’aucun privilège UPDATE ni DELETE.

## Conservation et purge

1. Fixez les durées de conservation (par exemple 3 ans pour les modifications de données, 5 ans pour l’activité métier, 1 an pour les événements de sécurité).
2. Effectuez une sauvegarde avant la purge ([Sauvegarde et restauration](backup-restore.md)) et vérifiez l’intégrité de la chaîne avec `audit:verify-chain`.
3. Contrôlez les volumes avec `audit:prune --before=YYYY-MM-DD --table=record_changes --dry-run`, puis exécutez la commande sans `--dry-run`. Les lignes non scellées ne sont jamais supprimées ; exécutez donc d’abord `audit:seal`.
4. Exécutez de nouveau `audit:verify-chain` après la purge.

La purge elle-même est tracée : chaque exécution écrit dans le journal d’activité (journal `command`) la date limite, les options, l’acteur et l’utilisateur du système d’exploitation, ainsi que, pour chaque table, **le nombre de lignes supprimées, la plage des identifiants supprimés et le chain_hash à la limite** ; une suppression effective est également enregistrée comme événement de sécurité `audit.pruned` (**Audit > Événements de sécurité**). Cette ligne d’enregistrement est elle-même reliée à la chaîne lors du scellement suivant. C’est précisément à cause de cette limite que `audit:verify-chain` ne vérifie pas le lien de la première ligne restante ; comparer l’empreinte de limite avec l’enregistrement de purge permet de confirmer que la plage supprimée correspond à une purge enregistrée.

## Nettoyage des fichiers

`media:prune-orphans --dry-run` liste les lignes de médias dont le propriétaire n’existe plus et les répertoires sans ligne correspondante ; sans `--dry-run`, elle les supprime. Elle couvre les photos des personnes, les documents de candidature, les PDF d’attestations et les pièces justificatives d’examen. Exécutez-la tous les mois ou après une suppression en masse.

## Contrôle de santé

`system:health-check` contrôle la configuration et les clés, les rôles de base de données et la RLS, les migrations, le catalogue d’autorisations, Redis et la file d’attente (Horizon), Gotenberg, le stockage et le signal de vie du planificateur (écrit dans le cache toutes les minutes), et renvoie le code de sortie 2 en cas d’anomalie. Utilisez `--json` pour la raccorder à la supervision.
