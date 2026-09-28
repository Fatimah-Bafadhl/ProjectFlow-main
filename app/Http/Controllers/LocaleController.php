<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(config('app.supported_locales', ['ar']))],
        ]);

        // Guests and logged-in users both get the session value.
        $request->session()->put('locale', $data['locale']);

        // Logged-in users also persist it on their account.
        if ($user = $request->user()) {
            $user->update(['locale' => $data['locale']]);
        }

        return back();
    }
}