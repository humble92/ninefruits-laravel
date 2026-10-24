# Key management

- Audience: IT operators

| Key | Purpose | If lost | Rotation |
|---|---|---|---|
| `APP_KEY` | Sessions, cookies, two-factor secrets, signing of remembered-device cookies | Every user is signed out; enrolled two-factor authentication (authenticator apps) stops working | Put the previous key in `APP_PREVIOUS_KEYS`, then rotate |
| `PII_ENCRYPTION_KEY` | Encryption of personal data such as national ID numbers | **Encrypted personal data cannot be recovered** | Procedure below |
| `PII_HASH_KEY` | Hashes for exact-match search of personal data | Search stops working (the data remains) | Not rotated as a rule |

Storage principles

- Keep at least two copies of the three keys outside the server's environment configuration, in a secret store (Vault, a cloud secret manager, etc.) or in sealed offline storage.
- Never store them alongside the backup files. Even if a DB backup leaks, personal data must stay unreadable without the keys.

## Personal data key rotation procedure

1. Generate a new key with `php artisan pii:generate-key` (use only the `PII_ENCRYPTION_KEY` from the output).
2. Put the current key in `PII_PREVIOUS_KEYS` (comma-separated for several) and the new key in `PII_ENCRYPTION_KEY`.
3. Reload the configuration: `php artisan config:cache`, `php artisan horizon:terminate`.
4. For each personal data model, check the number of affected records with `php artisan pii:reencrypt {model} --dry-run`, then run it without `--force`.
5. Once Horizon shows the batch as finished and `--dry-run` reports 0, remove the previous key from `PII_PREVIOUS_KEYS`.
6. Keep the previous key sealed for the backup retention period (it is needed to restore older backups).
