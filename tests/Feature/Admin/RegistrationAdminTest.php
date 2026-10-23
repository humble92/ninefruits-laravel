<?php

use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Registration\Enums\EnrollmentSource;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Registration\Enums\HoldType;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Resources\Offering\Sections\Pages\EditSection;
use App\Filament\Admin\Resources\Offering\Sections\RelationManagers\RosterRelationManager;
use App\Filament\Admin\Resources\Offering\Sections\RelationManagers\WaitlistRelationManager;
use App\Filament\Admin\Resources\Offering\Sections\SectionResource;
use App\Filament\Admin\Resources\Registration\Attempts\RegistrationAttemptResource;
use App\Filament\Admin\Resources\Registration\CreditLimitPolicies\CreditLimitPolicyResource;
use App\Filament\Admin\Resources\Registration\CreditLimitPolicies\Pages\ManageCreditLimitPolicies;
use App\Filament\Admin\Resources\Registration\Enrollments\EnrollmentResource;
use App\Filament\Admin\Resources\Registration\Enrollments\Pages\ListEnrollments;
use App\Filament\Admin\Resources\Registration\Holds\Pages\ListRegistrationHolds;
use App\Filament\Admin\Resources\Registration\Holds\RegistrationHoldResource;
use App\Filament\Admin\Resources\Registration\Overrides\Pages\ListRegistrationOverrides;
use App\Filament\Admin\Resources\Registration\Overrides\RegistrationOverrideResource;
use App\Filament\Admin\Resources\Registration\RuleSets\Pages\EditRegistrationRuleSet;
use App\Filament\Admin\Resources\Registration\RuleSets\Pages\ListRegistrationRuleSets;
use App\Filament\Admin\Resources\Registration\RuleSets\RegistrationRuleSetResource;
use App\Filament\Admin\Resources\Registration\RuleSets\RelationManagers\RulesRelationManager;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Registration\Enrollment;
use App\Models\Registration\RegistrationHold;
use App\Models\Registration\RegistrationOverride;
use App\Models\Registration\RegistrationRuleSet;
use App\Models\Registration\WaitlistEntry;
use App\Models\Term\Term;
use App\Models\User;
use App\Support\Setup\DemoOptions;
use Database\Seeders\Demo\DemoAccountSeeder;
use Database\Seeders\Demo\DemoCurriculumSeeder;
use Database\Seeders\Demo\DemoPeopleSeeder;
use Database\Seeders\Demo\DemoReferenceSeeder;
use Database\Seeders\Demo\DemoRegistrationSeeder;
use Database\Seeders\Essential\EssentialSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    // The smallest demo that still has every kind of data: the full one per test is slow.
    app()->instance(DemoOptions::class, new DemoOptions('tiny'));
    $this->seed([EssentialSeeder::class, DemoAccountSeeder::class, DemoReferenceSeeder::class, DemoPeopleSeeder::class, DemoCurriculumSeeder::class, DemoRegistrationSeeder::class]);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');
    $this->manager = User::factory()->withPermissions(Permission::cases())->create();
    // A section with free seats (the demo also has a full next-term section with a waitlist).
    $this->enrollment = Enrollment::query()->where('status', EnrollmentStatus::Enrolled)
        ->whereHas('section', fn ($section) => $section->whereColumn('enrolled_count', '<', 'capacity'))->with('section')->orderBy('id')->first();
});

it('seeds consistent demo registrations', function () {
    expect(Enrollment::query()->count())->toBeGreaterThan(0)
        ->and(WaitlistEntry::query()->count())->toBeGreaterThan(0)
        ->and(RegistrationHold::query()->count())->toBe(2)
        ->and(RegistrationRuleSet::query()->count())->toBe(2);

    Section::query()->get()->each(fn (Section $section) => expect($section->enrolled_count)
        ->toBe($section->enrollments()->whereIn('status', [EnrollmentStatus::Enrolled, EnrollmentStatus::Completed])->count())
        ->toBeLessThanOrEqual($section->capacity));
});

it('renders the registration pages', function () {
    $pages = [
        EnrollmentResource::getUrl('index'), RegistrationHoldResource::getUrl('index'), RegistrationOverrideResource::getUrl('index'),
        RegistrationRuleSetResource::getUrl('index'), RegistrationRuleSetResource::getUrl('edit', ['record' => RegistrationRuleSet::query()->first()]),
        CreditLimitPolicyResource::getUrl('index'), RegistrationAttemptResource::getUrl('index'),
        SectionResource::getUrl('edit', ['record' => $this->enrollment->section]),
    ];

    foreach ($pages as $url) {
        $this->actingAs($this->manager)->get($url)->assertOk();
    }

    $student = User::query()->where('email', 'student@demo.test')->sole();
    $this->actingAs($student)->get(route('portal.registration'))->assertOk()->assertSee(__('registration.portal.cart'));
});

