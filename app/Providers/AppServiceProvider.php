<?php

namespace App\Providers;

use App\Models\ColdRoomTemperatureLog;
use App\Observers\ColdRoomTemperatureLogObserver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
     */
    public function boot(): void
    {
        ColdRoomTemperatureLog::observe(ColdRoomTemperatureLogObserver::class);

        // Super Admin bypasses all permission checks (roles/permissions still apply to tenants).
        Gate::before(function ($user, $ability) {
            if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }
        });

        // Session cookie: must match your site (HTTP vs HTTPS) so the browser sends it.
        $appUrl = config('app.url');
        if ($appUrl) {
            if (str_starts_with($appUrl, 'https://')) {
                config(['session.secure' => true]);  // HTTPS: cookie must be Secure so browser sends it
            } else {
                config(['session.secure' => false]); // HTTP: do not use Secure or browser won't send cookie
            }
        }

        // Use correct domain for links (View, Facilities, Edit) so they work on cPanel/production.
        $appUrl = config('app.url');
        $appHost = $appUrl ? parse_url($appUrl, PHP_URL_HOST) : null;
        $appUrlIsProduction = $appHost && ! in_array($appHost, ['localhost', '127.0.0.1'], true)
            && ! str_ends_with((string) $appHost, '.local') && ! str_ends_with((string) $appHost, '.test');

        if ($appUrlIsProduction && $appUrl) {
            // On server: APP_URL is set to real domain (e.g. https://dayare.sandbox.rw) – use it for all links.
            URL::forceRootUrl(rtrim($appUrl, '/'));
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }

            return;
        }

        if ($this->app->runningInConsole()) {
            if ($appUrl) {
                URL::forceRootUrl(rtrim($appUrl, '/'));
            }

            return;
        }

        $request = Request::capture();
        $host = $request->getHost();
        $isLocal = in_array($host, ['localhost', '127.0.0.1'], true)
            || str_ends_with($host, '.local') || str_ends_with($host, '.test');

        if (! $isLocal) {
            $scheme = $request->getScheme();
            $port = $request->getPort();
            $url = $scheme.'://'.$host.(in_array($port, [80, 443, null], true) ? '' : ':'.$port);
            URL::forceRootUrl(rtrim($url, '/'));
            if ($scheme === 'https') {
                URL::forceScheme('https');
            }
        } elseif ($appUrl) {
            URL::forceRootUrl(rtrim($appUrl, '/'));
        }
    }
}
