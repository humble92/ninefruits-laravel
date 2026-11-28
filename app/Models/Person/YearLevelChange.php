<?php

namespace App\Models\Person;

use App\Models\Organization\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $student_record_id
 * @property int $program_id
 * @property int $from_level
 * @property int $to_level
 * @property string $rule_name
 * @property string $basis
 * @property string $measured_value
 * @property string|null $outcome
 * @property-read StudentRecord $studentRecord
 * @property-read Program $program
 */
class YearLevelChange extends Model
{
    public $timestamps = false;

    protected $attributes = ['outcome' => null];

    protected $fillable = ['student_record_id', 'program_id', 'from_level', 'to_level', 'rule_name', 'basis', 'measured_value', 'outcome'];

    /** @return BelongsTo<StudentRecord, $this> */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
