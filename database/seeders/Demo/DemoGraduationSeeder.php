<?php

namespace Database\Seeders\Demo;

use App\Domain\Graduation\Actions\RunDegreeAudits;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Graduation\DegreeAudit;
use App\Models\Term\Term;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Stored degree audits for the current term's third- and fourth-year students, so the
 * graduation screens have candidates (most still short of credits).
 */
class DemoGraduationSeeder extends Seeder
{
    public function run(RunDegreeAudits $audits): void
    {
        $registrar = User::query()->where('email', 'registrar@'.config('setup.demo.email_domain'))->first();
        $current = Term::query()->where('status', TermStatus::Published)->where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->first();

        if ($registrar === null || $current === null || DegreeAudit::query()->exists()) {
            return;
        }

        $audits->forAll($current, $registrar, minimumYearLevel: 3);
    }
}
