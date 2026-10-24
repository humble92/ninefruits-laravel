<?php

use App\Domain\Institution\Services\SupportedLocales;
use App\Domain\Institution\Settings\InstitutionSettings;
use App\Models\Security\Role;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

it('uses the browser language when supported', function () {
    $this->withHeader('Accept-Language', 'zh-CN,zh;q=0.9')->get('/login')->assertSee('登录');
    $this->withHeader('Accept-Language', 'ko-KR,ko;q=0.9')->get('/login')->assertSee('로그인');
    $this->withHeader('Accept-Language', 'fr-FR')->get('/login')->assertSee('Sign in');
});

it('ships Lao as a supported language', function () {
    // Added 2026-10-23 for the first Lao customer: lang/lo plus the settings migration default.
    expect(app(SupportedLocales::class)->all())->toContain('lo');
    $this->withHeader('Accept-Language', 'lo-LA,lo;q=0.9')->get('/login')->assertSee(__('authentication.login.title', [], 'lo'));
});

it('prefers the language chosen by the user', function () {
    $user = User::factory()->create(['locale' => 'ko']);

    $this->actingAs($user)->withHeader('Accept-Language', 'en')->get('/portal')->assertSee('환영합니다');
});

it('switches the language and stores it for signed-in users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/locale', ['locale' => 'zh_CN'])->assertRedirect();

    expect($user->fresh()->locale)->toBe('zh_CN');
});

it('rejects languages the institution does not offer', function () {
    $settings = app(InstitutionSettings::class);
    $settings->supported_locales = ['en', 'ko'];
    $settings->save();

    $this->post('/locale', ['locale' => 'zh_CN'])->assertSessionHasErrors('locale');
    $this->post('/locale', ['locale' => 'xx'])->assertSessionHasErrors('locale');
});

it('passes the locale and user to the database session and clears them after the request', function () {
    Route::middleware('web')->get('/_test/database-context', fn () => app(DatabaseSessionContext::class)->current());
    $user = User::factory()->create();

    $this->actingAs($user)->withHeader('Accept-Language', 'ko')->get('/_test/database-context')
        ->assertJson(['app.locale' => 'ko', 'app.user_id' => (string) $user->id, 'app.actor' => "user:{$user->id}"]);

    expect(app(DatabaseSessionContext::class)->current())
        ->{'app.locale'}->toBe('')
        ->{'app.user_id'}->toBe('');
});

it('returns translatable attributes in the current locale with fallback', function () {
    $role = Role::factory()->create(['display_name' => ['en' => 'Registrar', 'ko' => '학적 담당']]);

    app()->setLocale('ko');
    expect($role->display_name)->toBe('학적 담당');

    app()->setLocale('zh_CN');
    expect($role->display_name)->toBe('Registrar');
});

it('keeps translatable columns as json objects in the database', function () {
    $role = Role::factory()->create();

    expect(fn () => DB::transaction(
        fn () => DB::table('roles')->where('id', $role->id)->update(['display_name' => '"plain text"'])
    ))->toThrow(QueryException::class, 'roles_display_name_object_check');
});
