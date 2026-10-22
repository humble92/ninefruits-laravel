<?php

namespace App\Domain\Offering\Actions;

use App\Models\Offering\Section;
use App\Models\Offering\Syllabus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

/**
 * The section's instructors (or staff who manage the section) write the syllabus and
 * publish it for students. Changes after publishing stay published.
 */
class UpdateSyllabus
{
    /**
     * @param  array<string, mixed>  $content
     */
    public function handle(Section $section, string $language, array $content, User $user, ?bool $publish = null): Syllabus
    {
        if (! $section->isTaughtBy($user) && ! $section->isManageableBy($user)) {
            throw new AuthorizationException;
        }

        $data = Validator::make(['language' => $language, ...Arr::only($content, ['overview', 'objectives', 'materials', 'policies', 'assessment', 'weekly_plan'])], [
            'language' => ['required', 'string', 'max:16'],
            'overview' => ['nullable', 'string', 'max:5000'],
            'objectives' => ['nullable', 'string', 'max:5000'],
            'materials' => ['nullable', 'string', 'max:5000'],
            'policies' => ['nullable', 'string', 'max:5000'],
            'assessment' => ['array', 'max:20'],
            'assessment.*.item' => ['required', 'string', 'max:255'],
            'assessment.*.weight' => ['nullable', 'numeric', 'between:0,100'],
            'weekly_plan' => ['array', 'max:30'],
            'weekly_plan.*.week' => ['required', 'integer', 'between:1,30'],
            'weekly_plan.*.topic' => ['required', 'string', 'max:500'],
        ])->validate();

        $syllabus = Syllabus::query()->firstOrNew(['section_id' => $section->id]);
        $syllabus->fill(['language' => $data['language'], 'content' => Arr::except($data, ['language'])]);
        $syllabus->updated_by_id = $user->id;

        if ($publish !== null) {
            $syllabus->published_at = $publish ? ($syllabus->published_at ?? CarbonImmutable::now()) : null;
        }

        $syllabus->save();

        return $syllabus;
    }
}
