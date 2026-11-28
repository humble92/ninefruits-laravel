<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Institution\Settings\InstitutionSettings;
use App\Domain\Person\Actions\ManageYearLevels;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Organization\Program;
use App\Models\Person\YearLevelChange;
use App\Models\Person\YearLevelRun;
use App\Models\Term\Term;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use UnitEnum;

class YearLevelProgression extends Page
{
    use WithPagination;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::StudentRecords;

    protected string $view = 'filament.admin.year-level-progression';

    #[Url]
    public ?int $run = null;

    #[Url(as: 'program')]
    public ?int $reviewProgram = null;

    public function updatedReviewProgram(): void
    {
        $this->resetPage();
    }

    public static function getNavigationLabel(): string
    {
        return __('progression.title');
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public static function canAccess(): bool
    {
        return app(ManageYearLevels::class)->canManage(auth()->user());
    }

    public function selectedRun(): ?YearLevelRun
    {
        abort_unless(static::canAccess(), 403);

        return $this->run === null ? null : YearLevelRun::query()->with(['term', 'program'])->findOrFail($this->run);
    }

    /** @return LengthAwarePaginator<int, YearLevelRun> */
    public function runs(): LengthAwarePaginator
    {
        abort_unless(static::canAccess(), 403);

        return YearLevelRun::query()->with(['term', 'program'])->latest('id')->paginate(25);
    }

    /** @return LengthAwarePaginator<int, YearLevelChange> */
    public function changes(YearLevelRun $run): LengthAwarePaginator
    {
        abort_unless(static::canAccess(), 403);

        $program = $this->reviewProgram ?? null;

        return $run->changes()->with(['studentRecord', 'program'])
            ->when($run->program_id === null && $program !== null, fn ($query) => $query->where('program_id', $program))
            ->orderBy('id')->paginate(50);
    }

    /** @return array<int, string> */
    public function reviewPrograms(YearLevelRun $run): array
    {
        abort_unless(static::canAccess(), 403);

        return Program::query()->whereIn('id', $run->changes()->select('program_id')->getQuery())
            ->orderBy('code')->get()
            ->mapWithKeys(fn (Program $program) => [$program->id => $program->code.' · '.$program->name])->all();
    }

    protected function getHeaderActions(): array
    {
        if ($this->run === null) {
            return [
                Action::make('preview')->label(__('progression.preview'))->schema([
                    Select::make('term_id')->label(__('progression.target_term'))->required()->searchable()
                        ->options(fn () => Term::query()->orderByDesc('start_date')->pluck('code', 'id')),
                    Select::make('program_id')->label(__('progression.program'))
                        ->searchable()->placeholder(__('progression.all_programs'))->exists('programs', 'id')
                        ->options(fn () => Program::query()->orderBy('code')->get()
                            ->mapWithKeys(fn (Program $program) => [$program->id => $program->code.' · '.$program->name])),
                    DatePicker::make('cutoff_date')->label(__('progression.cutoff'))->maxDate(fn () => today(app(InstitutionSettings::class)->timezone))->required(),
                ])->action(function (array $data, ManageYearLevels $levels): void {
                    $run = ShowsActionErrors::run(
                        fn () => $levels->preview(
                            (int) $data['term_id'],
                            CarbonImmutable::parse($data['cutoff_date']),
                            auth()->user(),
                            filled($data['program_id'] ?? null) ? (int) $data['program_id'] : null,
                        ),
                    );
                    $this->redirect(static::getUrl(['run' => $run->id]));
                }),
            ];
        }

        return [
            Action::make('schedule')->label(__('progression.schedule'))
                ->visible(fn (): bool => $this->selectedRun()?->status === 'draft')
                ->schema([
                    DateTimePicker::make('scheduled_at')->label(__('progression.scheduled_at'))->seconds(false)->required()
                        ->helperText(fn () => app(InstitutionSettings::class)->timezone),
                ])->action(fn (array $data, ManageYearLevels $levels) => ShowsActionErrors::run(
                    fn () => $levels->schedule((int) $this->run, CarbonImmutable::parse($data['scheduled_at']), auth()->user()),
                )),
            Action::make('cancel')->label(__('progression.cancel'))->color('danger')->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->selectedRun()?->status, ['draft', 'scheduled'], true))
                ->action(fn (ManageYearLevels $levels) => ShowsActionErrors::run(
                    fn () => $levels->cancel((int) $this->run, auth()->user()),
                )),
        ];
    }

    public function toggleExclusion(int $changeId, ManageYearLevels $levels): void
    {
        $levels->exclude((int) $this->run, $changeId, auth()->user());
    }
}
