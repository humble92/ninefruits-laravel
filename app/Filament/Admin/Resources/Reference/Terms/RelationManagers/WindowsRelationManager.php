<?php

namespace App\Filament\Admin\Resources\Reference\Terms\RelationManagers;

use App\Domain\Term\Enums\TermWindowType;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TargetCriteriaFields;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Term\TermSession;
use App\Models\Term\TermWindow;
use App\Support\Targeting\TargetCriteria;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Business windows of a term. Several windows of one type with different eligibility make
 * a staggered opening (e.g. fourth-year students first). Windows about courses belong to a
 * session.
 */
class WindowsRelationManager extends RelationManager
{
    protected static string $relationship = 'windows';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('reference.resources.term_windows.plural_label');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('window_type')->label(__('reference.fields.window_type'))
                ->options(collect(TermWindowType::cases())->mapWithKeys(fn (TermWindowType $type) => [$type->value => $type->label()]))
                ->required()->live(),
            Select::make('term_session_id')->label(__('reference.fields.term_session'))
                ->options(fn () => TermSession::query()->where('term_id', $this->getOwnerRecord()->getKey())->get()->pluck('name', 'id'))
                ->default(fn () => TermSession::query()->where('term_id', $this->getOwnerRecord()->getKey())->where('is_full_term', true)->value('id'))
                ->visible(fn (Get $get) => TermWindowType::tryFrom((string) $get('window_type'))?->isCourseScoped() ?? false)
                ->required(fn (Get $get) => TermWindowType::tryFrom((string) $get('window_type'))?->isCourseScoped() ?? false)
                ->helperText(__('reference.help.term_session')),
            TranslatableFields::make('name', __('reference.fields.window_name'), required: false),
            DateTimePicker::make('opens_at')->label(__('reference.fields.opens_at'))->seconds(false)->required(),
            DateTimePicker::make('closes_at')->label(__('reference.fields.closes_at'))->seconds(false)->required()->after('opens_at'),
            TargetCriteriaFields::make('eligibility', __('reference.fields.eligibility'), __('reference.help.eligibility')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('session'))
            ->columns([
                TextColumn::make('window_type')->label(__('reference.fields.window_type'))
                    ->formatStateUsing(fn (TermWindowType $state) => $state->label())->badge(),
                TextColumn::make('session.name')->label(__('reference.fields.term_session'))->placeholder('-'),
                TextColumn::make('name')->label(__('reference.fields.window_name'))->placeholder('-'),
                TextColumn::make('opens_at')->label(__('reference.fields.opens_at'))->dateTime()->sortable(),
                TextColumn::make('closes_at')->label(__('reference.fields.closes_at'))->dateTime(),
                TextColumn::make('eligibility')->label(__('reference.fields.eligibility'))
                    ->state(fn (TermWindow $record) => $record->eligibility->isEveryone() ? __('reference.eligibility.everyone') : __('reference.eligibility.restricted')),
            ])
            ->defaultSort('opens_at')
            ->headerActions([
                CreateAction::make()->mutateDataUsing(fn (array $data) => self::normalizeEligibility($data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, TermWindow $record) => [
                        ...$data,
                        'name' => $record->getTranslations('name'),
                        'eligibility' => $record->eligibility->toArray(),
                    ])
                    ->mutateDataUsing(fn (array $data) => self::normalizeEligibility($data)),
                SafeDeleteAction::make(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeEligibility(array $data): array
    {
        $data['eligibility'] = TargetCriteria::fromArray($data['eligibility'] ?? null);

        return $data;
    }
}
