# Liste de contrôle du déploiement et paramètres de sécurité PHP

## Version de PHP

- Utilisez **PHP ≥ 8.5.12** (ou 8.4.27, 8.3.36). La version de sécurité du 2026-10-21 a corrigé deux vulnérabilités d’exécution de code à distance sans authentification (CVE-2026-109618 corruption d’état en cas de mémoire insuffisante, CVE-2026-109619 fuite d’état entre requêtes), et php.net a indiqué qu’« il n’existe pas de mesure d’atténuation complète ; la mise à jour est la seule option sûre ». La contrainte `"php": "^8.5.12"` dans `composer.json` empêche l’installation sur une version de PHP inférieure.
- Environnement de développement : exécutez `./vendor/bin/sail build --no-cache && ./vendor/bin/sail up -d`, puis vérifiez avec `./vendor/bin/sail php -v`.
- Lors de la construction des images de conteneur ou de serveur, ne figez pas le paquet PHP ; prenez la dernière version corrective. `system:health-check` affiche également la version de PHP.

## php.ini de production (pool php-fpm ou configuration du serveur, et non `.user.ini` ou `ini_set()`)

| Paramètre | Valeur | Raison |
|---|---|---|
| `session.upload_progress.enabled` | `0` | Bloque les vecteurs de CVE-2026-109619 et CVE-2026-115560 (avis php.net des 21 et 23 octobre 2026). Ce système n’utilise pas la progression des téléversements |
| `fatal_error_backtraces` | `0` (PHP 8.5) | Bloque le vecteur des traces d’appels d’erreurs fatales mentionné dans le même avis |
| `memory_limit` | `256M` ou plus, figé avec `php_admin_value` | Afin que le code applicatif ne puisse pas le modifier |
| `post_max_size` / `upload_max_filesize` | `32M` / `20M` | Limite supérieure pour les documents d’admission, les pièces justificatives d’examen et les photos téléversées (avec une marge au-dessus des validations applicatives de 5 Mo et 2 Mo) |
| `expose_php` | `Off` | Ne pas exposer la version |
| `display_errors` | `Off`, `log_errors=On` | Production |
| `session.cookie_secure`, `session.cookie_httponly`, `session.cookie_samesite` | `1`, `1`, `Lax` | Conjointement avec les paramètres de session Laravel (`SESSION_SECURE_COOKIE=true`) |
| `opcache.enable`, `opcache.validate_timestamps` | `1`, `0` (redémarrage au déploiement) | Performances |

## Environnement de l’application (.env)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` défini sur l’adresse réelle.
- Injectez `APP_KEY`, `PII_ENCRYPTION_KEY` et `PII_HASH_KEY` depuis le coffre de secrets et sauvegardez-les ([Gestion des clés](security-keys.md)). En cas de perte des clés, les données personnelles ne peuvent plus être déchiffrées.
- Base de données : `DB_USERNAME=app_runtime`, `DB_MIGRATOR_USERNAME=app_migrator` (un mot de passe réservé aux migrations), `app_reporting` uniquement pour les outils de BI.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`. Enregistrez Horizon (`php artisan horizon`) et le planificateur (`* * * * * php artisan schedule:run`) comme services.
- `GOTENBERG_URL` : une adresse du réseau interne. Gotenberg n’est jamais exposé à l’extérieur.
- Messagerie (`MAIL_*`) : utilisée pour les invitations, les e-mails de double authentification et les notifications d’inscription et de notes.

## Procédure de déploiement

1. Sauvegardez ([Sauvegarde et restauration](backup-restore.md)).
2. Déployez le code, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` (bascule automatiquement sur la connexion de migration), `php artisan security:sync-permissions`, `php artisan setup:install --force` (actualise les données essentielles, conserve les modifications des administrateurs).
4. `php artisan optimize`, redémarrez Horizon (`horizon:terminate`), redémarrez php-fpm (applique le nouveau php.ini).
5. Vérifiez avec `php artisan system:health-check`. Vérifiez la chaîne d’audit avec `audit:verify-chain`.

## Contrôles courants

| Fréquence | Tâche |
|---|---|
| Quotidienne (automatique) | `audit:seal`, `lms:reconcile`, libération des places réservées, promotion des listes d’attente, expiration des brouillons de candidature |
| Hebdomadaire | `system:health-check`, vérification des mises à jour de sécurité de PHP et du système d’exploitation, vérification des tâches en échec dans Horizon |
| Mensuelle | `media:prune-orphans --dry-run`, exercice de restauration de sauvegarde, `audit:verify-chain` |
| Chaque semestre | Revue des attributions de rôles (personnes parties ou mutées), purge des enregistrements d’audit ayant dépassé leur durée de conservation (`audit:prune`) |
