<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Domain\Security\Actions\AssignRole;
use App\Domain\Security\Actions\RevokeRoleAssignment;
use App\Domain\Security\Enums\ScopeType;
use App\Models\Security\Role;
use App\Models\Security\RoleAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Role assignments of a user. Assignments are never edited: they are ended (revoked) and
 * new ones are created, so the history stays readable.
 */
class RoleAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'roleAssignments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.role_assignments.title');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['role', 'assignedBy']))
            ->columns([
                TextColumn::make('role.display_name')->label(__('admin.role_assignments.fields.role')),
                TextColumn::make('scope_type')
                    ->label(__('admin.role_assignments.fields.scope'))
                    ->formatStateUsing(fn (RoleAssignment $record) => $record->scope_type === ScopeType::Global
                        ? $record->scope_type->label()
                        : $record->scope_type->label().' #'.$record->scope_id),
                TextColumn::make('starts_at')->label(__('admin.role_assignments.fields.starts_at'))->dateTime()->placeholder('-'),
                TextColumn::make('ends_at')->label(__('admin.role_assignments.fields.ends_at'))->dateTime()->placeholder('-'),
                IconColumn::make('active')
                    ->label(__('admin.role_assignments.fields.active'))
                    ->state(fn (RoleAssignment $record) => $record->isActive())
                    ->boolean(),
                TextColumn::make('reason')->label(__('common.reason'))->limit(40)->placeholder('-'),
                TextColumn::make('assignedBy.name')->label(__('admin.role_assignments.fields.assigned_by'))->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Action::make('assign')
                    ->label(__('admin.role_assignments.actions.assign'))
                    ->authorize(fn () => Gate::allows('create', RoleAssignment::class))
                    ->schema([
                        Select::make('role_id')
                            ->label(__('admin.role_assignments.fields.role'))
                            ->options(fn () => Role::query()->assignable()->get()->mapWithKeys(fn (Role $role) => [$role->id => $role->display_name]))
                            ->required(),
                        Select::make('scope_type')
                            ->label(__('admin.role_assignments.fields.scope'))
                            ->options(collect(ScopeType::cases())->mapWithKeys(fn (ScopeType $type) => [$type->value => $type->label()]))
                            ->default(ScopeType::Global->value)
                            ->required()
                            ->live(),
                        // Becomes an org unit / program picker once those exist (Phase 1).
                        TextInput::make('scope_id')
                            ->label(__('admin.role_assignments.fields.scope_id'))
                            ->integer()
                            ->visible(fn (Get $get) => $get('scope_type') !== ScopeType::Global->value)
                            ->required(fn (Get $get) => $get('scope_type') !== ScopeType::Global->value),
                        DateTimePicker::make('starts_at')->label(__('admin.role_assignments.fields.starts_at')),
                        DateTimePicker::make('ends_at')->label(__('admin.role_assignments.fields.ends_at'))->after('starts_at'),
                        Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
                    ])
                    ->action(function (array $data, AssignRole $assignRole) {
                        /** @var User $owner */
                        $owner = $this->getOwnerRecord();
                        /** @var User $actor */
                        $actor = Auth::user();
                        $scopeType = ScopeType::from($data['scope_type']);

                        $assignRole->handle(
                            $owner,
                            Role::query()->findOrFail($data['role_id']),
                            $data['reason'],
                            $scopeType,
                            $scopeType === ScopeType::Global ? null : (int) $data['scope_id'],
                            filled($data['starts_at'] ?? null) ? CarbonImmutable::parse($data['starts_at']) : null,
                            filled($data['ends_at'] ?? null) ? CarbonImmutable::parse($data['ends_at']) : null,
                            $actor,
                        );

                        Notification::make()->success()->title(__('admin.role_assignments.notifications.assigned'))->send();
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label(__('admin.role_assignments.actions.revoke'))
                    ->color('danger')
                    ->authorize(fn (RoleAssignment $record) => Gate::allows('revoke', $record))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000),
                    ])
                    ->action(function (RoleAssignment $record, array $data, RevokeRoleAssignment $revoke) {
                        /** @var User $actor */
                        $actor = Auth::user();
                        $revoke->handle($record, $data['reason'], $actor);

                        Notification::make()->success()->title(__('admin.role_assignments.notifications.revoked'))->send();
                    }),
            ]);
    }
}
