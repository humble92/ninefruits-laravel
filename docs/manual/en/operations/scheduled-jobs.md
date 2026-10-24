# Scheduled jobs and queues

- Audience: IT operators

## Long-running processes

| Process | Command | Check |
|---|---|---|
| Queue worker | `php artisan horizon` | `/horizon` (permission `queue_monitor.view`) |
| Scheduler | cron `* * * * * php artisan schedule:run` | `php artisan schedule:list` |

## Scheduled jobs

Each phase adds to this table. The registrations live in `routes/console.php`. Times are in the server time zone (UTC).

| Job | Frequency | Phase | Failure detection |
|---|---|---|---|
| `admission:expire-drafts` | Daily at 03:10 | 3 | Recorded in the activity log (log `command`) on every run. If there is no record for more than a day, check the scheduler |
| `registration:release-reserved-seats` | Every 5 minutes | 5 | No audit record. If seat allocations whose release time has passed still have an empty `release_processed_at`, check the scheduler |
| `waitlist:promote` | Hourly | 5 | Recorded in the activity log (log `command`) on every run |

## Queue jobs

| Job | Triggered by | Notes |
|---|---|---|
| `ReencryptPiiRecords` | `pii:reencrypt` | Batch. Retry failed jobs from Horizon |
| `PromoteWaitlistJob` | Enrollment drop or withdrawal, seat allocation release | Only one runs per section (duplicates are prevented). Waitlist promotion re-checks every rule |
| `CloseTermRecordsJob`, `FinishTermCloseJob` | `term:close`, closing a term in the administration panel | Batches of 100 students, followed by the finishing job (honors ranking, term status change). Follow progress in the Horizon batch `term-close:{term code}`. Retry failed jobs from Horizon (per-student calculations give the same result when run again) |
| `ImportCsv` (Filament) | CSV import in the administration panel, `reference:import` | Batch. Completion is announced as an administration panel notification (`notifications` table). Failed rows go to `failed_import_rows` |
| `audit:seal` | Daily at 03:30 | Seals the audit hash chain ([Audit integrity](audit-and-maintenance.md)) |
| Scheduler heartbeat | Every minute | Writes the last run time to the cache. `system:health-check` treats more than 5 minutes without one as a failure |

While a queue job runs, the DB actor is recorded as `system:job:{class}`.
