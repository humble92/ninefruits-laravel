# Student information system manual (English)

For the staff who administer the system day to day and for the IT operators who install, deploy and maintain it. The screens are available in English (user menu > language).

Other languages: [한국어](../ko/README.md), [简体中文](../zh_CN/README.md), [ລາວ](../lo/README.md). Korean is the source; the other editions are translations awaiting native review.

## Administrator manual

[admin/README.md](admin/README.md) — chapters 1 to 28, from getting started and users and roles through institution setup, admissions, registration, grades, certificates, graduation and LMS integration.

## Operations

| File | Contents |
|---|---|
| [installation.md](operations/installation.md) | Requirements, database roles, environment configuration, first installation and updates |
| [deployment-checklist.md](operations/deployment-checklist.md) | PHP version and php.ini hardening, production .env, deployment procedure, routine checks |
| [commands.md](operations/commands.md) | Reference for every Artisan command: options, audit records, exit codes, examples |
| [scheduled-jobs.md](operations/scheduled-jobs.md) | Long-running processes, scheduled jobs and queue jobs, how to detect failures |
| [backup-restore.md](operations/backup-restore.md) | What to back up, dump and restore examples, restore drills |
| [security-keys.md](operations/security-keys.md) | The three secret keys, how to store them, personal data key rotation |
| [audit-and-maintenance.md](operations/audit-and-maintenance.md) | Audit hash chain, retention and pruning, file cleanup, health check |
| [load-testing.md](operations/load-testing.md) | k6 load test for the registration peak and the Octane decision |
| [troubleshooting.md](operations/troubleshooting.md) | Common symptoms, what to check, per-request tracing |
