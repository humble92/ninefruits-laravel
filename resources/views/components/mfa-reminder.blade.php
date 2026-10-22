@php($reminder = session(\App\Http\Middleware\EnforceMfaPolicy::REMINDER_SESSION_KEY))
@if ($reminder && ! request()->routeIs('portal.security'))
    <div class="mb-6 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
        @if ($reminder['required'] && $reminder['deadline'])
            {{ __('security.mfa.required_by', ['date' => \Illuminate\Support\Carbon::parse($reminder['deadline'])->isoFormat('LL')]) }}
        @else
            {{ __('security.mfa.recommended') }}
        @endif
        <a href="{{ route('portal.security') }}" class="ms-2 font-medium underline">{{ __('security.mfa.set_up') }}</a>
    </div>
@endif
