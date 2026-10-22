<x-layouts.guest :title="__('admissions.verify_email.title')">
    <h1 class="mb-2 text-xl font-semibold">{{ __('admissions.verify_email.title') }}</h1>
    <p class="mb-6 text-sm text-zinc-600">{{ __('admissions.verify_email.instructions', ['email' => auth()->user()?->email]) }}</p>

    @if (session('status') === 'verification-link-sent')
        <p class="mb-4 rounded bg-emerald-50 px-3 py-2 text-sm text-emerald-800" role="status">{{ __('admissions.verify_email.sent') }}</p>
    @endif

    <div class="flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('admissions.verify_email.resend') }}</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-zinc-600 underline">{{ __('authentication.logout') }}</button>
        </form>
    </div>
</x-layouts.guest>
