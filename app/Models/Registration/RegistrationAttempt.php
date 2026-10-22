<?php

namespace App\Models\Registration;

use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\AttemptOutcome;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Every registration attempt with each rule's result, for disputes ("why could I not
 * register?"). Append only.
 *
 * @property int $id
 * @property int $student_record_id
 * @property int|null $term_id
 * @property int|null $section_id
 * @property AttemptAction $action
 * @property AttemptOutcome $outcome
 * @property list<array{code: string, passed: bool, severity: string, overridden: bool, message: string|null}> $results
 * @property int|null $actor_id
 * @property string|null $request_id
 * @property CarbonImmutable $attempted_at
 * @property-read StudentRecord $studentRecord
 * @property-read Section|null $section
 * @property-read User|null $actor
 */
class RegistrationAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = ['student_record_id', 'term_id', 'section_id', 'action', 'outcome', 'results', 'actor_id', 'request_id', 'attempted_at'];

    protected function casts(): array
    {
        return ['action' => AttemptAction::class, 'outcome' => AttemptOutcome::class, 'results' => 'array', 'attempted_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<StudentRecord, $this>
     */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
