<?php

namespace App\Domain\Registration\Services;

use App\Models\Offering\Section;
use App\Models\Offering\SectionSeatGroup;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Seats of a section (docs/porting-plan.md section 10.7). Callers hold the section row
 * lock (lock()), so seat decisions of one section happen one at a time and briefly; the
 * counters also have CHECK constraints (enrolled_count <= capacity).
 *
 * Reserved seats: a seat group that has not reached releases_at keeps its unused seats
 * for its students. A student of the group takes a group seat while one is free, anyone
 * else takes a general seat (capacity minus taken seats minus unused reserved seats).
 * After releases_at the unused seats are general seats automatically.
 */
class SeatAllocator
{
    public function lock(int $sectionId): Section
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Seats are taken inside a transaction.');
        }

        return Section::query()->lockForUpdate()->findOrFail($sectionId);
    }

    /**
     * The seat the student would take: a seat group, 'general', or null when none is free.
     */
    public function find(Section $section, StudentRecord $record, CarbonImmutable $at): SectionSeatGroup|string|null
    {
        $reserved = $section->seatGroups()->get()->filter(fn (SectionSeatGroup $group) => $group->isReservedAt($at));

        $group = $reserved->first(fn (SectionSeatGroup $group) => $group->enrolled_count < $group->capacity && $group->eligibility->matches($record));

        if ($group !== null) {
            return $group;
        }

        $unusedReserved = $reserved->sum(fn (SectionSeatGroup $group) => max(0, $group->capacity - $group->enrolled_count));

        return $section->capacity - $section->enrolled_count - $unusedReserved > 0 ? 'general' : null;
    }

    /**
     * Counters only (no updated_at): the audit trigger ignores counter-only updates.
     */
    public function take(Section $section, SectionSeatGroup|string $seat): void
    {
        Section::query()->whereKey($section->id)->toBase()->increment('enrolled_count');
        $section->enrolled_count++;

        if ($seat instanceof SectionSeatGroup) {
            SectionSeatGroup::query()->whereKey($seat->id)->toBase()->increment('enrolled_count');
            $seat->enrolled_count++;
        }
    }

    public function release(Section $section, Enrollment $enrollment): void
    {
        Section::query()->whereKey($section->id)->where('enrolled_count', '>', 0)->toBase()->decrement('enrolled_count');
        $section->enrolled_count = max(0, $section->enrolled_count - 1);

        if ($enrollment->seat_group_id !== null) {
            SectionSeatGroup::query()->whereKey($enrollment->seat_group_id)->where('enrolled_count', '>', 0)->toBase()->decrement('enrolled_count');
        }
    }

    /**
     * Free seats for display: general seats and unused reserved seats.
     *
     * @return array{general: int, reserved: int}
     */
    public function availability(Section $section, CarbonImmutable $at): array
    {
        $reserved = $section->seatGroups()->get()->filter(fn (SectionSeatGroup $group) => $group->isReservedAt($at))
            ->sum(fn (SectionSeatGroup $group) => max(0, $group->capacity - $group->enrolled_count));

        return ['general' => max(0, $section->capacity - $section->enrolled_count - $reserved), 'reserved' => $reserved];
    }
}
