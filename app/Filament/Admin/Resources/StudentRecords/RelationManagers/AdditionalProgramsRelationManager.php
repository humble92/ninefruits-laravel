<?php

namespace App\Filament\Admin\Resources\StudentRecords\RelationManagers;

use App\Domain\Person\Actions\EndAdditionalProgram;
use App\Domain\Person\Enums\AdditionalProgramRole;
use App\Models\Person\StudentRecordProgram;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Second majors and minors of the record. They are added by approving an application;
 * ending one needs a reason.
 */
class AdditionalProgramsRelationManager extends RelationManager
{
    protected static string $relationship = 'additionalPrograms';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('people.resources.additional_programs.plural_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('program'))
            ->columns([
                TextColumn::make('program.name')->label(__('reference.fields.programs')),
                TextColumn::make('role')->label(__('people.fields.program_role'))->formatStateUsing(fn (AdditionalProgramRole $state) => $state->label())->badge(),
                TextColumn::make('start_date')->label(__('reference.fields.start_date'))->date(),
                TextColumn::make('end_date')->label(__('reference.fields.end_date'))->date()->placeholder('-'),
            ])
            ->recordActions([
                Action::make('end')->label(__('people.actions.end_additional_program'))->icon(Heroicon::OutlinedStopCircle)->color('danger')
                    ->visible(fn (StudentRecordProgram $record) => $record->end_date === null)
                    ->schema([
                        DatePicker::make('end_date')->label(__('reference.fields.end_date'))->default(now())->required(),
                        Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
                    ])
                    ->action(function (StudentRecordProgram $record, array $data, EndAdditionalProgram $end) {
                        /** @var User $user */
                        $user = Auth::user();
                        $end->handle($record, CarbonImmutable::parse($data['end_date']), $data['reason'], $user);
                        Notification::make()->success()->title(__('people.notifications.additional_program_ended'))->send();
                    }),
            ]);
    }
}
