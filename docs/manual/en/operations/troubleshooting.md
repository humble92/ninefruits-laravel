# Troubleshooting

- Audience: IT operators

| Symptom | What to check |
|---|---|
| Redirected to the security page right after signing in | The two-factor policy is "required" and the grace period has passed. Resolved once the user enrolls. The policy is under **Settings > Security** |
| 403 in the administration panel | The user has no valid role assignment with the `admin_panel.access` permission. Check **Users and access > Users > Role assignments** |
| A menu item is missing | The role lacks the permission for that screen (for example `roles.view`) |
| Email codes do not arrive | Mail settings (`MAIL_*`), the `Email code sent` entries among the security events, the sending limits (once per minute, five per hour) |
| A screen shows 0 rows of other people's data | May be normal (row level security). Confirm that the DB session actor is set by looking at the user column in **Data changes** |
| `permission denied for schema public` | The application attempted DDL as `app_runtime`. Run migrations only with `php artisan migrate` (which uses `app_migrator` automatically) |
| `Audit records are append-only` | An attempt to update or delete an audit table. This block is intended |
| PDF generation fails | `php artisan pdf:sample`, the Gotenberg status, `GOTENBERG_URL` |
| Queue jobs are stuck | Check failed jobs in `/horizon`, `php artisan horizon:status` |

Per-request tracing: with the value of the `X-Request-Id` response header you can search the logs, the activity log, the data changes and the security events together.
