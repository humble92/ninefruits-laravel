<?php

namespace App\Filament\Admin\Resources\Lms\CourseLinks;

use App\Domain\Lms\Actions\SyncSection;
use App\Domain\Lms\Enums\LinkStatus;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Lms\CourseLinks\Pages\ListLmsCourseLinks;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Lms\LmsCourseLink;
use App\Models\Lms\LmsPlatform;
use App\Models\Term\Term;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Which sections are linked to which platform courses, and their last sync.
 */
class LmsCourseLinkResource extends Resource
{
    protected static ?string $model = LmsCourseLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Lms;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'lms/course-links';

    public static function getModelLabel(): string
    {
        return __('lms.resources.course_links.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lms.resources.course_links.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['platform', 'section.course', 'section.term']))
            ->columns([
                TextColumn::make('platform.code')->label(__('lms.resources.platforms.label')),
                TextColumn::make('section.term.code')->label(__('reference.resources.terms.label')),
                TextColumn::make('section')->label(__('offering.resources.sections.label'))->state(fn (LmsCourseLink $record) => $record->section->label())->searchable(query: fn (Builder $query, string $search) => $query->whereHas('section.course', fn (Builder $c) => $c->where('code', 'ilike', "%{$search}%"))),
                TextColumn::make('external_course_id')->label(__('lms.fields.external_course_id'))->placeholder('-'),
                TextColumn::make('external_url')->label(__('lms.fields.external_url'))->placeholder('-')->url(fn (LmsCourseLink $record) => $record->external_url, true)->limit(40),
                TextColumn::make('status')->label(__('registration.fields.status'))->badge()->formatStateUsing(fn (LinkStatus $state) => $state->label())
                    ->color(fn (LinkStatus $state) => match ($state) {
                        LinkStatus::Linked => 'success', LinkStatus::Failed => 'danger', LinkStatus::Pending => 'gray'
                    }),
                TextColumn::make('last_synced_at')->label(__('lms.fields.last_synced_at'))->dateTime()->placeholder('-')->sortable(),
                TextColumn::make('last_error')->label(__('lms.fields.last_error'))->placeholder('-')->limit(40)->wrap(),
            ])
            ->defaultSort('last_synced_at', 'desc')
            ->filters([
                SelectFilter::make('lms_platform_id')->label(__('lms.resources.platforms.label'))->options(fn () => LmsPlatform::query()->orderBy('code')->pluck('code', 'id')),
                SelectFilter::make('status')->label(__('registration.fields.status'))->options(collect(LinkStatus::cases())->mapWithKeys(fn (LinkStatus $s) => [$s->value => $s->label()])),
                SelectFilter::make('term')->label(__('reference.resources.terms.label'))->options(fn () => Term::query()->orderByDesc('start_date')->pluck('code', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(filled($data['value'] ?? null), fn (Builder $q) => $q->whereHas('section', fn (Builder $s) => $s->where('term_id', $data['value'])))),
            ])
            ->recordActions([
                Action::make('resync')->label(__('lms.actions.resync'))->icon(Heroicon::OutlinedArrowPath)->color('gray')->requiresConfirmation()
                    ->action(function (LmsCourseLink $record, SyncSection $sync) {
                        $summary = ShowsActionErrors::run(fn () => $sync->handle($record->platform, $record->section));
                        Notification::make()->success()->title(__('lms.notifications.synced', ['members' => $summary['members']]))->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLmsCourseLinks::route('/')];
    }
}
