<?php

use App\Support\Database\DatabaseSessionContext;
use Illuminate\Support\Facades\DB;

function i18n(array|string|null $value, ?string $locale = null, string $fallback = 'en'): ?string
{
    $json = is_array($value) ? json_encode($value) : $value;

    return $locale === null
        ? DB::scalar('SELECT util.i18n(?::jsonb)', [$json])
        : DB::scalar('SELECT util.i18n(?::jsonb, ?, ?)', [$json, $locale, $fallback]);
}

it('returns the value for the requested locale', function () {
    expect(i18n(['en' => 'Data Structures', 'ko' => '데이터구조'], 'ko'))->toBe('데이터구조');
});

it('falls back to the base language, then the fallback locale, then the first value', function () {
    $value = ['en' => 'Data Structures', 'zh' => '数据结构'];

    expect(i18n($value, 'zh_CN'))->toBe('数据结构')
        ->and(i18n($value, 'fr'))->toBe('Data Structures')
        ->and(i18n(['ko' => '데이터구조'], 'fr'))->toBe('데이터구조')
        ->and(i18n(['en' => '', 'ko' => '데이터구조'], 'en'))->toBe('데이터구조');
});

it('returns null for non-object values', function () {
    expect(i18n(null, 'en'))->toBeNull()
        ->and(i18n('"plain"', 'en'))->toBeNull();
});

it('reads the locale from the session settings', function () {
    app(DatabaseSessionContext::class)->setLocale('ko', 'en');

    expect(i18n(['en' => 'Grade', 'ko' => '성적']))->toBe('성적');
});

it('joins all translations for search', function () {
    expect(DB::scalar("SELECT util.i18n_search_text('{\"en\":\"Data\",\"ko\":\"데이터\"}'::jsonb)"))
        ->toBe('Data 데이터');
});

it('supports ICU collations for locale aware ordering', function () {
    $names = collect(DB::select(<<<'SQL'
        SELECT name FROM (VALUES ('하'), ('가'), ('나')) AS t(name) ORDER BY name COLLATE "ko-x-icu"
        SQL))->pluck('name')->all();

    expect($names)->toBe(['가', '나', '하']);
});
