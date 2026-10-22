<div class="space-y-6">
    <h1 class="text-2xl font-semibold">{{ __('security.mfa.title') }}</h1>

    <p class="text-sm text-zinc-600">{{ __('security.mfa.description') }}</p>

    @if ($requirement === \App\Domain\Security\Enums\MfaRequirement::Required && ! $enrolled)
        <p class="rounded bg-amber-50 px-3 py-2 text-sm text-amber-900">{{ __('security.mfa.enrollment_required') }}</p>
    @endif

    @error('disable') <p class="rounded bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p> @enderror

    <section class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6">
        <h2 class="font-medium">{{ __('security.mfa_methods.authenticator_app') }}</h2>

        @if ($usesApp)
            <p class="text-sm text-emerald-700">{{ __('security.mfa.enabled') }}</p>

            <div class="flex flex-wrap gap-3">
                <button type="button" wire:click="regenerateRecoveryCodes" class="rounded border border-zinc-300 px-3 py-2 text-sm">
                    {{ __('security.mfa.regenerate_recovery_codes') }}
                </button>
                <button type="button" wire:click="disable" wire:confirm="{{ __('security.mfa.disable_confirm') }}" class="rounded border border-red-300 px-3 py-2 text-sm text-red-700">
                    {{ __('security.mfa.disable') }}
                </button>
            </div>
        @elseif ($pending)
            <p class="text-sm">{{ __('security.mfa.scan_qr_code') }}</p>
            <div class="inline-block rounded bg-white p-2">{!! $qrCodeSvg !!}</div>
            <p class="text-sm">{{ __('security.mfa.setup_key') }}: <code class="rounded bg-zinc-100 px-1">{{ $setupKey }}</code></p>

            <form wire:submit="confirm" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="code" class="block text-sm font-medium">{{ __('authentication.two_factor.code') }}</label>
                    <input id="code" type="text" inputmode="numeric" autocomplete="one-time-code" wire:model="code" class="mt-1 rounded border border-zinc-300 px-3 py-2">
                </div>
                <button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('security.mfa.confirm') }}</button>
            </form>
            @error('code') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @else
            <button type="button" wire:click="enable" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">
                {{ __('security.mfa.enable') }}
            </button>
        @endif

        @if ($recoveryCodes !== [])
            <div>
                <p class="mb-2 text-sm">{{ __('security.mfa.recovery_codes_instructions') }}</p>
                <ul class="grid max-w-md grid-cols-2 gap-1 rounded bg-zinc-100 p-3 font-mono text-sm">
                    @foreach ($recoveryCodes as $recoveryCode)
                        <li>{{ $recoveryCode }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

    @if ($emailCodesAllowed || $usesEmail)
        <section class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6">
            <h2 class="font-medium">{{ __('security.mfa_methods.email_code') }}</h2>
            <p class="text-sm text-zinc-600">{{ __('security.email_code.description', ['email' => $user->email]) }}</p>

            @if ($usesEmail)
                <p class="text-sm text-emerald-700">{{ __('security.email_code.enabled') }}</p>
                <button type="button" wire:click="disableEmail" wire:confirm="{{ __('security.email_code.disable_confirm') }}" class="rounded border border-red-300 px-3 py-2 text-sm text-red-700">
                    {{ __('security.mfa.disable') }}
                </button>
            @elseif ($emailCodeSent)
                <form wire:submit="confirmEmail" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="emailCode" class="block text-sm font-medium">{{ __('authentication.two_factor.email_code') }}</label>
                        <input id="emailCode" type="text" inputmode="numeric" autocomplete="one-time-code" wire:model="emailCode" class="mt-1 rounded border border-zinc-300 px-3 py-2">
                    </div>
                    <button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('security.mfa.confirm') }}</button>
                    <button type="button" wire:click="sendEmailCode" class="text-sm text-zinc-600 underline">{{ __('authentication.two_factor.resend_email_code') }}</button>
                </form>
            @else
                <button type="button" wire:click="sendEmailCode" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">
                    {{ __('security.email_code.enable') }}
                </button>
            @endif

            @error('emailCode') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @error('email_code') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </section>
    @endif
</div>
