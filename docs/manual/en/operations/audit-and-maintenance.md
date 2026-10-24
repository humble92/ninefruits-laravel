# Audit integrity and routine maintenance

## Audit hash chain

The audit tables (`audit.record_changes` for data changes, `audit.activity_log` for business activity, `audit.security_events` for security events) are append-only (enforced by triggers). On top of that there is a mechanism that **exposes** tampering.

- Every row gets a SHA-256 of its content (`row_hash`) when it is inserted.
- `audit:seal` (scheduled daily at 03:30) links new rows into the chain in ID order: `chain_hash = SHA-256(previous chain_hash || row_hash)`.
- `audit:verify-chain` recomputes the content hash and the chain for every row. A row whose content changed is reported as "row mismatch", a missing or reordered row as "chain mismatch", and the command returns exit code 2.

Things to keep in mind

- A row changed before it was sealed (within the same day) cannot be caught by the chain. That is why sealing runs daily and verification runs before backups and during security reviews.
- When pruning (`audit:prune`) removes the oldest rows, the link of the first remaining row is not checked (the pruned range is kept in the prune command's audit record).
- Only the DB administrator (schema owner) can set `app.audit_maintenance = on`. The application role has no UPDATE or DELETE privilege.

## Retention and pruning

1. Decide the retention periods (for example data changes 3 years, business activity 5 years, security events 1 year).
2. Take a backup before pruning ([Backup and restore](backup-restore.md)) and confirm the chain is intact with `audit:verify-chain`.
3. Check the counts with `audit:prune --before=YYYY-MM-DD --table=record_changes --dry-run`, then run it without `--dry-run`. Unsealed rows are never deleted, so run `audit:seal` first.
4. Run `audit:verify-chain` again after pruning.

The pruning itself is recorded: every run writes to the activity log (log `command`) the cutoff date, options, actor and OS user, plus per table the **number of rows deleted, the range of deleted IDs and the chain_hash at the boundary**; an actual deletion is also recorded as the security event `audit.pruned` (**Audit > Security events**). This record row is itself linked into the chain at the next sealing. This boundary is exactly why `audit:verify-chain` does not check the link of the first remaining row; comparing the boundary hash with the prune record lets you confirm that the deleted range matches a recorded prune.

## File cleanup

`media:prune-orphans --dry-run` lists media rows whose owner no longer exists and directories without a row; without `--dry-run` it deletes them. It covers person photos, application documents, certificate PDFs and examination evidence. Run it monthly or after a bulk deletion.

## Health check

`system:health-check` checks configuration and keys, DB roles and RLS, migrations, the permission catalog, Redis and the queue (Horizon), Gotenberg, storage and the scheduler heartbeat (written to the cache every minute), and returns exit code 2 when something is wrong. Use `--json` to connect it to monitoring.
