<?php

namespace App\Http\Controllers\Attendance;

use App\Domain\Attendance\Services\AttendanceSummary;
use App\Domain\Attendance\Services\SectionRoster;
use App\Domain\Institution\Settings\InstitutionSettings;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Attendance\ClassMeeting;
use App\Models\Offering\Section;
use App\Support\Pdf\PdfOptions;
use App\Support\Pdf\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The attendance sheet PDF (legacy prt_chuxi): students down, meetings across, the
 * recorded statuses and totals; empty cells for meetings still to come.
 */
class AttendanceSheetController
{
    public function __invoke(Request $request, Section $section, SectionRoster $roster, AttendanceSummary $summary, PdfRenderer $pdf, InstitutionSettings $institution): Response
    {
        SectionRoster::authorizeView($section, $request->user());
        $section->load(['course', 'version', 'term', 'instructors.person']);
        $enrollments = $roster->enrollments($section);
        $meetings = ClassMeeting::query()->where('section_id', $section->id)->scheduled()->orderBy('meeting_date')->orderBy('start_time')->get();
        $records = AttendanceRecord::query()->whereIn('class_meeting_id', $meetings->pluck('id'))->get()->groupBy('enrollment_id')->map(fn ($group) => $group->keyBy('class_meeting_id'));

        $content = $pdf->renderView('pdf.attendance-sheet', [
            'institution' => $institution->name[app()->getLocale()] ?? reset($institution->name) ?: (string) config('app.name'),
            'section' => $section,
            'enrollments' => $enrollments,
            'meetings' => $meetings,
            'records' => $records,
            'summary' => $summary->forSection($section, $enrollments),
        ], new PdfOptions(landscape: true));

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$section->label().'-attendance.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
