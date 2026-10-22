<?php

namespace App\Console\Commands\Person;

use App\Console\Commands\AuditedCommand;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Lists people that are probably the same person: the same national id, or the same name
 * and birth date. Merging is done on the person screen (merge action), after checking.
 */
#[Signature('people:find-duplicates {--limit=100 : Maximum groups to list}')]
#[Description('List people who may be duplicates (same national id, or same name and birth date)')]
class FindDuplicatePeople extends Command
{
    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $groups = DB::select(<<<'SQL'
            SELECT 'national_id' AS reason, array_agg(id ORDER BY id) AS ids
            FROM people
            WHERE merged_into_person_id IS NULL AND national_id_hash IS NOT NULL
            GROUP BY national_id_hash
            HAVING count(*) > 1
            UNION ALL
            SELECT 'name_birth_date', array_agg(id ORDER BY id)
            FROM people
            WHERE merged_into_person_id IS NULL AND birth_date IS NOT NULL
            GROUP BY lower(family_name), lower(coalesce(given_name, '')), birth_date
            HAVING count(*) > 1
            ORDER BY 1, 2
            LIMIT ?
            SQL, [$limit]);

        if ($groups === []) {
            $this->components->info('No likely duplicates.');

            return self::SUCCESS;
        }

        $this->table(['Reason', 'Person IDs'], array_map(
            fn (object $group) => [$group->reason, trim((string) $group->ids, '{}')],
            $groups,
        ));
        $this->components->warn(count($groups).' group(s). Check each on the person screen and merge with the merge action.');

        return AuditedCommand::PROBLEMS_FOUND;
    }
}
