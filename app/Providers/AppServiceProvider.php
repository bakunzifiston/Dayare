<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * Session cookie is set from the current request only — works on any host (localhost, cPanel, any domain) without .env changes.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $request = request();
        if (! $request) {
            return;
        }

        // Match session cookie to current request: HTTPS → Secure cookie, HTTP → non-Secure. No domain or APP_URL needed.
        config(['session.secure' => $request->secure()]);
    }
}
