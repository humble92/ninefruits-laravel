<?php

namespace App\Domain\Admission\Actions;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Admission\Settings\AdmissionSettings;
use App\Models\Admission\Application;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Drafts in a closed cycle, or untouched for the configured number of days, become
 * expired so they no longer block a new application. Runs from the scheduler.
 */
class ExpireDraftApplications
{
    public function __construct(private readonly AdmissionSettings $settings) {}

    /**
     * @return Builder<Application>
     */
    public function query(?CarbonImmutable $at = null): Builder
    {
        $at ??= CarbonImmutable::now();

        return Application::query()
            ->where('status', ApplicationStatus::Draft)
            ->where(fn (Builder $query) => $query
                ->where('updated_at', '<', $at->subDays(max(1, $this->settings->draft_expiry_days)))
                ->orWhereHas('cycle', fn (Builder $cycle) => $cycle->whereNotNull('closes_at')->where('closes_at', '<=', $at)));
    }

    public function handle(?CarbonImmutable $at = null): int
    {
        return $this->query($at)->update(['status' => ApplicationStatus::Expired, 'updated_at' => CarbonImmutable::now()]);
    }
}
