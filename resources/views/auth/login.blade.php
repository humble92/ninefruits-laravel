@php($identifiers = app(\App\Domain\Security\Services\LoginIdentifierResolver::class))
@php($providers = app(\App\Domain\Security\Services\Sso\OidcClient::class)->enabledProviders())
<x-layouts.guest :title="__('authentication.login.title')">
    <h1 class="mb-6 text-xl font-semibold">{{ __('authentication.login.title') }}</h1>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">
                {{ $identifiers->allowsStudentNumbers() ? __('authentication.fields.email_or_student_number') : __('authentication.fields.email') }}
            </label>
            <input id="email" name="email" type="{{ $identifiers->allowsStudentNumbers() ? 'text' : 'email' }}" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">{{ __('authentication.fields.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1"> {{ __('authentication.login.remember_me') }}
        </label>

        <div class="flex items-center justify-between">
            <a href="{{ route('password.request') }}" class="text-sm text-zinc-600 underline">{{ __('authentication.login.forgot_password') }}</a>
            <button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('authentication.login.submit') }}</button>
        </div>
    </form>

    @if ($providers !== [])
        <div class="mt-6 space-y-2 border-t border-zinc-200 pt-6">
            @foreach ($providers as $provider)
                <a href="{{ route('sso.redirect', $provider->key) }}" class="block w-full rounded border border-zinc-300 px-4 py-2 text-center text-sm font-medium">
                    {{ __('authentication.login.sso', ['provider' => $provider->label]) }}
                </a>
            @endforeach
        </div>
    @endif
</x-layouts.guest>
