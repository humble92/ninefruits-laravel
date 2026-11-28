<?php

use App\Domain\Institution\Settings\InstitutionSettings;
use App\Domain\Person\Actions\ManageYearLevels;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Pages\YearLevelProgression;
use App\Filament\Admin\Resources\StudentRecords\YearLevelRules\ManageYearLevelRules;
use App\Filament\Admin\Resources\StudentRecords\YearLevelRules\YearLevelRuleResource;
use App\Models\Grading\TermRecord;
use App\Models\Organization\Program;
use App\Models\Person\StudentRecord;
use App\Models\Person\YearLevelRule;
use App\Models\Person\YearLevelRun;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('reviews and applies absolute levels once, using the program rule and only closed results', function () {
    $this->travelTo(CarbonImmutable::parse('2035-01-25 09:00:00'));
    $staff = User::factory()->withPermissions([Permission::StudentRecordsManage, Permission::InstitutionSettingsManage])->create();
    $action = app(ManageYearLevels::class);
    $action->saveRule(null, ['name' => ['en' => 'Credits'], 'basis' => 'credits', 'thresholds' => [['minimum' => 24], ['minimum' => 48]], 'is_default' => true], $staff);
    $override = $action->saveRule(null, ['name' => ['en' => 'Terms'], 'basis' => 'terms', 'thresholds' => [['minimum' => 1]], 'is_default' => false], $staff);
    $program = Program::factory()->create(['year_level_rule_id' => $override->id]);
    $target = Term::factory()->between('2035-03-02', '2035-06-30')->create();
    $closed = Term::factory()->between('2034-09-01', '2034-12-31')->create();
    $retakeTerm = Term::factory()->between('2035-01-01', '2035-01-10')->create();
    $unclosed = Term::factory()->between('2035-01-01', '2035-01-20')->create();
    $cutoff = CarbonImmutable::parse('2035-01-20');
    $now = now();
    $eligible = StudentRecord::factory()->create();
    $stale = StudentRecord::factory()->create();
    $excluded = StudentRecord::factory()->create();
    $overrideStudent = StudentRecord::factory()->inProgram($program)->create();
    $onLeave = StudentRecord::factory()->status(StudentStatus::OnLeave)->create();
    $noClosedResults = StudentRecord::factory()->create();
    $retake = StudentRecord::factory()->create();
    foreach ([[$eligible, 50], [$stale, 24], [$excluded, 24], [$overrideStudent, 1], [$onLeave, 50], [$retake, 15]] as [$student, $credits]) {
        TermRecord::query()->create(['student_record_id' => $student->id, 'term_id' => $closed->id, 'earned_credits' => $credits, 'attempted_credits' => $credits, 'cumulative_earned_credits' => $credits, 'calculated_at' => $now])
            ->forceFill(['closed_at' => $now])->save();
    }
    TermRecord::query()->create([
        'student_record_id' => $retake->id, 'term_id' => $retakeTerm->id,
        'earned_credits' => 15, 'attempted_credits' => 15, 'cumulative_earned_credits' => 15, 'calculated_at' => $now,
    ])->forceFill(['closed_at' => $now])->save();
    TermRecord::query()->create(['student_record_id' => $noClosedResults->id, 'term_id' => $unclosed->id, 'earned_credits' => 100, 'attempted_credits' => 100, 'cumulative_earned_credits' => 100, 'calculated_at' => $now]);

    $run = $action->preview($target->id, $cutoff, $staff);
    $changes = $run->changes()->get()->keyBy('student_record_id');
    expect($changes)->toHaveCount(4)
        ->and($changes[$eligible->id]->to_level)->toBe(3)
        ->and($changes[$overrideStudent->id]->to_level)->toBe(2);
    $action->exclude($run->id, $changes[$excluded->id]->id, $staff);
    $action->schedule($run->id, CarbonImmutable::now()->addHour(), $staff);
    expect($action->applyDue())->toBe(0);
    $stale->forceFill(['status' => StudentStatus::OnLeave])->save();
    $this->travel(61)->minutes();
    $this->artisan('students:apply-year-levels')->assertSuccessful();
    expect($action->applyDue())->toBe(0)
        ->and($run->fresh()->status)->toBe('applied')
        ->and($eligible->fresh()->year_level)->toBe(3)
        ->and($overrideStudent->fresh()->year_level)->toBe(2)
        ->and($excluded->fresh()->year_level)->toBe(1)
        ->and($retake->fresh()->year_level)->toBe(1)
        ->and($stale->fresh()->year_level)->toBe(1)
        ->and($changes[$stale->id]->fresh()->outcome)->toBe('stale');
});

it('renders both progression screens and saves a rule through the settings form', function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    Filament::setCurrentPanel('admin');
    $staff = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::StudentRecordsManage, Permission::InstitutionSettingsManage])->create();
    $this->actingAs($staff)->get(YearLevelProgression::getUrl())->assertOk();
    $this->actingAs($staff)->get(YearLevelRuleResource::getUrl())->assertOk();
    Livewire::actingAs($staff)->test(ManageYearLevelRules::class)->mountAction('create')->set('mountedActions.0.data', [
        'name' => ['en' => 'Standard'], 'basis' => 'credits', 'is_default' => true,
        'thresholds' => [['minimum' => 24]],
    ])->callMountedAction()->assertHasNoActionErrors();
    expect(YearLevelRule::query()->where('is_default', true)->sole()->name)->toBe('Standard');
    $this->travelTo(CarbonImmutable::parse('2035-01-25 09:00:00'));
    $settings = app(InstitutionSettings::class);
    $settings->timezone = 'Asia/Seoul';
    $settings->save();

    $term = Term::factory()->between('2035-03-02', '2035-06-30')->create();
    $closed = Term::factory()->between('2034-09-01', '2034-12-31')->create();
    $student = StudentRecord::factory()->create();
    TermRecord::query()->create([
        'student_record_id' => $student->id, 'term_id' => $closed->id, 'earned_credits' => 24,
        'attempted_credits' => 24, 'cumulative_earned_credits' => 24, 'calculated_at' => now(),
    ])->forceFill(['closed_at' => now()])->save();
    Livewire::actingAs($staff)->test(YearLevelProgression::class)->mountAction('preview')
        ->set('mountedActions.0.data', ['term_id' => $term->id, 'cutoff_date' => '2035-01-20'])
        ->callMountedAction()->assertHasNoActionErrors();

    $run = YearLevelRun::query()->where('term_id', $term->id)->sole();
    $this->get(YearLevelProgression::getUrl(['run' => $run->id]))->assertOk()->assertSee('1 → 2');
    Livewire::actingAs($staff)->withQueryParams(['run' => $run->id])->test(YearLevelProgression::class)
        ->mountAction('schedule')->set('mountedActions.0.data', ['scheduled_at' => '2035-01-26 10:00'])
        ->callMountedAction()->assertHasNoActionErrors();
    expect($run->fresh()->scheduled_at->utc()->format('Y-m-d H:i'))->toBe('2035-01-26 01:00');
});
