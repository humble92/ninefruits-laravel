<?php

namespace Database\Seeders\Demo;

use App\Domain\Lms\Actions\SyncSection;
use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Lms\LmsPlatform;
use App\Models\Offering\Section;
use App\Models\Term\Term;
use Illuminate\Database\Seeder;

/**
 * A "null" platform (records, no calls) with the current term's sections linked, so the
 * links, logs and portal course links can be seen.
 */
class DemoLmsSeeder extends Seeder
{
    public function run(SyncSection $sync): void
    {
        $current = Term::query()->where('status', TermStatus::Published)->where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->first();

        if ($current === null || LmsPlatform::query()->exists()) {
            return;
        }

        $platform = LmsPlatform::query()->create([
            'code' => 'DEMO', 'name' => ['en' => 'Demo LMS', 'ko' => '시연용 LMS', 'zh_CN' => '演示 LMS'], 'driver' => 'null',
            'base_url' => 'https://lms.example.test', 'is_active' => true, 'should_sync_enrollments' => true, 'should_pull_grades' => false,
        ]);

        foreach (Section::query()->where('term_id', $current->id)->where('status', SectionStatus::Published)->limit(20)->get() as $section) {
            $sync->handle($platform, $section);
        }
    }
}
