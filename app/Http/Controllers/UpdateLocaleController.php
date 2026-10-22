<?php

namespace App\Http\Controllers;

use App\Domain\Institution\Services\SupportedLocales;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateLocaleController
{
    public function __invoke(Request $request, SupportedLocales $locales): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in($locales->all())],
        ]);

        $request->session()->put(SetLocale::SESSION_KEY, $validated['locale']);
        $request->user()?->forceFill(['locale' => $validated['locale']])->save();

        return back();
    }
}
