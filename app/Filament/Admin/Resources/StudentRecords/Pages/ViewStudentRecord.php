<?php

namespace App\Filament\Admin\Resources\StudentRecords\Pages;

use App\Domain\Graduation\Actions\RunDegreeAudits;
use App\Domain\Graduation\Actions\WaiveRequirement;
use App\Domain\Graduation\Services\DegreeAudit;
use App\Domain\Person\Actions\AssignAdvisor;
use App\Domain\Person\Actions\RecordStatusChange;
use App\Domain\Person\Enums\AdvisorRole;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Resources\StudentRecords\StudentRecordResource;
use App\Filament\Admin\Support\IssueCertificateAction;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Person\Instructor;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentStatusChangeType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ViewStudentRecord extends ViewRecord
{
    protected static string $resource = StudentRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('degreeAudit')->label(__('graduation.actions.audit_now'))->icon(Heroicon::OutlinedAcademicCap)->color('gray')
                ->visible(fn () => (bool) auth()->user()?->can(Permission::GraduationAudit->value))
                ->modalHeading(__('graduation.actions.audit_now'))
                ->modalContent(fn (StudentRecord $record) => view('filament.admin.degree-audits', ['results' => array_map(fn ($r) => $r->toArray(), app(DegreeAudit::class)->evaluateAll($record))]))
                ->modalSubmitActionLabel(__('graduation.actions.save_audit'))
                ->action(function (StudentRecord $record) {
                    /** @var User $user */
                    $user = auth()->user();
                    ShowsActionErrors::run(fn () => app(RunDegreeAudits::class)->forRecord($record, $user));
                    Notification::make()->success()->title(__('graduation.notifications.audit_saved'))->send();
                }),
            Action::make('waiveRequirement')->label(__('graduation.actions.waive'))->icon(Heroicon::OutlinedShieldCheck)->color('gray')->modalDescription(__('graduation.help.waiver'))
                ->visible(fn () => (bool) auth()->user()?->can(Permission::GraduationProcess->value))
                ->schema([
                    Select::make('curriculum_requirement_id')->label(__('curriculum.sections.requirements'))->required()
                        ->options(function (ViewStudentRecord $livewire) {
                            /** @var StudentRecord $record */
                            $record = $livewire->getRecord();
                            $options = [];

                            // Built by hand: flatMap would renumber the integer keys.
                            foreach (CurriculumRequirementSet::query()->where('program_id', $record->program_id)->forCatalogYear($record->catalog_year)->with('requirements.assessmentType')->get() as $set) {
                                foreach ($set->requirements as $requirement) {
                                    $options[$requirement->id] = $set->program_role->label().' · '.($requirement->name ?? ($requirement->assessmentType !== null ? $requirement->assessmentType->name : $requirement->kind->label()));
                                }
                            }

                            return $options;
                        }),
                    Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
                ])
                ->action(function (StudentRecord $record, array $data) {
                    /** @var User $user */
                    $user = auth()->user();
                    ShowsActionErrors::run(fn () => app(WaiveRequirement::class)->request($record, CurriculumRequirement::query()->findOrFail($data['curriculum_requirement_id']), (string) $data['reason'], $user));
                    Notification::make()->success()->title(__('graduation.notifications.waiver_requested'))->send();
                }),
            IssueCertificateAction::make(),
            Action::make('transcript')->label(__('grading.transcript.title'))->icon(Heroicon::OutlinedDocumentText)->color('gray')
                ->visible(fn () => (bool) auth()->user()?->can(Permission::GradesView->value))
                ->url(fn (StudentRecord $record) => route('transcripts.show', $record))->openUrlInNewTab(),
            Action::make('recordStatusChange')
                ->label(__('people.actions.record_status_change'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->authorize(fn (StudentRecord $record) => Gate::allows('changeStatus', $record))
                ->schema([
                    Select::make('status_change_type_id')->label(__('people.fields.status_change_type'))
                        ->options(fn (StudentRecord $record) => StudentStatusChangeType::query()->where('is_active', true)->orderBy('sort_order')->get()
                            ->filter(fn (StudentStatusChangeType $type) => $type->appliesFrom($record->status))
                            ->mapWithKeys(fn (StudentStatusChangeType $type) => [$type->id => $type->name]))
                        ->required(),
                    DatePicker::make('effective_date')->label(__('people.fields.effective_date'))->default(now())->required(),
                    Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(2000),
                ])
                ->action(function (StudentRecord $record, array $data, RecordStatusChange $recordChange) {
                    $recordChange->handle($record, StudentStatusChangeType::query()->findOrFail($data['status_change_type_id']), CarbonImmutable::parse($data['effective_date']), $data['reason'], $this->actor());
                    $this->refreshFormData(['status']);
                    Notification::make()->success()->title(__('people.notifications.status_changed'))->send();
                }),
            Action::make('assignAdvisor')
                ->label(__('people.actions.assign_advisor'))
                ->icon(Heroicon::OutlinedUserPlus)
                ->authorize(fn (StudentRecord $record) => Gate::allows('assignAdvisor', $record))
                ->schema([
                    Select::make('instructor_id')->label(__('people.fields.advisor'))
                        ->options(fn () => Instructor::query()->current()->with('person')->get()->mapWithKeys(fn (Instructor $instructor) => [$instructor->id => $instructor->person->displayName().' ('.$instructor->employee_number.')']))
                        ->searchable()->required(),
                    Select::make('role')->label(__('people.fields.advisor_role'))
                        ->options(collect(AdvisorRole::cases())->mapWithKeys(fn (AdvisorRole $role) => [$role->value => $role->label()]))
                        ->default(AdvisorRole::Primary->value)->required(),
                    DatePicker::make('start_date')->label(__('reference.fields.start_date'))->default(now())->required(),
                ])
                ->action(function (StudentRecord $record, array $data, AssignAdvisor $assign) {
                    $assign->handle($record, Instructor::query()->findOrFail($data['instructor_id']), CarbonImmutable::parse($data['start_date']), AdvisorRole::from($data['role']), $this->actor());
                    Notification::make()->success()->title(__('people.notifications.advisor_assigned'))->send();
                }),
        ];
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
