# Gestion des clés

- Public : exploitants informatiques

| Clé | Usage | En cas de perte | Rotation |
|---|---|---|---|
| `APP_KEY` | Sessions, cookies, secrets de double authentification, signature des cookies d’appareils mémorisés | Tous les utilisateurs sont déconnectés ; la double authentification enrôlée (applications d’authentification) cesse de fonctionner | Placez la clé précédente dans `APP_PREVIOUS_KEYS`, puis effectuez la rotation |
| `PII_ENCRYPTION_KEY` | Chiffrement des données personnelles telles que les numéros d’identification national | **Les données personnelles chiffrées sont irrécupérables** | Procédure ci-dessous |
| `PII_HASH_KEY` | Empreintes pour la recherche exacte dans les données personnelles | La recherche cesse de fonctionner (les données subsistent) | En principe, pas de rotation |

Principes de conservation

- Conservez au moins deux copies des trois clés en dehors de la configuration d’environnement du serveur, dans un coffre de secrets (Vault, gestionnaire de secrets d’un fournisseur cloud, etc.) ou sur un support hors ligne scellé.
- Ne les stockez jamais avec les fichiers de sauvegarde. Même en cas de fuite d’une sauvegarde de la base de données, les données personnelles doivent rester illisibles sans les clés.

## Procédure de rotation de la clé des données personnelles

1. Générez une nouvelle clé avec `php artisan pii:generate-key` (n’utilisez que la `PII_ENCRYPTION_KEY` de la sortie).
2. Placez la clé actuelle dans `PII_PREVIOUS_KEYS` (séparées par des virgules s’il y en a plusieurs) et la nouvelle clé dans `PII_ENCRYPTION_KEY`.
3. Rechargez la configuration : `php artisan config:cache`, `php artisan horizon:terminate`.
4. Pour chaque modèle de données personnelles, vérifiez le nombre d’enregistrements concernés avec `php artisan pii:reencrypt {model} --dry-run`, puis exécutez la commande sans `--force`.
5. Lorsque Horizon indique que le lot est terminé et que `--dry-run` renvoie 0, retirez la clé précédente de `PII_PREVIOUS_KEYS`.
6. Conservez la clé précédente sous scellé pendant la durée de conservation des sauvegardes (elle est nécessaire pour restaurer les sauvegardes plus anciennes).
