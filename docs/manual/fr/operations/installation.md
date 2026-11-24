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

## 7. Serveur web (Nginx)

Placez Nginx devant php-fpm. Ce qui suit est un exemple : adaptez l’adresse, les chemins, les certificats et le socket php-fpm à votre serveur.

```nginx
log_format sis '$remote_addr - $remote_user [$time_local] "$request" $status $body_bytes_sent '
               '"$http_referer" "$http_user_agent" rt=$request_time rid=$request_id';

server {
    listen 443 ssl;
    http2 on;
    server_name sis.example.edu;
    root /var/www/sis/public;
    index index.php;

    ssl_certificate     /etc/ssl/sis/fullchain.pem;
    ssl_certificate_key /etc/ssl/sis/privkey.pem;

    access_log /var/log/nginx/sis.access.log sis;
    client_max_body_size 32m;     # same as post_max_size in php.ini
    server_tokens off;

    # Only when PHP does not answer. The application's own error pages pass through.
    error_page 502 503 504 /errors/unavailable.html;
    location = /errors/unavailable.html {
        internal;
        ssi on;                    # puts $request_id on the page
        add_header Cache-Control "no-store" always;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param HTTP_X_REQUEST_ID $request_id;   # replaces a client-sent X-Request-Id
        internal;
    }

    location ~ \.php$ { return 404; }
    location ~ /\.(?!well-known) { deny all; }
}
```

- **Page d’erreur :** `public/errors/unavailable.html` est la page statique que Nginx affiche quand PHP ne répond pas (502, 503, 504). Elle ne dépend ni des fichiers de build ni de la base de données, et son texte suit la langue du navigateur (anglais pour les langues non prises en charge). Les pages d’erreur envoyées par l’application elle-même (500, 503 pendant `php artisan down`, etc.) passent telles quelles, car `fastcgi_intercept_errors` reste à sa valeur par défaut (off).
- **Numéro de référence :** `ssi on` insère le `$request_id` de Nginx dans la page. La même valeur est transmise à l’application comme `X-Request-Id` : le journal d’accès Nginx (`rid=`), les journaux de l’application et les enregistrements d’audit se recherchent avec un seul numéro ([dépannage](troubleshooting.md)). Un `X-Request-Id` envoyé par le client est remplacé par cette valeur.
- **Quand Nginx relaie vers un serveur d’application distinct :** utilisez `proxy_set_header X-Request-Id $request_id;` et laissez `proxy_intercept_errors` à sa valeur par défaut (off). Conservez `error_page` et sa `location`.
- **Quand vous modifiez le texte :** si vous changez `unavailable` et `reference` dans `lang/*/errors.php`, modifiez aussi le JSON de ce fichier. Le test (`ErrorPagesTest`) échoue si les deux divergent.
- **Version de Nginx :** `http2 on;` nécessite Nginx 1.25.1 ou plus récent. Sur les versions plus anciennes (par exemple 1.18 sous Ubuntu 22.04), supprimez cette ligne et écrivez plutôt `listen 443 ssl http2;`.
