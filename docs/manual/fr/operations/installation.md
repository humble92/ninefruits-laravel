# Installation

- Public : exploitants informatiques
- Voir aussi : [Référence des commandes](commands.md), [Gestion des clés](security-keys.md)

## 1. Prérequis

| Composant | Version | Remarques |
|---|---|---|
| PHP | 8.5 | Extensions : `pdo_pgsql`, `pgsql`, `intl`, `redis`, `bcmath`, `zip`, `gd`, `sodium` |
| PostgreSQL | 18 | Extensions `btree_gist` et `pg_trgm` (installées par les migrations), collation ICU |
| Redis | 8 | Cache, sessions, files d’attente |
| Gotenberg | 8 | Génération des PDF. Exposé uniquement sur le réseau interne |
| Serveur de messagerie | SMTP | Réinitialisation des mots de passe, codes de double authentification par e-mail |
| Worker de file d’attente | Horizon | Maintenez `php artisan horizon` en fonctionnement sous un gestionnaire de processus (systemd, supervisor) |
| Planificateur | cron | `* * * * * php artisan schedule:run` |

## 2. Préparation de la base de données

À exécuter une seule fois en tant que superutilisateur de la base de données. L’application n’utilise jamais le superutilisateur.

```bash
createdb -U postgres sis
psql -U postgres -d postgres -v ON_ERROR_STOP=1 \
     -v migrator_password='...' -v runtime_password='...' -v reporting_password='...' \
     -f database/sql/roles/create_roles.sql
psql -U postgres -d sis -v ON_ERROR_STOP=1 -f database/sql/roles/prepare_database.sql
```

| Rôle | Variables d’environnement | Usage |
|---|---|---|
| `app_runtime` | `DB_USERNAME`, `DB_PASSWORD` | Exécution de l’application |
| `app_migrator` | `DB_MIGRATOR_USERNAME`, `DB_MIGRATOR_PASSWORD` | Migrations uniquement |
| `app_reporting` | (configuré dans l’outil de BI) | Lecture du schéma `reporting` |

## 3. Configuration de l’environnement

Copiez `.env.example` et renseignez les valeurs. Valeurs à modifier impérativement

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
- `APP_KEY` : `php artisan key:generate --show`
- `PII_ENCRYPTION_KEY`, `PII_HASH_KEY` : `php artisan pii:generate-key`. **En cas de perte, les données personnelles chiffrées sont irrécupérables.** Conservez-les à part comme décrit dans [Gestion des clés](security-keys.md).
- `DB_*`, `REDIS_*`, `MAIL_*`, `GOTENBERG_URL`
- `INSTITUTION_TIMEZONE` : le fuseau horaire par défaut de l’établissement lors de la première installation

## 4. Installation

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan setup:install
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`setup:install` procède en posant des questions. Dans les scripts de déploiement, exécutez-la avec `--force` et des options ([Référence des commandes](commands.md#setupinstall)).

Profil d’installation (`--profile=`) : `korean-university` — premier et second semestres, semestres d’été et d’hiver, barème de notation sur 4,5, catégories de cours, créneaux horaires de 50 minutes à partir de 09:00, usages des salles. Sans profil, saisissez ces éléments dans le panneau d’administration. Appliquer de nouveau un profil par la suite conserve les valeurs modifiées par les administrateurs.

Après l’installation, l’administrateur fonctionnel saisit les données de référence en suivant le [chapitre 5 du manuel d’administration, ordre de configuration initiale](../admin/05-institution-setup.md).

## 5. Vérification de l’installation

1. Connectez-vous sur `/login` en tant qu’administrateur initial.
2. Définissez la politique de double authentification dans **Paramètres > Sécurité** du panneau d’administration.
3. Enrôlez la double authentification de l’administrateur lui-même (portail, **Sécurité**).
4. Vérifiez le service PDF avec `php artisan pdf:sample`.
5. Vérifiez dans **Audit > Modifications de données** du panneau d’administration que l’installation a été enregistrée sous `system:command:setup:install`.

## 6. Mise à jour

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan setup:install --force   # migrations, essential data, permission sync
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate        # workers restart with the new code
php artisan up
```
