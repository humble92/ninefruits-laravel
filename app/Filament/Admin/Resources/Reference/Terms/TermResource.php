<?php

namespace App\Filament\Admin\Resources\Reference\Terms;

use App\Domain\Term\Enums\TermStatus;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Resources\Reference\Terms\Pages\CreateTerm;
use App\Filament\Admin\Resources\Reference\Terms\Pages\EditTerm;
use App\Filament\Admin\Resources\Reference\Terms\Pages\ListTerms;
use App\Filament\Admin\Resources\Reference\Terms\RelationManagers\SessionsRelationManager;
use App\Filament\Admin\Resources\Reference\Terms\RelationManagers\WindowsRelationManager;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Term\AcademicYear;
use App\Models\Term\Term;
use App\Models\Term\TermType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Terms and their business windows. The status is changed only with the Change status
 * action (TermStatus transitions), not in the form.
 */
class TermResource extends ReferenceResource
{
    protected static ?string $model = Term::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 42;

    protected static function labelKey(): string
    {
        return 'terms';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('academic_year_id')->label(__('reference.fields.academic_year'))
                ->options(fn () => AcademicYear::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (AcademicYear $year) => [$year->id => $year->name]))
                ->required(),
            Select::make('term_type_id')->label(__('reference.fields.term_type'))
                ->options(fn () => TermType::query()->orderBy('sort_order')->get()->mapWithKeys(fn (TermType $type) => [$type->id => $type->name]))
                ->required(),
            TextInput::make('code')->label(__('reference.fields.code'))->helperText(__('reference.help.term_code'))
                ->required()->maxLength(32)->unique(ignoreRecord: true),
            TranslatableFields::make('name', __('reference.fields.name')),
            DatePicker::make('start_date')->label(__('reference.fields.instruction_start_date'))->required(),
            DatePicker::make('end_date')->label(__('reference.fields.instruction_end_date'))->required()->afterOrEqual('start_date'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['academicYear', 'type'])->withCount('windows'))
            ->columns([
                TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('reference.fields.name')),
                TextColumn::make('academicYear.name')->label(__('reference.fields.academic_year')),
                TextColumn::make('type.name')->label(__('reference.fields.term_type')),
                TextColumn::make('start_date')->label(__('reference.fields.instruction_start_date'))->date()->sortable(),
                TextColumn::make('end_date')->label(__('reference.fields.instruction_end_date'))->date(),
                TextColumn::make('status')->label(__('reference.fields.status'))
                    ->formatStateUsing(fn (TermStatus $state) => $state->label())->badge(),
                TextColumn::make('windows_count')->label(__('reference.fields.windows_count')),
            ])
            ->filters([
                SelectFilter::make('academic_year_id')->label(__('reference.fields.academic_year'))->relationship('academicYear', 'code'),
                SelectFilter::make('status')->label(__('reference.fields.status'))
                    ->options(collect(TermStatus::cases())->mapWithKeys(fn (TermStatus $status) => [$status->value => $status->label()])),
            ])
            ->defaultSort('start_date', 'desc')
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [SessionsRelationManager::class, WindowsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTerms::route('/'),
            'create' => CreateTerm::route('/create'),
            'edit' => EditTerm::route('/{record}/edit'),
        ];
    }
}
