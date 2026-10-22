<?php

namespace App\Domain\Person\Services;

use App\Models\Person\StudentRecord;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * The student record a person works with in the portal. People with several admissions
 * (e.g. a graduate studying again) choose one; the default is the newest current record,
 * otherwise the newest record.
 */
class CurrentStudentRecord
{
    public const string SESSION_KEY = 'portal.student_record_id';

    public function __construct(private readonly Session $session) {}

    /**
     * @return Collection<int, StudentRecord>
     */
    public function all(User $user): Collection
    {
        if ($user->person_id === null) {
            return new Collection;
        }

        return StudentRecord::query()->with('program')->where('person_id', $user->person_id)->orderByDesc('admission_date')->get();
    }

    public function get(User $user): ?StudentRecord
    {
        $records = $this->all($user);
        $chosen = $records->firstWhere('id', $this->session->get(self::SESSION_KEY));

        return $chosen ?? $records->first(fn (StudentRecord $record) => $record->status->isActive()) ?? $records->first();
    }

    public function choose(User $user, int $recordId): ?StudentRecord
    {
        $record = $this->all($user)->firstWhere('id', $recordId);

        if ($record !== null) {
            $this->session->put(self::SESSION_KEY, $record->id);
        }

        return $record;
    }
}
