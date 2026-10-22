<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Person\Actions\AcceptInvitation;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Account creation from an invitation link: the person chooses a password.
 */
class InvitationController
{
    public function show(string $token): View
    {
        $invitation = AcceptInvitation::find($token);

        return view('auth.accept-invitation', ['token' => $token, 'email' => $invitation?->email]);
    }

    public function store(Request $request, string $token, AcceptInvitation $accept, StatefulGuard $guard): RedirectResponse
    {
        $request->validate(['password' => ['required', 'confirmed', Password::defaults()]]);

        $user = $accept->handle($token, (string) $request->input('password'));

        $guard->login($user);
        $request->session()->regenerate();

        return redirect()->route('portal.dashboard')->with('status', __('people.invitations.accepted'));
    }
}
