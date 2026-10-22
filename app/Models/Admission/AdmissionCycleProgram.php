<?php

namespace App\Models\Admission;

use App\Models\Organization\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A program offered in a cycle. Capacity is shown to staff when deciding; it is not
 * enforced (decisions are a human judgement).
 *
 * @property int $id
 * @property int $admission_cycle_id
 * @property int $program_id
 * @property int|null $capacity
 * @property-read AdmissionCycle $cycle
 * @property-read Program $program
 */
class AdmissionCycleProgram extends Model
{
    protected $fillable = ['admission_cycle_id', 'program_id', 'capacity'];

    protected $attributes = ['capacity' => null];

    protected function casts(): array
    {
        return ['capacity' => 'integer'];
    }

    /**
     * @return BelongsTo<AdmissionCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AdmissionCycle::class, 'admission_cycle_id');
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
