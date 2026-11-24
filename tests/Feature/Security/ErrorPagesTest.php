<?php

use App\Domain\Security\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Js;

beforeEach(function () {
    config(['app.debug' => false]);

    Route::middleware('web')->group(function () {
        Route::get('/_test/abort/{code}', fn (int $code) => abort($code));
        Route::get('/admin/_test/abort/{code}', fn (int $code) => abort($code));
        Route::get('/_test/crash', fn () => throw new RuntimeException('SELECT secret_column FROM people WHERE student_number = 20261234'));
    });
});

it('shows a translated page with the status code and the request id as reference', function (int $code, string $key) {
    $response = $this->withHeader('Accept-Language', 'ko')->get("/_test/abort/{$code}")->assertStatus($code);
    $requestId = $response->headers->get('X-Request-Id');

    expect($requestId)->toBeString()->not->toBeEmpty();
    $response->assertSee(__("errors.{$key}.title", locale: 'ko'))
        ->assertSee(__("errors.{$key}.message", locale: 'ko'))
        ->assertSee(__('errors.code', ['code' => $code], 'ko'))
        ->assertSee(__('errors.reference', locale: 'ko'))
        ->assertSee($requestId)
        ->assertSee('<svg', false);
})->with([
    [403, 'forbidden'],
    [404, 'not_found'],
    [419, 'page_expired'],
    [429, 'too_many_requests'],
    [500, 'server_error'],
    [503, 'unavailable'],
]);

it('never shows exception details to the user', function () {
    $response = $this->get('/_test/crash')->assertStatus(500)
        ->assertSee(__('errors.server_error.title'))
        ->assertDontSee('secret_column')->assertDontSee('20261234')->assertDontSee('RuntimeException');
    $response->assertSee((string) $response->headers->get('X-Request-Id'));

    $json = $this->getJson('/_test/crash')->assertStatus(500);
    expect($json->json())->toBe(['message' => 'Server Error', 'request_id' => $json->headers->get('X-Request-Id')]);
});

it('adds the request id to JSON error responses', function () {
    $json = $this->getJson('/_test/abort/404')->assertNotFound();

    $json->assertJsonStructure(['message', 'request_id'])->assertJsonPath('request_id', $json->headers->get('X-Request-Id'));
});

it('links to the home that fits the user', function () {
    $this->get('/_test/abort/404')->assertSee('href="'.url('/').'"', false);

    $student = User::factory()->create();
    $this->actingAs($student)->get('/_test/abort/404')->assertSee('href="'.route('portal.dashboard').'"', false);
    $this->actingAs($student)->get('/admin/_test/abort/404')->assertSee('href="'.route('portal.dashboard').'"', false);

    $staff = User::factory()->withPermissions([Permission::AdminPanelAccess])->create();
    $this->actingAs($staff)->get('/admin/_test/abort/404')
        ->assertSee('href="'.url('/admin').'"', false)->assertSee(__('portal.navigation.admin'));

    // A server error page does not look up the user, so a broken database cannot break it.
    $this->actingAs($staff)->get('/admin/_test/abort/500')->assertSee('href="'.url('/').'"', false);
});

it('replaces the English Livewire page-expired prompt in the portal and the admin panel', function () {
    $prompt = (string) Js::from(__('errors.page_expired.title', locale: 'ko')."\n".__('errors.page_expired.reload_prompt', locale: 'ko'));

    $student = User::factory()->create(['locale' => 'ko']);
    $this->actingAs($student)->get(route('portal.security'))->assertOk()
        ->assertSee('interceptRequest', false)->assertSee($prompt, false);

    $staff = User::factory()->withPermissions([Permission::AdminPanelAccess])->create(['locale' => 'ko']);
    $this->actingAs($staff)->get('/admin')->assertOk()
        ->assertSee('interceptRequest', false)->assertSee($prompt, false);
});

it('keeps the web server error page in step with the translations', function () {
    $html = (string) file_get_contents(public_path('errors/unavailable.html'));
    preg_match('#<script type="application/json" id="messages">(.*?)</script>#s', $html, $match);
    $messages = json_decode($match[1] ?? '', true, flags: JSON_THROW_ON_ERROR);
    $locales = array_map('basename', glob(lang_path('*'), GLOB_ONLYDIR) ?: []);

    expect(array_keys($messages))->toEqualCanonicalizing($locales);
    foreach ($locales as $locale) {
        expect($messages[$locale])->toBe([
            'title' => __('errors.unavailable.title', locale: $locale),
            'message' => __('errors.unavailable.message', locale: $locale),
            'reference' => __('errors.reference', locale: $locale),
        ]);
    }

    // Self-contained (the app and its build may be down) and the reference comes from Nginx.
    expect($html)->toContain('<!--# echo var="request_id" default="" -->')
        ->not->toMatch('#(src|href)=#');
});
