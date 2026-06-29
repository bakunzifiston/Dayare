<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class PwaServiceWorkerController extends Controller
{
    public function __invoke(): Response
    {
        if (! config('pwa.enabled')) {
            abort(404);
        }

        $path = resource_path('pwa/service-worker.js');
        if (! File::exists($path)) {
            abort(404);
        }

        $version = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) config('pwa.cache_version', 'v1'));
        $script = str_replace('__CACHE_VERSION__', $version, File::get($path));

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Service-Worker-Allowed' => '/',
        ]);
    }
}
