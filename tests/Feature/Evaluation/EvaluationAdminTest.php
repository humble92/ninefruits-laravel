<?php

use App\Domain\Evaluation\Notifications\EvaluationReminderNotification;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Domain\Term\Enums\TermWindowType;
use App\Filament\Admin\Pages\ManageEvaluationSettings;
use App\Filament\Admin\Resources\Evaluation\Forms\EvaluationFormResource;
use App\Filament\Admin\Resources\Evaluation\Forms\Pages\EditEvaluationForm;
use App\Filament\Admin\Resources\Evaluation\Forms\RelationManagers\QuestionsRelationManager;
use App\Filament\Admin\Resources\Evaluation\Sections\SectionEvaluationResource;
use App\Filament\Admin\Resources\Evaluation\Terms\Pages\ListTermEvaluations;
use App\Filament\Admin\Resources\Evaluation\Terms\TermEvaluationResource;
use App\Models\Evaluation\EvaluationForm;
use App\Models\Evaluation\EvaluationResponse;
use App\Models\Evaluation\TermEvaluation;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Models\User;
use Database\Seeders\Essential\EssentialSeeder;
use Database\Seeders\Profiles\KoreanUniversityProfileSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed([EssentialSeeder::class, KoreanUniversityProfileSeeder::class]);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');
    $this->manager = User::factory()->withPermissions(Permission::cases())->create();
    $this->form = EvaluationForm::query()->where('code', 'STANDARD')->sole();
    $this->term = Term::factory()->published()->between(now()->subMonths(4)->toDateString(), now()->addWeek()->toDateString())->create();
});

it('sets up a term, renders the evaluation pages and publishes', function () {
    Livewire::actingAs($this->manager)->test(ListTermEvaluations::class)
        ->callAction('prepare', ['term_id' => $this->term->id, 'evaluation_form_id' => $this->form->id, 'minimum_responses' => 4])
        ->assertHasNoActionErrors();

    $evaluation = TermEvaluation::query()->sole();
    expect($evaluation)->minimum_responses->toBe(4)->minimum_response_percent->toBe(25);

    foreach ([TermEvaluationResource::getUrl('index'), EvaluationFormResource::getUrl('index'), EvaluationFormResource::getUrl('create'),
        EvaluationFormResource::getUrl('edit', ['record' => $this->form]), SectionEvaluationResource::getUrl('index'), ManageEvaluationSettings::getUrl()] as $url) {
        $this->actingAs($this->manager)->get($url)->assertOk();
    }

    Livewire::actingAs($this->manager)->test(ListTermEvaluations::class)->callTableAction('publish', $evaluation);
    expect($evaluation->refresh()->results_published_at)->not->toBeNull();
});

it('locks the questions of a form with responses and copies it', function () {
    TermEvaluation::query()->create(['term_id' => $this->term->id, 'evaluation_form_id' => $this->form->id]);
    EvaluationResponse::query()->create(['term_id' => $this->term->id, 'section_id' => Section::factory()->inTerm($this->term)->create()->id, 'evaluation_form_id' => $this->form->id]);

    expect($this->form->isLocked())->toBeTrue();
    Livewire::actingAs($this->manager)->test(QuestionsRelationManager::class, ['ownerRecord' => $this->form, 'pageClass' => EditEvaluationForm::class])
        ->assertTableActionHidden('edit', $this->form->questions()->first());

    Livewire::actingAs($this->manager)->test(EditEvaluationForm::class, ['record' => $this->form->getRouteKey()])
        ->callAction('copy', ['code' => 'STANDARD2']);

    $copy = EvaluationForm::query()->where('code', 'STANDARD2')->sole();
    expect($copy->questions()->count())->toBe($this->form->questions()->count())
        ->and($copy->isLocked())->toBeFalse();
});

it('reminds students with open evaluations and publishes results by command', function () {
    TermEvaluation::query()->create(['term_id' => $this->term->id, 'evaluation_form_id' => $this->form->id]);
    TermWindow::factory()->for($this->term)->ofType(TermWindowType::CourseEvaluation)
        ->between(now()->subDay(), now()->addWeek())->create(['term_session_id' => $this->term->fullTermSession()->id]);
    $record = StudentRecord::factory()->create();
    Enrollment::factory()->inSection(Section::factory()->published()->inTerm($this->term)->create())->create(['student_record_id' => $record->id]);
    $student = User::factory()->create(['person_id' => $record->person_id]);

    $this->artisan('evaluation:remind', ['term' => $this->term->code, '--dry-run' => true])->expectsOutputToContain('1 student(s) with evaluations to do, 0 reminder(s) sent.')->assertSuccessful();
    $this->artisan('evaluation:remind', ['term' => $this->term->code])->expectsOutputToContain('1 reminder(s) sent.')->assertSuccessful();
    Notification::assertSentTo($student, EvaluationReminderNotification::class);

    $this->artisan('evaluation:publish-results', ['term' => 'NOPE'])->assertFailed();
    $this->artisan('evaluation:publish-results', ['term' => $this->term->code])->expectsOutputToContain('Published')->assertSuccessful();
    expect(TermEvaluation::query()->sole()->results_published_at)->not->toBeNull();
});
