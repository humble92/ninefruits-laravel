<?php

use App\Domain\Security\Actions\AssignRole;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;
use App\Models\Curriculum\CourseCategory;
use App\Models\Grading\GradeScale;
use App\Models\Institution\Country;
use App\Models\Institution\Language;
use App\Models\Institution\LookupType;
use App\Models\Organization\OrgUnit;
use App\Models\Security\Role;
use App\Models\Term\Term;
use App\Models\Term\TermType;
use App\Models\User;
use Database\Seeders\Essential\EssentialSeeder;
use Database\Seeders\Profiles\KoreanUniversityProfileSeeder;

it('seeds ISO countries and languages with names in every installed language', function () {
    $this->seed(EssentialSeeder::class);

    $korea = Country::query()->where('code', 'KR')->sole();

    expect(Country::query()->count())->toBe(249)
        ->and($korea->alpha3_code)->toBe('KOR')
        ->and($korea->numeric_code)->toBe('410')
        ->and($korea->getTranslations('name'))->toHaveKeys(['en', 'ko', 'zh_CN'])
        ->and(Language::query()->where('code', 'ko')->sole()->getTranslation('name', 'zh_CN'))->toBe('韩语')
        ->and(LookupType::query()->where('is_system', true)->pluck('code')->sort()->values()->all())->toBe(['admission_type', 'ethnicity', 'room_type']);
});

it('keeps administrator edits and only adds missing translations when run again', function () {
    $this->seed(EssentialSeeder::class);

    $korea = Country::query()->where('code', 'KR')->sole();
    $korea->setAttribute('name', ['en' => 'South Korea']);
    $korea->save();

    $this->seed(EssentialSeeder::class);

    expect(Country::query()->count())->toBe(249)
        ->and($korea->fresh()->getTranslation('name', 'en'))->toBe('South Korea')
        ->and($korea->fresh()->getTranslation('name', 'ko', false))->toBe('대한민국');
});

it('applies the Korean university profile idempotently', function () {
    $this->seed([EssentialSeeder::class, KoreanUniversityProfileSeeder::class, KoreanUniversityProfileSeeder::class]);

    $scale = GradeScale::query()->with('items')->where('code', 'KR45')->sole();

    expect(TermType::query()->pluck('code')->sort()->values()->all())->toBe(['fall', 'spring', 'summer', 'winter'])
        ->and($scale->is_default)->toBeTrue()
        ->and($scale->items->pluck('code')->all())->toBe(['A+', 'A0', 'B+', 'B0', 'C+', 'C0', 'D+', 'D0', 'F', 'P', 'NP'])
        ->and($scale->items->firstWhere('code', 'P')->counts_in_gpa)->toBeFalse()
        ->and(CourseCategory::query()->where('code', 'MAJ_REQ')->sole()->parent()->value('code'))->toBe('MAJ');
});

it('applies profiles chosen in setup:install', function () {
    $this->seed(EssentialSeeder::class);
    app(AssignRole::class)->handle(User::factory()->create(), Role::query()->where('name', 'system_administrator')->sole(), 'test');

    $this->artisan('setup:install', ['--force' => true, '--profile' => ['korean-university']])->assertSuccessful();

    expect(GradeScale::query()->where('code', 'KR45')->exists())->toBeTrue();
});

it('builds a demo calendar that matches the term resolver', function () {
    $this->seed(EssentialSeeder::class);
    $this->artisan('setup:demo', ['--force' => true])->assertSuccessful();
    $this->artisan('setup:demo', ['--force' => true])->assertSuccessful();

    $resolver = app(TermResolver::class);

    expect(Term::query()->count())->toBe(3)
        ->and(OrgUnit::query()->where('code', 'DEMO')->count())->toBe(1)
        ->and($resolver->termsOpenFor(TermWindowType::RegistrationCart))->toHaveCount(1)
        ->and($resolver->openWindows(TermWindowType::Registration)->map(fn ($window) => $window->eligibility->yearLevels)->all())->toBe([[4]]);
});
