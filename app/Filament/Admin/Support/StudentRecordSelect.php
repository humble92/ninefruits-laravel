<?php

namespace App\Filament\Admin\Support;

use App\Models\Person\StudentRecord;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;

/**
 * A searchable select of the student records the user can see, by student number.
 */
final class StudentRecordSelect
{
    public static function make(string $name = 'student_record_id'): Select
    {
        return Select::make($name)->label(__('people.fields.student_number'))->required()->searchable()
            ->getSearchResultsUsing(fn (string $search) => StudentRecord::query()->visibleTo(self::user())->where('student_number', 'ilike', $search.'%')
                ->with('person')->orderBy('student_number')->limit(20)->get()
                ->mapWithKeys(fn (StudentRecord $record) => [$record->id => $record->student_number.' '.$record->person->displayName()]))
            ->getOptionLabelUsing(fn ($value) => StudentRecord::query()->find($value)?->student_number);
    }

    private static function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
