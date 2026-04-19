<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiJson;
use App\Models\MobileApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = \App\Models\User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return ApiJson::failure(__('Invalid credentials.'), [], 401);
        }

        $plainToken = bin2hex(random_bytes(32));
        $token = MobileApiToken::create([
            'user_id' => $user->id,
            'name' => $data['device_name'] ?? 'mobile-app',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
            'last_used_at' => now(),
        ]);

        return ApiJson::success([
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($token->expires_at)->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => (bool) $user->is_super_admin,
                'userRole' => $user->mobileUserRole(),
                'business_type' => $user->tenantWorkspaceType(),
            ],
        ], __('Logged in successfully.'));
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiJson::success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_super_admin' => (bool) $user->is_super_admin,
            'userRole' => $user->mobileUserRole(),
            'business_type' => $user->tenantWorkspaceType(),
            'accessible_business_ids' => $user->accessibleBusinessIds()->all(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $tokenId = (int) $request->attributes->get('mobile_api_token_id');
        if ($tokenId > 0) {
            MobileApiToken::whereKey($tokenId)->delete();
        }

        return ApiJson::success(null, __('Logged out successfully.'));
    }
}
