<?php

namespace App\Http\Controllers\Admission;

use App\Domain\Admission\Settings\AdmissionSettings;
use App\Models\Admission\AdmissionCycle;
use Illuminate\Contracts\View\View;

/**
 * Public list of cycles taking applications, the starting point for new applicants.
 */
class AdmissionCycleListController
{
    public function __invoke(AdmissionSettings $settings): View
    {
        $cycles = AdmissionCycle::query()->acceptingApplications()
            ->with('programs.program')
            ->orderBy('opens_at')
            ->get();

        return view('admissions.index', [
            'cycles' => $cycles,
            'registrationEnabled' => $settings->applicant_registration_enabled,
        ]);
    }
}
