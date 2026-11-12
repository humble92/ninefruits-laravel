# Dépannage

- Public : exploitants informatiques

| Symptôme | Points à vérifier |
|---|---|
| Redirection vers la page de sécurité juste après la connexion | La politique de double authentification est « obligatoire » et le délai de grâce est écoulé. Le problème disparaît une fois que l’utilisateur s’est enrôlé. La politique se trouve dans **Paramètres > Sécurité** |
| Erreur 403 dans le panneau d’administration | L’utilisateur n’a aucune attribution de rôle valide comportant l’autorisation `admin_panel.access`. Vérifiez **Utilisateurs et accès > Utilisateurs > Attributions de rôles** |
| Une entrée de menu est absente | Le rôle ne dispose pas de l’autorisation correspondant à cet écran (par exemple `roles.view`) |
| Les codes par e-mail n’arrivent pas | Paramètres de messagerie (`MAIL_*`), entrées `Code par e-mail envoyé` parmi les événements de sécurité, limites d’envoi (une fois par minute, cinq fois par heure) |
| Un écran affiche 0 ligne pour les données d’autres personnes | Peut être normal (sécurité au niveau des lignes). Vérifiez que l’acteur de la session de base de données est bien défini en consultant la colonne utilisateur dans **Modifications de données** |
| `permission denied for schema public` | L’application a tenté une opération DDL en tant que `app_runtime`. Exécutez les migrations uniquement avec `php artisan migrate` (qui utilise automatiquement `app_migrator`) |
| `Audit records are append-only` | Tentative de mise à jour ou de suppression dans une table d’audit. Ce blocage est voulu |
| La génération des PDF échoue | `php artisan pdf:sample`, état de Gotenberg, `GOTENBERG_URL` |
| Les tâches de file d’attente sont bloquées | Vérifiez les tâches en échec dans `/horizon`, `php artisan horizon:status` |

Traçage par requête : la valeur de l’en-tête de réponse `X-Request-Id` permet de rechercher conjointement dans les journaux, le journal d’activité, les modifications de données et les événements de sécurité.
