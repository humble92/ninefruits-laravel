<x-layouts.guest :title="__('authentication.confirm_password.title')">
    <h1 class="mb-2 text-xl font-semibold">{{ __('authentication.confirm_password.title') }}</h1>
    <p class="mb-6 text-sm text-zinc-600">{{ __('authentication.confirm_password.instructions') }}</p>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="password" class="block text-sm font-medium">{{ __('authentication.fields.password') }}</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="current-password"
                   class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="w-full rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('authentication.confirm_password.submit') }}</button>
    </form>
</x-layouts.guest>
