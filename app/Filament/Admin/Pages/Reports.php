<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Grading\Enums\AcademicStanding;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Reporting\Services\ReportCatalog;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\NavigationGroup;
use App\Models\Organization\OrgUnit;
use App\Models\Term\Term;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * The standard reports (legacy report.php menu, many entries of which were dead): pick a
 * report and its filters, see the rows, download the CSV.
 *
 * @property-read Schema $form
 */
class Reports extends Page
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Reports;

    protected static ?string $slug = 'reports';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::ReportsView->value);
    }

    public static function getNavigationLabel(): string
    {
        return __('graduation.reports.title');
    }

    public function getTitle(): string
    {
        return __('graduation.reports.title');
    }

    public function mount(): void
    {
        $this->form->fill(['report' => 'students']);
    }

    public function form(Schema $schema): Schema
    {
        $uses = fn (string $param) => fn (Get $get) => in_array($param, ReportCatalog::REPORTS[$get('report') ?? 'students']['params'], true);

        return $schema->statePath('data')->columns(4)->components([
            Select::make('report')->label(__('graduation.reports.report'))->live()->selectablePlaceholder(false)
                ->options(collect(ReportCatalog::keys())->mapWithKeys(fn (string $key) => [$key => __('graduation.reports.names.'.$key)])),
            Select::make('term')->label(__('reference.resources.terms.label'))->live()->visible($uses('term'))
                ->options(fn () => Term::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name.' ('.$term->code.')'])),
            Select::make('org_unit')->label(__('reference.resources.org_units.label'))->live()->searchable()->visible($uses('org_unit'))
                ->options(fn () => OrgUnit::query()->orderBy('code')->get()->mapWithKeys(fn (OrgUnit $unit) => [$unit->id => $unit->code.' '.$unit->name])),
            Select::make('status')->label(__('registration.fields.status'))->live()->visible($uses('status'))
                ->options(collect(StudentStatus::cases())->mapWithKeys(fn (StudentStatus $status) => [$status->value => $status->label()])),
            Select::make('year_level')->label(__('people.fields.year_level'))->live()->visible($uses('year_level'))->options(array_combine(range(1, 6), array_map('strval', range(1, 6)))),
            Select::make('standing')->label(__('grading.fields.standing'))->live()->visible($uses('standing'))
                ->options(collect(AcademicStanding::cases())->mapWithKeys(fn (AcademicStanding $standing) => [$standing->value => $standing->label()])),
            Select::make('satisfied')->label(__('graduation.fields.is_satisfied'))->live()->visible($uses('satisfied'))->options(['yes' => __('graduation.satisfied.yes'), 'no' => __('graduation.satisfied.no')]),
            DatePicker::make('date_from')->label(__('reference.fields.start_date'))->live()->visible($uses('date_from')),
            DatePicker::make('date_to')->label(__('reference.fields.end_date'))->live()->visible($uses('date_to')),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('form'),
            View::make('filament.admin.report')->viewData(fn () => ['report' => $this->report(), 'rows' => $this->rows(), 'columns' => ReportCatalog::REPORTS[$this->report()]['columns'], 'csvUrl' => $this->csvUrl()]),
        ]);
    }

    public function report(): string
    {
        $key = $this->data['report'] ?? 'students';

        return array_key_exists($key, ReportCatalog::REPORTS) ? $key : 'students';
    }

    /**
     * @return Collection<int, \stdClass>
     */
    public function rows(): Collection
    {
        if (in_array('term', ReportCatalog::REPORTS[$this->report()]['params'], true) && blank($this->data['term'] ?? null)) {
            return collect();
        }

        return app(ReportCatalog::class)->rows($this->report(), $this->params(), 500);
    }

    public function csvUrl(): string
    {
        return route('reports.csv', ['report' => $this->report(), ...array_filter($this->params(), fn ($value) => filled($value))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function params(): array
    {
        return array_intersect_key($this->data ?? [], array_flip(ReportCatalog::REPORTS[$this->report()]['params']));
    }
}
