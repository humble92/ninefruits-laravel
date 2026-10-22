<x-layouts.guest :title="__('people.invitations.title')">
    <h1 class="mb-2 text-xl font-semibold">{{ __('people.invitations.title') }}</h1>

    @if ($email === null)
        <p class="text-sm text-red-700">{{ __('people.invitations.invalid') }}</p>
    @else
        <p class="mb-6 text-sm text-zinc-600">{{ __('people.invitations.instructions', ['email' => $email]) }}</p>

        <form method="POST" action="{{ route('invitations.store', $token) }}" class="space-y-4">
            @csrf
            <div>
                <label for="password" class="block text-sm font-medium">{{ __('authentication.fields.new_password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('token') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium">{{ __('authentication.fields.password_confirmation') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
            </div>
            <button type="submit" class="w-full rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('people.invitations.submit') }}</button>
        </form>
    @endif
</x-layouts.guest>
