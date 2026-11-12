# Tests de charge (pic d’inscription)

Reproduit avec k6 la situation où de nombreux étudiants se précipitent en même temps sur les mêmes groupes, comme le premier matin des inscriptions. Le script est `tests/load/registration.js`.

## Préparation

1. Générez un jeu de données de démonstration volumineux et associez-y des comptes étudiants (le CSV de la liste des comptes est écrit dans `storage/app/private/load-accounts.csv`).

   ```bash
   ./vendor/bin/sail artisan setup:demo --size=large --seed=42 --student-accounts
   ```

2. Une période d’inscription doit être ouverte (la démonstration ouvre la période d’inscription de la session du semestre en cours).
3. Le worker de file d’attente (Horizon) et Redis doivent être en fonctionnement. La réservation des places et la promotion des listes d’attente passent par la file d’attente.

## Exécution

```bash
docker run --rm -i --network ninefruits-laravel_sail -v "$PWD":/work -w /work grafana/k6 run \
    --vus 200 --duration 2m -e BASE_URL=http://laravel.test -e ACCOUNTS=/work/storage/app/private/load-accounts.csv tests/load/registration.js
```

- Augmentez `--vus` de 100 à 200 puis à 500 et surveillez le p95 de `http_req_duration` ainsi que le taux d’échec. Objectifs : p95 de l’écran d’inscription < 800 ms, p95 des requêtes d’inscription < 1,5 s, taux d’échec < 1 %.
- Côté serveur, surveillez également le nombre de sessions en attente (verrous) dans `pg_stat_activity`, le délai de traitement d’Horizon et la saturation des workers php-fpm.
- Lors d’une exécution sur Sail, augmentez le nombre de workers du serveur intégré (`PHP_CLI_SERVER_WORKERS` dans `.env`, 4 par défaut). Le nombre de workers plafonne le débit.

## Ce que garantit la conception

- La capacité est protégée par des verrous de ligne en base de données (`SeatAllocator::lock`) ; même une rafale de requêtes simultanées ne peut donc pas provoquer de sur-inscription dans un groupe (`ConcurrentRegistrationTest`).
- La réservation des places est correcte au seul niveau de la base de données, sans Redis ni file d’attente, mais la promotion des listes d’attente et les notifications sont traitées par la file d’attente.

## Décision concernant Octane

Si le p95 dépasse les objectifs avec un seul php-fpm sous Sail, ajustez d’abord le nombre de workers php-fpm et le paramètre PostgreSQL `max_connections` ou PgBouncer. Si cela ne suffit toujours pas, envisagez Laravel Octane (FrankenPHP ou Swoole). Avant de passer à Octane, vérifiez : l’état qui doit être réinitialisé à chaque requête (les variables de session de `DatabaseSessionContext` sont rétablies par `reset()` à la fin de la requête ; elles sont donc sûres sous Octane également), les données utilisateur restées dans des singletons, les caches statiques. La décision est consignée dans le rapport de la phase 13 au vu des chiffres de charge réels.

## Résultats du 2026-10-23

Conteneur Sail de développement (serveur intégré, sans opcache), 100 VU pendant une minute : 24,7 req/s avec 4 workers, 34,5 req/s avec 16 workers, 0 échec HTTP, 0 sur-inscription, 4 connexions PostgreSQL. Le temps serveur par requête est d’environ 120 ms pour la page et 190 ms pour une inscription ; la latence de plusieurs secondes à 100 VU provient de l’attente des workers. Le débit croît avec le nombre de workers PHP et la base de données est loin de sa limite ; **Octane n’est donc pas adopté** : le nombre de workers php-fpm et la mise à l’échelle horizontale des serveurs applicatifs suffisent. Répétez l’exécution avec 200 et 500 VU sur un environnement de préproduction php-fpm + opcache avant la mise en service. Chiffres complets : [rapport de la phase 13](../../../../porting/phase-13-report.md), section 7.
