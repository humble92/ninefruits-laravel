<?php

namespace App\Models\Person;

use App\Domain\Person\Enums\AdditionalProgramRole;
use App\Models\Organization\Program;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A second major or minor of a student record. The primary program is on the record itself.
 *
 * @property int $id
 * @property int $student_record_id
 * @property int $program_id
 * @property AdditionalProgramRole $role
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $end_date
 */
class StudentRecordProgram extends Model
{
    protected $fillable = ['student_record_id', 'program_id', 'role', 'start_date', 'end_date'];

    protected $attributes = ['end_date' => null];

    protected function casts(): array
    {
        return ['role' => AdditionalProgramRole::class, 'start_date' => 'immutable_date', 'end_date' => 'immutable_date'];
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
