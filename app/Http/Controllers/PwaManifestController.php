<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class PwaManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        if (! config('pwa.enabled')) {
            abort(404);
        }

        return response()->json([
            'name' => (string) config('pwa.name'),
            'short_name' => (string) config('pwa.short_name'),
            'description' => (string) config('pwa.description'),
            'start_url' => (string) config('pwa.start_url'),
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => (string) config('pwa.background_color'),
            'theme_color' => (string) config('pwa.theme_color'),
            'icons' => [
                [
                    'src' => asset('pwa-icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('pwa-icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('pwa-icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
