<?php

namespace App\Models\Person;

use App\Domain\Person\Enums\StudentStatus;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use Database\Factories\Person\StudentStatusChangeTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Kind of status change (legacy SRALTERDEF), e.g. leave of absence, return, withdrawal,
 * dismissal. Institutions may add their own kinds (e.g. military leave) that map to one of
 * the system statuses.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property StudentStatus $to_status
 * @property list<string> $from_statuses
 * @property bool $can_student_request
 * @property bool $is_system
 * @property bool $is_active
 * @property int $sort_order
 */
class StudentStatusChangeType extends Model implements Translatable
{
    /** @use HasFactory<StudentStatusChangeTypeFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['code', 'name', 'to_status', 'from_statuses', 'can_student_request', 'is_active', 'sort_order'];

    protected $attributes = ['can_student_request' => false, 'is_system' => false, 'is_active' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return [
            'to_status' => StudentStatus::class,
            'from_statuses' => 'array',
            'can_student_request' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function appliesFrom(StudentStatus $status): bool
    {
        return in_array($status->value, $this->from_statuses, true);
    }
}
