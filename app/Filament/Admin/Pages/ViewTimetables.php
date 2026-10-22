<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Offering\Services\Timetable;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Offering\Sections\SectionResource;
use App\Models\Facility\Room;
use App\Models\Offering\SectionMeeting;
use App\Models\Person\Instructor;
use App\Models\Term\Term;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Weekly timetable of a room or an instructor in a term, for planning and checking.
 *
 * @property-read Schema $form
 */
class ViewTimetables extends Page
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Curriculum;

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'offering/timetables';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->can(Permission::SectionsView->value) || $user->can(Permission::SectionsManage->value));
    }

    public static function getNavigationLabel(): string
    {
        return __('offering.timetable.title');
    }

    public function getTitle(): string
    {
        return __('offering.timetable.title');
    }

    public function mount(): void
    {
        $this->form->fill(['term_id' => SectionResource::defaultTermId(), 'mode' => 'room']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->columns(3)->components([
            Select::make('term_id')->label(__('reference.resources.terms.label'))->live()
                ->options(fn () => Term::query()->orderByDesc('start_date')->get()->mapWithKeys(fn (Term $term) => [$term->id => $term->name.' ('.$term->code.')'])),
            Select::make('mode')->label(__('offering.timetable.mode'))->live()->selectablePlaceholder(false)
                ->options(['room' => __('offering.fields.room'), 'instructor' => __('people.resources.instructors.label')]),
            Select::make('target_id')->label(fn (Get $get) => $get('mode') === 'instructor' ? __('people.resources.instructors.label') : __('offering.fields.room'))
                ->live()->searchable()
                ->options(fn (Get $get) => $get('mode') === 'instructor'
                    ? Instructor::query()->current()->with('person')->get()->sortBy(fn (Instructor $instructor) => $instructor->person->displayName())
                        ->mapWithKeys(fn (Instructor $instructor) => [$instructor->id => $instructor->person->displayName().' ('.$instructor->employee_number.')'])
                    : Room::query()->where('is_active', true)->with('building')->get()->sortBy(fn (Room $room) => $room->building->code.$room->code)
                        ->mapWithKeys(fn (Room $room) => [$room->id => $room->building->code.'-'.$room->code])),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('form'),
            View::make('filament.admin.timetable')->viewData(fn () => ['timetable' => $this->timetable()]),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function timetable(): ?array
    {
        $termId = $this->data['term_id'] ?? null;
        $targetId = $this->data['target_id'] ?? null;

        if ($termId === null || $targetId === null) {
            return null;
        }

        $meetings = SectionMeeting::query()
            ->where('is_active', true)
            ->whereHas('section', fn (Builder $section) => $section->where('term_id', $termId)->where('status', '!=', SectionStatus::Cancelled))
            ->when(($this->data['mode'] ?? 'room') === 'room',
                fn (Builder $query) => $query->where('room_id', $targetId),
                fn (Builder $query) => $query->whereHas('section.instructors', fn (Builder $instructor) => $instructor->whereKey($targetId)))
            ->with(['section.course', 'section.version', 'section.session', 'room.building'])
            ->get();

        return app(Timetable::class)->build($meetings, fn (SectionMeeting $meeting) => [
            $meeting->section->label(),
            trim($meeting->section->version->title.' · '.Timetable::roomLabel($meeting).($meeting->section->session->is_full_term ? '' : ' · '.$meeting->section->session->code), ' ·'),
        ]);
    }
}
