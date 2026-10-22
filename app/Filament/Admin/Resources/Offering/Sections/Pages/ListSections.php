<?php

namespace App\Filament\Admin\Resources\Offering\Sections\Pages;

use App\Domain\Offering\Actions\CopySections;
use App\Domain\Offering\Data\SectionCopyReport;
use App\Filament\Admin\Resources\Offering\Sections\SectionResource;
use App\Models\Offering\Section;
use App\Models\Term\Term;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

class ListSections extends ListRecords
{
    protected static string $resource = SectionResource::class;

    protected function getHeaderActions(): array
    {
        $terms = fn () => Term::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name.' ('.$term->code.')']);

        return [
            CreateAction::make(),
            Action::make('copySections')->label(__('offering.actions.copy_sections'))->icon(Heroicon::OutlinedDocumentDuplicate)
                ->authorize(fn () => Gate::allows('create', Section::class))
                ->modalDescription(__('offering.help.copy_sections'))
                ->schema([
                    Select::make('from_term_id')->label(__('offering.fields.from_term'))->options($terms)->required(),
                    Select::make('to_term_id')->label(__('offering.fields.to_term'))->options($terms)->required()->different('from_term_id')
                        ->default(fn () => SectionResource::defaultTermId()),
                    Toggle::make('dry_run')->label(__('offering.fields.dry_run'))->default(true)->helperText(__('offering.help.dry_run')),
                ])
                ->action(function (array $data, CopySections $copy) {
                    /** @var User $user */
                    $user = Auth::user();
                    $report = $copy->handle(Term::query()->findOrFail($data['from_term_id']), Term::query()->findOrFail($data['to_term_id']), $user, dryRun: (bool) $data['dry_run']);

                    Notification::make()->success()->persistent()
                        ->title(__($data['dry_run'] ? 'offering.notifications.copy_preview' : 'offering.notifications.copied', $report->summary()))
                        ->body(self::reportBody($report))
                        ->send();
                }),
        ];
    }

    private static function reportBody(SectionCopyReport $report): HtmlString
    {
        $lines = [
            ...array_map(fn (string $label, string $reason) => e($label.': '.$reason), array_keys($report->skipped), $report->skipped),
            ...array_map(fn (string $label, array $notes) => e($label.': '.implode('; ', $notes)), array_keys($report->notes), $report->notes),
        ];

        return new HtmlString(implode('<br>', array_slice($lines, 0, 30)).(count($lines) > 30 ? '<br>…' : ''));
    }
}
