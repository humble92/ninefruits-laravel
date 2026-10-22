<x-layouts.guest :title="__('admissions.register.title')">
    <h1 class="mb-2 text-xl font-semibold">{{ __('admissions.register.title') }}</h1>
    <p class="mb-6 text-sm text-zinc-600">{{ __('admissions.register.instructions') }}</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="family_name" class="block text-sm font-medium">{{ __('people.fields.family_name') }}</label>
                <input id="family_name" name="family_name" type="text" value="{{ old('family_name') }}" required autocomplete="family-name"
                       class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                @error('family_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="given_name" class="block text-sm font-medium">{{ __('people.fields.given_name') }}</label>
                <input id="given_name" name="given_name" type="text" value="{{ old('given_name') }}" autocomplete="given-name"
                       class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                @error('given_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-xs text-zinc-500">{{ __('people.help.romanized') }}</p>
        <div>
            <label for="email" class="block text-sm font-medium">{{ __('authentication.fields.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                   class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
            <p class="mt-1 text-xs text-zinc-500">{{ __('admissions.register.email_help') }}</p>
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium">{{ __('authentication.fields.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium">{{ __('authentication.fields.password_confirmation') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
        </div>
        <button type="submit" class="w-full rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('admissions.register.submit') }}</button>
    </form>

    <p class="mt-6 text-sm text-zinc-600">
        {{ __('admissions.register.have_account') }}
        <a href="{{ route('login') }}" class="underline">{{ __('authentication.login.submit') }}</a>
    </p>
</x-layouts.guest>
