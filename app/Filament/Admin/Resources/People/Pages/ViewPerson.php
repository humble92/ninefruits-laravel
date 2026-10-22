<?php

namespace App\Filament\Admin\Resources\People\Pages;

use App\Domain\Person\Actions\InvitePerson;
use App\Domain\Person\Actions\MergePeople;
use App\Domain\Security\Actions\RevealSensitiveValue;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Resources\People\PersonResource;
use App\Models\Person\Person;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ViewPerson extends ViewRecord
{
    protected static string $resource = PersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ActionGroup::make([
                $this->revealAction('national_id'),
                $this->revealAction('passport_number'),
                Action::make('invite')
                    ->label(__('people.actions.invite'))
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->visible(fn (Person $record) => $record->user === null && $record->merged_into_person_id === null)
                    ->authorize(fn (Person $record) => Gate::allows('invite', $record))
                    ->fillForm(fn (Person $record) => ['email' => $record->email])
                    ->schema([
                        TextInput::make('email')->label(__('people.fields.login_email'))->helperText(__('people.help.invitation_email'))->email()->required(),
                    ])
                    ->action(function (Person $record, array $data, InvitePerson $invite) {
                        $invite->handle($record, $data['email'], $this->actor());
                        Notification::make()->success()->title(__('people.notifications.invited'))->send();
                    }),
                Action::make('merge')
                    ->label(__('people.actions.merge'))
                    ->icon(Heroicon::OutlinedArrowsPointingIn)
                    ->color('danger')
                    ->authorize(fn (Person $record) => Gate::allows('merge', $record))
                    ->modalDescription(__('people.help.merge'))
                    ->schema([
                        Select::make('target_id')->label(__('people.fields.merge_target'))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search, Person $record) => Person::query()->visibleTo($this->actor())->notMerged()
                                ->whereKeyNot($record->id)->search($search)->limit(20)->get()
                                ->mapWithKeys(fn (Person $person) => [$person->id => $person->displayName().' ('.($person->birth_date?->toDateString() ?? '-').')']))
                            ->required(),
                        Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Person $record, array $data, MergePeople $merge) {
                        $target = $merge->handle($record, Person::query()->findOrFail($data['target_id']), $data['reason'], $this->actor());
                        Notification::make()->success()->title(__('people.notifications.merged'))->send();
                        $this->redirect(PersonResource::getUrl('view', ['record' => $target]));
                    }),
            ]),
        ];
    }

    private function revealAction(string $attribute): Action
    {
        return Action::make('reveal_'.$attribute)
            ->label(__('people.actions.reveal_'.$attribute))
            ->icon(Heroicon::OutlinedEye)
            ->visible(fn (Person $record) => $record->getAttribute($attribute.'_encrypted') !== null)
            ->authorize(fn (Person $record) => Gate::allows('viewSensitive', $record))
            ->modalDescription(__('people.help.reveal'))
            ->schema([Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(500)])
            ->action(function (Person $record, array $data, RevealSensitiveValue $reveal) use ($attribute) {
                $value = $reveal->handle($this->actor(), $record, $attribute, Permission::PeopleViewSensitive->value, $data['reason']);

                Notification::make()->info()->persistent()
                    ->title(__('people.fields.'.$attribute))
                    ->body((string) $value)
                    ->send();
            });
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
