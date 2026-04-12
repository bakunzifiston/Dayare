<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiJson;
use App\Models\MobileApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        if (! preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return ApiJson::failure(__('Unauthorized.'), [], 401);
        }

        $plainToken = trim($matches[1]);
        if ($plainToken === '') {
            return ApiJson::failure(__('Unauthorized.'), [], 401);
        }

        $token = MobileApiToken::with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (! $token || $token->isExpired() || ! $token->user) {
            return ApiJson::failure(__('Unauthorized.'), [], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('mobile_api_token_id', $token->id);
        $request->setUserResolver(fn () => $token->user);

        return $next($request);
    }
}
