<?php

namespace App\Filament\Admin\Resources\Curriculum\Courses\RelationManagers;

use App\Filament\Admin\Resources\Curriculum\Courses\CourseResource;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFields;
use App\Filament\Admin\Support\TranslatableFormData;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseEquivalency;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Substitutions: which course counts for this one, and in which period.
 */
class EquivalenciesRelationManager extends RelationManager
{
    protected static string $relationship = 'equivalencies';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('curriculum.resources.course_equivalencies.plural_label');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('equivalent_course_id')->label(__('curriculum.fields.equivalent_course'))->searchable()->required()
                ->getSearchResultsUsing(fn (string $search) => array_diff_key(CourseResource::courseSearch($search), [$this->getOwnerRecord()->getKey() => true]))
                ->getOptionLabelUsing(fn ($value) => Course::query()->find($value)?->code),
            Toggle::make('is_mutual')->label(__('curriculum.fields.is_mutual'))->default(true)->helperText(__('curriculum.help.is_mutual'))->inline(false),
            DatePicker::make('effective_start_date')->label(__('reference.fields.effective_start_date'))->required(),
            DatePicker::make('effective_end_date')->label(__('reference.fields.effective_end_date'))->afterOrEqual('effective_start_date'),
            TranslatableFields::make('note', __('curriculum.fields.note'), required: false)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('equivalentCourse'))
            ->columns([
                TextColumn::make('equivalentCourse.code')->label(__('curriculum.fields.equivalent_course')),
                IconColumn::make('is_mutual')->label(__('curriculum.fields.is_mutual'))->boolean(),
                TextColumn::make('effective_start_date')->label(__('reference.fields.effective_start_date'))->date(),
                TextColumn::make('effective_end_date')->label(__('reference.fields.effective_end_date'))->date()->placeholder('-'),
                TextColumn::make('note')->label(__('curriculum.fields.note'))->placeholder('-'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, CourseEquivalency $record) => TranslatableFormData::fill($data, $record)),
                SafeDeleteAction::make(),
            ]);
    }
}
