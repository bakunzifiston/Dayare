<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = (array) config('app.supported_locales', ['en', 'rw']);
        $defaultLocale = (string) config('app.locale', 'en');
        $locale = $defaultLocale;

        $user = $request->user();
        if ($user !== null) {
            $preferredLocale = Setting::query()
                ->where('user_id', $user->id)
                ->where('key', 'default_language')
                ->value('value');

            if (is_string($preferredLocale) && in_array($preferredLocale, $supportedLocales, true)) {
                $locale = $preferredLocale;
            }
        } elseif ($request->hasSession()) {
            $sessionLocale = $request->session()->get('locale');
            if (is_string($sessionLocale) && in_array($sessionLocale, $supportedLocales, true)) {
                $locale = $sessionLocale;
            }
        } else {
            $preferredFromHeader = $request->getPreferredLanguage($supportedLocales);
            if (is_string($preferredFromHeader) && in_array($preferredFromHeader, $supportedLocales, true)) {
                $locale = $preferredFromHeader;
            }
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }
}
