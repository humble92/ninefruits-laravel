<?php

namespace App\Http\Controllers\Grading;

use App\Domain\Grading\Services\GradeReport;
use App\Domain\Institution\Settings\InstitutionSettings;
use App\Domain\Person\Services\CurrentStudentRecord;
use App\Domain\Security\Enums\Permission;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * A printable grade report (unofficial transcript). The official, issued transcript is a
 * certificate (Phase 9). Students print their own unless a hold blocks grades; staff with
 * grades.view print records they can see.
 */
class TranscriptController
{
    public function own(Request $request, CurrentStudentRecord $current, GradeReport $report, InstitutionSettings $institution): View
    {
        /** @var User $user */
        $user = $request->user();
        $record = $current->get($user) ?? abort(404);
        abort_if($report->blockingHold($record) !== null, 403);

        return $this->render($record, $report, $institution);
    }

    public function show(Request $request, StudentRecord $studentRecord, GradeReport $report, InstitutionSettings $institution): View
    {
        Gate::authorize('view', $studentRecord);
        abort_unless((bool) $request->user()?->can(Permission::GradesView->value), 403);

        return $this->render($studentRecord, $report, $institution);
    }

    private function render(StudentRecord $record, GradeReport $report, InstitutionSettings $institution): View
    {
        $record->loadMissing(['person', 'program']);
        $terms = $report->terms($record);

        return view('grading.transcript', [
            'institution' => $institution->name[app()->getLocale()] ?? reset($institution->name) ?: config('app.name'),
            'record' => $record,
            'terms' => $terms,
            'superseded' => $report->superseded($record),
            'latest' => $terms->last()['record'] ?? null,
        ]);
    }
}
