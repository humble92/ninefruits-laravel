<?php

use App\Domain\Registration\Actions\EnrollStudent;
use App\Domain\Registration\Data\EnrollOptions;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\WaitlistStatus;
use App\Domain\Security\Enums\Permission;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\CartItem;
use App\Models\Registration\Enrollment;
use App\Models\Registration\WaitlistEntry;
use App\Models\Term\Term;
use App\Models\User;
use Database\Seeders\Demo\Support\BulkRegistrar;
use Illuminate\Database\Eloquent\Collection;

/*
 * The demo's bulk registrations leave the cart and the waitlist as EnrollStudent does:
 * one student goes through the action, the other through the bulk path, from the same start.
 */
it('clears the cart item and promotes the waiting entry of a bulk registration like EnrollStudent', function () {
    $term = Term::factory()->published()->between(now()->addMonth()->toDateString(), now()->addMonths(5)->toDateString())->create();
    $section = Section::factory()->published()->inTerm($term)->create(['capacity' => 30, 'waitlist_capacity' => 5]);
    $otherTerm = Term::factory()->published()->between(now()->addMonths(6)->toDateString(), now()->addMonths(10)->toDateString())->create();
    $elsewhere = Section::factory()->published()->inTerm($otherTerm)->create(['capacity' => 30, 'waitlist_capacity' => 5]);
    $staff = User::factory()->withPermissions([Permission::RegistrationManage])->create();
    [$single, $bulk] = StudentRecord::factory()->count(2)->create()->all();

    foreach ([$single, $bulk] as $record) {
        foreach ([$section, $elsewhere] as $target) {
            CartItem::query()->create(['student_record_id' => $record->id, 'term_id' => $target->term_id, 'section_id' => $target->id]);
            WaitlistEntry::query()->create(['section_id' => $target->id, 'student_record_id' => $record->id, 'requested_at' => now()->subHour()]);
        }
    }

    expect(app(EnrollStudent::class)->handle($single, $section, $staff, new EnrollOptions(waive: [RuleCode::Window], reason: 'Test'))->succeeded())->toBeTrue();
    app(BulkRegistrar::class)->register($term, new Collection([$bulk->fresh()]), $staff, 'Test');

    foreach ([$single, $bulk] as $record) {
        $enrollment = Enrollment::query()->active()->where('student_record_id', $record->id)->where('section_id', $section->id)->sole();
        $promoted = WaitlistEntry::query()->where('student_record_id', $record->id)->where('section_id', $section->id)->sole();
        $untouched = WaitlistEntry::query()->where('student_record_id', $record->id)->where('section_id', $elsewhere->id)->sole();

        expect(CartItem::query()->where('student_record_id', $record->id)->pluck('section_id')->all())->toBe([$elsewhere->id])
            ->and($promoted->status)->toBe(WaitlistStatus::Promoted)
            ->and($promoted->enrollment_id)->toBe($enrollment->id)
            ->and($promoted->resolved_at)->not->toBeNull()
            ->and($untouched->status)->toBe(WaitlistStatus::Waiting)
            ->and($untouched->enrollment_id)->toBeNull();
    }

    expect($section->fresh()->enrolled_count)->toBe(2);
});