it('registers a student as staff with a waived rule and drops with a reason', function () {
    $section = $this->enrollment->section;
    $record = StudentRecord::query()->where('status', StudentStatus::Enrolled)->whereDoesntHave('holds')->whereDoesntHave('enrollments', fn ($query) => $query->where('term_id', $section->term_id)->where('course_id', $section->course_id))
        ->get()->first(fn (StudentRecord $record) => $section->admits($record));

    Livewire::actingAs($this->manager)->test(RosterRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditSection::class])
        ->callTableAction('staffEnroll', data: ['student_record_id' => $record->id, 'waive' => [RuleCode::Requisites->value, RuleCode::TimeConflict->value, RuleCode::CreditLimit->value], 'reason' => 'Advisor approval'])
        ->assertHasNoTableActionErrors();

    $enrollment = Enrollment::query()->where('student_record_id', $record->id)->where('section_id', $section->id)->sole();
    expect($enrollment->source)->toBe(EnrollmentSource::Staff);

    Livewire::actingAs($this->manager)->test(ListEnrollments::class)
        ->callTableAction('drop', $enrollment, ['reason' => 'Requested by the student'])
        ->assertHasNoTableActionErrors();

    expect($enrollment->refresh()->status)->toBe(EnrollmentStatus::Dropped);
});

it('places and releases a hold and grants an override', function () {
    $record = $this->enrollment->studentRecord;

    Livewire::actingAs($this->manager)->test(ListRegistrationHolds::class)
        ->callAction('placeHold', ['student_record_id' => $record->id, 'hold_type' => HoldType::Academic->value, 'blocks_registration' => true, 'blocks_grades' => false, 'blocks_certificates' => false, 'reason' => 'Probation meeting'])
        ->assertHasNoActionErrors();

    $hold = RegistrationHold::query()->where('student_record_id', $record->id)->where('hold_type', HoldType::Academic)->sole();

    Livewire::actingAs($this->manager)->test(ListRegistrationHolds::class)
        ->callTableAction('release', $hold, ['reason' => 'Met the advisor']);

    expect($hold->refresh()->released_at)->not->toBeNull();

    Livewire::actingAs($this->manager)->test(ListRegistrationOverrides::class)
        ->callAction('grantOverride', ['student_record_id' => $record->id, 'term_id' => $this->enrollment->term_id, 'rule_code' => RuleCode::CreditLimit->value, 'reason' => 'Graduating senior'])
        ->assertHasNoActionErrors();

    expect(RegistrationOverride::query()->where('student_record_id', $record->id)->sole()->rule_code)->toBe(RuleCode::CreditLimit);
});

it('prepares a rule set by copying and edits a rule with its parameters', function () {
    $term = Term::factory()->published()->between(now()->addYear()->toDateString(), now()->addYear()->addMonths(4)->toDateString())->create();
    $source = RegistrationRuleSet::query()->first();

    Livewire::actingAs($this->manager)->test(ListRegistrationRuleSets::class)
        ->callAction('prepare', ['term_id' => $term->id, 'copy_from_id' => $source->id]);

    $set = RegistrationRuleSet::query()->where('term_id', $term->id)->sole();
    $rule = $set->rules()->where('rule_code', RuleCode::TimeConflict)->sole();

    Livewire::actingAs($this->manager)->test(RulesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditRegistrationRuleSet::class])
        ->callTableAction('edit', $rule, ['is_enabled' => true, 'allows_override' => true, 'severity' => RuleSeverity::Warn->value, 'parameters' => ['allowed_overlap_minutes' => 15]])
        ->assertHasNoTableActionErrors();

    expect($rule->refresh())
        ->severity->toBe(RuleSeverity::Warn)
        ->parameters->toBe(['allowed_overlap_minutes' => 15])
        ->and($set->rules()->where('rule_code', RuleCode::Hold)->sole()->parameters)->toBeNull();
});

it('creates a credit limit for a group of students', function () {
    Livewire::actingAs($this->manager)->test(ManageCreditLimitPolicies::class)
        ->callAction('create', ['name' => ['en' => 'Honors'], 'priority' => 50, 'max_credits' => 24, 'is_active' => true, 'eligibility' => ['year_levels' => [4]]])
        ->assertHasNoActionErrors();

    expect(CreditLimitPolicy::query()->where('priority', 50)->sole()->eligibility->yearLevels)->toBe([4]);
});

it('keeps capacity at or above the registered students', function () {
    $section = Section::query()->where('enrolled_count', '>', 1)->first();

    Livewire::actingAs($this->manager)->test(EditSection::class, ['record' => $section->getRouteKey()])
        ->fillForm(['capacity' => 0])
        ->call('save')
        ->assertHasErrors();

    expect($section->refresh()->capacity)->toBeGreaterThan(0);
});

it('lists the waitlist and removes an entry with a reason', function () {
    $entry = WaitlistEntry::query()->with('section')->first();

    Livewire::actingAs($this->manager)->test(WaitlistRelationManager::class, ['ownerRecord' => $entry->section, 'pageClass' => EditSection::class])
        ->assertSee($entry->studentRecord->student_number)
        ->callTableAction('remove', $entry, ['reason' => 'Changed plans']);

    expect($entry->refresh()->status->value)->toBe('removed');
});
