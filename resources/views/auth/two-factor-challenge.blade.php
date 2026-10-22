@php($usesApp = in_array(\App\Domain\Security\Enums\MfaMethod::AuthenticatorApp, $methods, true))
@php($usesEmail = in_array(\App\Domain\Security\Enums\MfaMethod::EmailCode, $methods, true))
<x-layouts.guest :title="__('authentication.two_factor.title')">
    <h1 class="mb-6 text-xl font-semibold">{{ __('authentication.two_factor.title') }}</h1>

    @if ($usesApp)
        <p class="mb-4 text-sm text-zinc-600">{{ __('authentication.two_factor.instructions') }}</p>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="code" class="block text-sm font-medium">{{ __('authentication.two_factor.code') }}</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus
                       class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <details class="text-sm">
                <summary class="cursor-pointer text-zinc-600">{{ __('authentication.two_factor.use_recovery_code') }}</summary>
                <label for="recovery_code" class="mt-2 block font-medium">{{ __('authentication.two_factor.recovery_code') }}</label>
                <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code"
                       class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                @error('recovery_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </details>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember_device" value="1"> {{ __('authentication.two_factor.remember_device') }}
            </label>

            <button type="submit" class="w-full rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('authentication.two_factor.submit') }}</button>
        </form>
    @endif

    @if ($usesEmail)
        <div @class(['mt-6 border-t border-zinc-200 pt-6' => $usesApp])>
            <p class="mb-4 text-sm text-zinc-600">{{ __('authentication.two_factor.email_instructions') }}</p>

            <form method="POST" action="{{ route('two-factor.email.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email_code" class="block text-sm font-medium">{{ __('authentication.two_factor.email_code') }}</label>
                    <input id="email_code" name="email_code" type="text" inputmode="numeric" autocomplete="one-time-code" @if (! $usesApp) autofocus @endif
                           class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                    @error('email_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="remember_device" value="1"> {{ __('authentication.two_factor.remember_device') }}
                </label>

                <button type="submit" class="w-full rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('authentication.two_factor.submit') }}</button>
            </form>

            <form method="POST" action="{{ route('two-factor.email.send') }}" class="mt-3">
                @csrf
                <button type="submit" class="text-sm text-zinc-600 underline">
                    {{ $usesApp ? __('authentication.two_factor.send_email_code') : __('authentication.two_factor.resend_email_code') }}
                </button>
            </form>
        </div>
    @endif
</x-layouts.guest>
