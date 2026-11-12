# Tâches planifiées et files d’attente

- Public : exploitants informatiques

## Processus permanents

| Processus | Commande | Contrôle |
|---|---|---|
| Worker de file d’attente | `php artisan horizon` | `/horizon` (autorisation `queue_monitor.view`) |
| Planificateur | cron `* * * * * php artisan schedule:run` | `php artisan schedule:list` |

## Tâches planifiées

Chaque phase complète ce tableau. Les enregistrements se trouvent dans `routes/console.php`. Les heures sont exprimées dans le fuseau horaire du serveur (UTC).

| Tâche | Fréquence | Phase | Détection des échecs |
|---|---|---|---|
| `admission:expire-drafts` | Tous les jours à 03:10 | 3 | Enregistrée dans le journal d’activité (journal `command`) à chaque exécution. En l’absence d’enregistrement depuis plus d’un jour, vérifiez le planificateur |
| `registration:release-reserved-seats` | Toutes les 5 minutes | 5 | Pas d’enregistrement d’audit. Si des places réservées dont l’heure de libération est passée ont toujours un `release_processed_at` vide, vérifiez le planificateur |
| `waitlist:promote` | Toutes les heures | 5 | Enregistrée dans le journal d’activité (journal `command`) à chaque exécution |

## Tâches de file d’attente

| Tâche | Déclenchée par | Remarques |
|---|---|---|
| `ReencryptPiiRecords` | `pii:reencrypt` | Lot. Relancez les tâches en échec depuis Horizon |
| `PromoteWaitlistJob` | Désinscription ou abandon, libération de places réservées | Une seule s’exécute par groupe (les doublons sont empêchés). La promotion depuis la liste d’attente recontrôle toutes les règles |
| `CloseTermRecordsJob`, `FinishTermCloseJob` | `term:close`, clôture d’un semestre dans le panneau d’administration | Lots de 100 étudiants, suivis de la tâche de finalisation (classement du tableau d’honneur, changement de statut du semestre). Suivez l’avancement dans le lot Horizon `term-close:{term code}`. Relancez les tâches en échec depuis Horizon (les calculs par étudiant donnent le même résultat lorsqu’ils sont relancés) |
| `ImportCsv` (Filament) | Import CSV dans le panneau d’administration, `reference:import` | Lot. La fin est annoncée par une notification du panneau d’administration (table `notifications`). Les lignes en échec vont dans `failed_import_rows` |
| `audit:seal` | Tous les jours à 03:30 | Scelle la chaîne de hachage de l’audit ([Intégrité de l’audit](audit-and-maintenance.md)) |
| Signal de vie du planificateur | Toutes les minutes | Écrit l’heure de la dernière exécution dans le cache. `system:health-check` considère comme une défaillance une absence de plus de 5 minutes |

Pendant l’exécution d’une tâche de file d’attente, l’acteur de base de données est enregistré sous `system:job:{class}`.
