<?php

namespace App\Providers;

use Illuminate\Http\Request;
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
        // Use correct domain for links (View, Facilities, Edit) so they work on cPanel/production.
        // Prefer request URL when the request is to a real domain (not localhost), so it works even if APP_URL is wrong.
        if ($this->app->runningInConsole()) {
            $appUrl = config('app.url');
            if ($appUrl) {
                URL::forceRootUrl(rtrim($appUrl, '/'));
            }
            return;
        }

        $request = Request::capture();
        $host = $request->getHost();
        $isLocal = in_array($host, ['localhost', '127.0.0.1'], true)
            || str_ends_with($host, '.local') || str_ends_with($host, '.test');

        if (!$isLocal) {
            // Production-like host: force root URL from the request so links use the same domain.
            $scheme = $request->getScheme();
            $port = $request->getPort();
            $url = $scheme . '://' . $host . (in_array($port, [80, 443, null], true) ? '' : ':' . $port);
            URL::forceRootUrl(rtrim($url, '/'));
            if ($scheme === 'https') {
                URL::forceScheme('https');
            }
        } else {
            $appUrl = config('app.url');
            if ($appUrl) {
                URL::forceRootUrl(rtrim($appUrl, '/'));
            }
        }
    }
}
