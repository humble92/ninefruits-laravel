<?php

namespace App\Models\Offering;

use App\Domain\Offering\Enums\InstructorRole;
use App\Models\Person\Instructor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $section_id
 * @property int $instructor_id
 * @property InstructorRole $role
 * @property-read Section $section
 * @property-read Instructor $instructor
 */
class SectionInstructor extends Model
{
    protected $fillable = ['section_id', 'instructor_id', 'role'];

    protected $attributes = ['role' => 'primary'];

    protected function casts(): array
    {
        return ['role' => InstructorRole::class];
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<Instructor, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }
}
