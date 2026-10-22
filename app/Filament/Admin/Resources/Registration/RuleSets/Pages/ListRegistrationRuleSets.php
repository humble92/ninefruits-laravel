<?php

namespace App\Filament\Admin\Resources\Registration\RuleSets\Pages;

use App\Domain\Registration\Actions\PrepareRuleSet;
use App\Filament\Admin\Resources\Registration\RuleSets\RegistrationRuleSetResource;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Registration\RegistrationRuleSet;
use App\Models\Term\Term;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ListRegistrationRuleSets extends ListRecords
{
    protected static string $resource = RegistrationRuleSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('prepare')->label(__('registration.actions.prepare_rule_set'))->icon(Heroicon::OutlinedPlus)
                ->authorize(fn () => Gate::allows('create', RegistrationRuleSet::class))
                ->modalDescription(__('registration.help.prepare_rule_set'))
                ->schema([
                    Select::make('term_id')->label(__('reference.resources.terms.label'))->required()
                        ->options(fn () => Term::query()->whereDoesntHave('registrationRuleSet')->whereIn('status', ['planned', 'published'])
                            ->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name.' ('.$term->code.')'])),
                    Select::make('copy_from_id')->label(__('registration.fields.copy_from'))
                        ->options(fn () => RegistrationRuleSet::query()->with('term')->orderByDesc('term_id')->get()
                            ->mapWithKeys(fn (RegistrationRuleSet $set) => [$set->id => $set->term->name.' ('.$set->term->code.')']))
                        ->default(fn () => RegistrationRuleSet::query()->orderByDesc('term_id')->value('id')),
                ])
                ->action(function (array $data, PrepareRuleSet $prepare) {
                    /** @var User $user */
                    $user = Auth::user();
                    $set = ShowsActionErrors::run(fn () => $prepare->handle(
                        Term::query()->findOrFail($data['term_id']),
                        filled($data['copy_from_id'] ?? null) ? RegistrationRuleSet::query()->findOrFail($data['copy_from_id']) : null,
                        $user,
                    ));
                    $this->redirect(RegistrationRuleSetResource::getUrl('edit', ['record' => $set]));
                }),
        ];
    }
}
