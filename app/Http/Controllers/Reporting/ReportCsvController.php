<?php

namespace App\Http\Controllers\Reporting;

use App\Domain\Reporting\Services\ReportCatalog;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * A standard report as CSV. Exports of personal data are audited.
 */
class ReportCsvController
{
    public function __invoke(Request $request, string $report, ReportCatalog $catalog, AuditLogger $audit): Response
    {
        abort_unless((bool) $request->user()?->can(Permission::ReportsView->value), 403);
        abort_unless(array_key_exists($report, ReportCatalog::REPORTS), 404);
        $params = $request->only(['org_unit', 'status', 'year_level', 'term', 'standing', 'satisfied', 'date_from', 'date_to']);
        $audit->record('reports.exported', null, null, ['report' => $report, 'params' => $params], $request->user());

        return response($catalog->csv($report, $params), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$report.'-'.now()->format('Ymd').'.csv"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
