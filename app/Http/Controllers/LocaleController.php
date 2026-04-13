<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $supportedLocales = (array) config('app.supported_locales', ['en', 'rw']);

        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in($supportedLocales)],
        ]);

        $locale = $validated['locale'];

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        if ($request->user() !== null) {
            Setting::query()->updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'key' => 'default_language',
                ],
                [
                    'value' => $locale,
                ]
            );
        }

        app()->setLocale($locale);

        return back();
    }
}
