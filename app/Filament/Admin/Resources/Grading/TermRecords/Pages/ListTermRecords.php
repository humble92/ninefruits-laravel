<?php

namespace App\Filament\Admin\Resources\Grading\TermRecords\Pages;

use App\Domain\Grading\Actions\CloseTerm;
use App\Domain\Grading\Actions\RecalculateTermRecords;
use App\Domain\Security\Enums\Permission;
use App\Domain\Term\Enums\TermStatus;
use App\Filament\Admin\Resources\Grading\TermRecords\TermRecordResource;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Offering\Section;
use App\Models\Term\Term;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ListTermRecords extends ListRecords
{
    protected static string $resource = TermRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('closeTerm')->label(__('grading.actions.close_term'))->icon(Heroicon::OutlinedLockClosed)
                ->authorize(fn () => $this->user()->can(Permission::GradesManage->value))
                ->modalDescription(__('grading.help.close_term'))
                ->schema([
                    Select::make('term_id')->label(__('reference.resources.terms.label'))->required()
                        ->options(fn () => Term::query()->where('status', TermStatus::Published)->orderByDesc('start_date')->get()
                            ->mapWithKeys(fn (Term $term) => [$term->id => $term->name.' ('.$term->code.')'])),
                    Toggle::make('dry_run')->label(__('offering.fields.dry_run'))->default(true)->helperText(__('grading.help.close_dry_run')),
                    Textarea::make('reason')->label(__('common.reason'))->maxLength(1000),
                ])
                ->action(function (array $data, CloseTerm $close) {
                    $term = Term::query()->with('type')->findOrFail($data['term_id']);
                    $preflight = $close->preflight($term);

                    if ($data['dry_run'] || ! $preflight->isReady()) {
                        $lines = $preflight->openSections->take(30)
                            ->map(fn (Section $section) => e(__('grading.notifications.open_section', ['section' => $section->label(), 'count' => $section->getAttribute('unfinished_count')])));
                        Notification::make()->persistent()->{$preflight->isReady() ? 'success' : 'warning'}()
                            ->title(__($preflight->isReady() ? 'grading.notifications.close_ready' : 'grading.notifications.close_blocked', ['students' => $preflight->students, 'sections' => $preflight->openSections->count()]))
                            ->body(new HtmlString($lines->implode('<br>')))->send();

                        return;
                    }

                    ShowsActionErrors::run(fn () => $close->close($term, $this->user(), $data['reason'] ?: null));
                    Notification::make()->success()->title(__('grading.notifications.close_started'))->send();
                }),
            Action::make('recalculate')->label(__('grading.actions.recalculate'))->icon(Heroicon::OutlinedArrowPath)->color('gray')
                ->authorize(fn () => $this->user()->can(Permission::GradesManage->value))
                ->modalDescription(__('grading.help.recalculate'))
                ->schema([
                    Select::make('term_id')->label(__('reference.resources.terms.label'))->required()
                        ->options(fn () => Term::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name.' ('.$term->code.')'])),
                    Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
                ])
                ->action(fn (array $data, RecalculateTermRecords $recalculate) => ShowsActionErrors::run(fn () => Notification::make()->success()
                    ->title(__('grading.notifications.recalculated', ['count' => $recalculate->handle(Term::query()->findOrFail($data['term_id']), null, $data['reason'], $this->user())]))
                    ->send())),
        ];
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
