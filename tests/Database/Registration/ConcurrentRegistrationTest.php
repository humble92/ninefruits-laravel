<?php

/*
| Phase 5 completion criterion: concurrent requests never exceed the capacity. Real
| processes (pcntl_fork) with their own database connections register different students
| into one section at the same moment. Fixtures are committed through the migrator
| connection, because the test's own connection is inside a rolled-back transaction the
| other processes cannot see, and removed afterwards.
*/

use App\Domain\Registration\Actions\EnrollStudent;
use App\Domain\Term\Enums\TermWindowType;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Support\Database\DatabaseSessionContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

const CONCURRENT_TABLES = [
    'registration_attempts', 'waitlist_entries', 'enrollments', 'section_meetings', 'sections', 'term_windows', 'term_sessions', 'terms',
    'academic_years', 'term_types', 'course_versions', 'courses', 'student_records', 'people', 'programs', 'org_units', 'org_unit_types',
];

function committed(Closure $callback): mixed
{
    $default = DB::getDefaultConnection();
    DB::setDefaultConnection('pgsql_migrator');

    try {
        return $callback();
    } finally {
        DB::setDefaultConnection($default);
    }
}

it('never seats more students than the capacity under concurrent requests', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required.');
    }

    $capacity = 5;
    $students = 16;

    [$sectionId, $recordIds] = committed(function () use ($capacity, $students) {
        $term = Term::factory()->published()->between(now()->addMonth()->toDateString(), now()->addMonths(5)->toDateString())->create();
        TermWindow::factory()->for($term)->ofType(TermWindowType::Registration)->between(now()->subHour(), now()->addHour())->create();
        $section = Section::factory()->published()->inTerm($term)->create(['capacity' => $capacity]);

        return [$section->id, StudentRecord::factory()->count($students)->create()->modelKeys()];
    });

    $resultDirectory = sys_get_temp_dir().'/concurrent-'.uniqid();
    mkdir($resultDirectory);
    $startAt = microtime(true) + 1.0;
    $children = [];

    try {
        foreach ($recordIds as $recordId) {
            $pid = pcntl_fork();

            if ($pid === 0) {
                // Child: open its own connections. The inherited ones share the parent's
                // sockets: closing them would end the parent's sessions, so the old manager
                // is kept alive (never destructed: the child is killed at the end).
                $GLOBALS['inherited_database_manager'] = app('db');
                app()->forgetInstance('db');
                app()->forgetInstance('db.connection');
                app()->forgetInstance(DatabaseSessionContext::class);
                DB::clearResolvedInstance('db');
                Model::setConnectionResolver(app('db'));

                $outcome = 'error';

                try {
                    app(DatabaseSessionContext::class)->useBaseline([]);
                    app(DatabaseSessionContext::class)->forSystem('test:concurrency');
                    $record = StudentRecord::query()->findOrFail($recordId);
                    $section = Section::query()->findOrFail($sectionId);
                    time_sleep_until($startAt);
                    $result = app(EnrollStudent::class)->handle($record, $section, null);
                    $outcome = $result->succeeded() ? 'enrolled' : ($result->refusedForSeat() ? 'full' : 'refused');
                } catch (Throwable $exception) {
                    $outcome = 'error: '.$exception->getMessage();
                }

                file_put_contents("{$resultDirectory}/{$recordId}", $outcome);
                posix_kill(posix_getpid(), SIGKILL);
            }

            $children[] = $pid;
        }

        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
        }

        $outcomes = array_map(fn (string $file) => file_get_contents($file), glob("{$resultDirectory}/*") ?: []);
        $counted = committed(fn () => DB::table('sections')->where('id', $sectionId)->value('enrolled_count'));
        $rows = committed(fn () => DB::table('enrollments')->where('section_id', $sectionId)->where('status', 'enrolled')->count());

        expect($outcomes)->toHaveCount($students)
            ->and(array_count_values($outcomes))->toEqual(['enrolled' => $capacity, 'full' => $students - $capacity])
            ->and($counted)->toBe($capacity)
            ->and($rows)->toBe($capacity);
    } finally {
        array_map('unlink', glob("{$resultDirectory}/*") ?: []);
        @rmdir($resultDirectory);
        committed(fn () => DB::statement('TRUNCATE '.implode(', ', CONCURRENT_TABLES).' CASCADE'));
    }
});
