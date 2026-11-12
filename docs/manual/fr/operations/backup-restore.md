# Sauvegarde et restauration

- Public : exploitants informatiques

## Éléments à sauvegarder

| Cible | Méthode | Fréquence (recommandée) |
|---|---|---|
| PostgreSQL | `pg_dump -Fc` ou PITR (archivage des WAL) | Sauvegarde complète quotidienne + WAL en continu |
| Stockage des fichiers (`storage/app/private` ou S3) | Instantané ou bucket versionné | Identique à la base de données |
| Configuration d’environnement et clés | Coffre de secrets | À chaque modification |

La base de données et le stockage des fichiers doivent pouvoir être restaurés au même instant (les métadonnées des photos et des PDF d’attestations résident dans la base de données).

## Exemple de sauvegarde de la base de données

```bash
pg_dump -U app_migrator -d sis -Fc -f sis-$(date +%F).dump
```

`app_migrator` est propriétaire de tous les schémas (`public`, `audit`, `reporting`, `util`) ; sauvegardez donc avec ce compte. Les rôles (`app_*`) sont des objets de cluster et ne font pas partie du dump. Sur le serveur de restauration, créez d’abord les rôles comme décrit dans la section 2 de l’[Installation](installation.md).

## Exemple de restauration

```bash
createdb -U postgres sis_restore
psql -U postgres -d sis_restore -f database/sql/roles/prepare_database.sql
pg_restore -U app_migrator -d sis_restore --no-owner --role=app_migrator sis-2026-10-22.dump
```

## Exercices de restauration

Chaque trimestre, restaurez sur un serveur distinct et vérifiez les points suivants : connexion, déchiffrement des données personnelles (concordance des clés), consultation des enregistrements d’audit, ouverture des fichiers (photos, PDF).
