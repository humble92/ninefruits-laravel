<?php

namespace App\Http\Controllers\Attendance;

use App\Domain\Attendance\Services\SectionRoster;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Offering\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The roster as a CSV for spreadsheets, for the instructors and staff who see the section.
 * Downloading personal data is audited.
 */
class RosterCsvController
{
    public function __invoke(Request $request, Section $section, SectionRoster $roster, AuditLogger $audit): Response
    {
        SectionRoster::authorizeView($section, $request->user());
        $audit->record('attendance.roster_exported', $section, null, ['section_id' => $section->id], $request->user());

        return response($roster->csv($section), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$section->label().'-roster.csv"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
