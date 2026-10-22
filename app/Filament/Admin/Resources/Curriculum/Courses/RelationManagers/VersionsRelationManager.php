<?php

namespace App\Filament\Admin\Resources\Curriculum\Courses\RelationManagers;

use App\Domain\Curriculum\Actions\ReviseCourse;
use App\Domain\Curriculum\Enums\GradingBasis;
use App\Filament\Admin\Resources\Curriculum\Courses\CourseResource;
use App\Filament\Admin\Support\SafeDeleteAction;
use App\Filament\Admin\Support\TranslatableFormData;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseVersion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Versions of a course. "Revise from" is the normal way to change a course: it ends the
 * current version and starts a new one, so earlier sections keep their version.
 */
class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('curriculum.resources.course_versions.plural_label');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            ...CourseResource::versionFields(fn () => $this->getOwnerRecord()->getKey()),
            CourseResource::requisitesField(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('category'))
            ->columns([
                TextColumn::make('effective_start_date')->label(__('reference.fields.effective_start_date'))->date(),
                TextColumn::make('effective_end_date')->label(__('reference.fields.effective_end_date'))->date()->placeholder('-'),
                TextColumn::make('title')->label(__('curriculum.fields.title')),
                TextColumn::make('credits')->label(__('curriculum.fields.credits')),
                TextColumn::make('category.name')->label(__('curriculum.fields.category'))->placeholder('-'),
                TextColumn::make('grading_basis')->label(__('curriculum.fields.grading_basis'))->formatStateUsing(fn (GradingBasis $state) => $state->label()),
                TextColumn::make('requisites_count')->counts('requisites')->label(__('curriculum.fields.requisites')),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('revise')->label(__('curriculum.actions.revise'))->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                    ->authorize(fn () => $this->canCreate())
                    ->visible(fn (CourseVersion $record) => $record->effective_end_date === null)
                    ->modalDescription(__('curriculum.help.revise'))
                    ->fillForm(fn (CourseVersion $record) => TranslatableFormData::fill([...$record->attributesToArray(), 'effective_from' => null], $record))
                    ->schema(fn (Schema $schema) => $schema->columns(3)->components([
                        DatePicker::make('effective_from')->label(__('curriculum.fields.effective_from'))->required()->after(fn (CourseVersion $record) => $record->effective_start_date->toDateString()),
                        ...array_slice(CourseResource::versionFields(fn () => null), 2),
                        Textarea::make('reason')->label(__('common.reason'))->maxLength(1000)->columnSpanFull(),
                    ]))
                    ->action(function (CourseVersion $record, array $data, ReviseCourse $revise) {
                        /** @var Course $course */
                        $course = $this->getOwnerRecord();
                        /** @var User $user */
                        $user = Auth::user();
                        $revise->handle($course, CarbonImmutable::parse($data['effective_from']), $data, $user, $data['reason'] ?? null);
                        Notification::make()->success()->title(__('curriculum.notifications.revised'))->send();
                    }),
                EditAction::make()->mutateRecordDataUsing(fn (array $data, CourseVersion $record) => TranslatableFormData::fill($data, $record)),
                SafeDeleteAction::make(),
            ]);
    }
}
