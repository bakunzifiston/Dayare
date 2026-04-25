<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MobileLoginRequest;
use App\Http\Requests\MobileRegisterRequest;
use App\Http\Responses\ApiJson;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\MobileApiToken;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    public function login(MobileLoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return ApiJson::failure(__('Invalid credentials.'), [], 401);
        }

        $preferredBusinessId = isset($data['business_id']) ? (int) $data['business_id'] : null;
        if ($preferredBusinessId !== null && ! $user->accessibleBusinessIds()->contains($preferredBusinessId)) {
            return ApiJson::failure(__('Business not accessible.'), ['business_id' => [__('You do not have access to this business.')]], 404);
        }

        [$plainToken, $token] = $this->createMobileToken($user, $data['device_name'] ?? null);

        return ApiJson::success([
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($token->expires_at)->toIso8601String(),
            'user' => $this->mobileUserPayload($user, $preferredBusinessId),
        ], __('Logged in successfully.'));
    }

    /**
     * Stateless registration (same account rules as web `POST /register` — no CSRF).
     */
    public function register(MobileRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $normalizedEmail = Str::lower(trim((string) $data['email']));

        try {
            $user = DB::transaction(function () use ($data, $normalizedEmail) {
                $user = User::create([
                    'name' => trim((string) $data['name']),
                    'email' => $data['email'],
                    'email_normalized' => $normalizedEmail,
                    'password' => Hash::make($data['password']),
                ]);

                $nameTrim = trim((string) $data['name']);
                $nameParts = preg_split('/\s+/', $nameTrim, 2, PREG_SPLIT_NO_EMPTY) ?: [];
                $ownerFirst = $nameParts[0] ?? '—';
                $ownerLast = $nameParts[1] ?? '—';
                $generatedBusinessName = $this->generateUniqueWorkspaceName($nameTrim);
                $normalizedBusinessName = $this->normalizeBusinessName($generatedBusinessName);

                $business = $user->businesses()->create([
                    'type' => $data['business_type'],
                    'business_name' => $generatedBusinessName,
                    'business_name_normalized' => $normalizedBusinessName,
                    'registration_number' => 'PENDING-'.Str::uuid()->toString(),
                    'contact_phone' => '0000000000',
                    'email' => $data['email'],
                    'status' => Business::STATUS_ACTIVE,
                    'owner_first_name' => $ownerFirst,
                    'owner_last_name' => $ownerLast,
                ]);
                $role = match ($data['business_type']) {
                    Business::TYPE_FARMER => BusinessUser::ROLE_FARMER,
                    Business::TYPE_LOGISTICS => BusinessUser::ROLE_LOGISTICS_MANAGER,
                    default => BusinessUser::ROLE_ORG_ADMIN,
                };

                BusinessUser::query()->updateOrCreate(
                    ['business_id' => $business->id, 'user_id' => $user->id],
                    ['role' => $role]
                );

                return $user;
            });
        } catch (QueryException $exception) {
            $errorMessage = Str::lower($exception->getMessage());

            if (str_contains($errorMessage, 'users_email_unique')
                || str_contains($errorMessage, 'users_email_normalized_unique')
                || str_contains($errorMessage, 'users.email')) {
                throw ValidationException::withMessages([
                    'email' => [__('This email is already registered')],
                ]);
            }

            if (str_contains($errorMessage, 'businesses_business_name_unique')
                || str_contains($errorMessage, 'businesses_business_name_normalized_unique')
                || str_contains($errorMessage, 'businesses.business_name')
                || str_contains($errorMessage, 'business_name_normalized')) {
                throw ValidationException::withMessages([
                    'business_name' => [__('This business name is already taken')],
                ]);
            }

            throw $exception;
        }

        event(new Registered($user));

        $user->refresh();

        [$plainToken, $token] = $this->createMobileToken($user, $data['device_name'] ?? null);

        return ApiJson::success([
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($token->expires_at)->toIso8601String(),
            'user' => $this->mobileUserPayload($user, null),
        ], __('Registration successful.'), 201);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $preferred = $request->query('business_id');
        $preferredBusinessId = $preferred !== null && $preferred !== '' ? (int) $preferred : null;

        if ($preferredBusinessId !== null && ! $user->accessibleBusinessIds()->contains($preferredBusinessId)) {
            return ApiJson::failure(__('Business not accessible.'), ['business_id' => [__('You do not have access to this business.')]], 404);
        }

        return ApiJson::success($this->mobileUserPayload($user, $preferredBusinessId));
    }

    public function logout(Request $request): JsonResponse
    {
        $tokenId = (int) $request->attributes->get('mobile_api_token_id');
        if ($tokenId > 0) {
            MobileApiToken::whereKey($tokenId)->delete();
        }

        return ApiJson::success(null, __('Logged out successfully.'));
    }

    /**
     * @return array{0: string, 1: MobileApiToken}
     */
    private function createMobileToken(User $user, ?string $deviceName): array
    {
        $plainToken = bin2hex(random_bytes(32));
        $token = MobileApiToken::create([
            'user_id' => $user->id,
            'name' => $deviceName ?? 'mobile-app',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
            'last_used_at' => now(),
        ]);

        return [$plainToken, $token];
    }

    /**
     * @return array<string, mixed>
     */
    private function mobileUserPayload(User $user, ?int $preferredBusinessId): array
    {
        $ctx = $user->mobileApiWorkspaceContext($preferredBusinessId);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_super_admin' => (bool) $user->is_super_admin,
            'userRole' => $ctx['userRole'],
            'business_type' => $ctx['business_type'],
            'business_id' => $ctx['business_id'],
            'accessible_businesses' => $ctx['accessible_businesses'],
            'accessible_business_ids' => $user->accessibleBusinessIds()->all(),
        ];
    }

    private function normalizeBusinessName(string $businessName): string
    {
        $trimmed = trim($businessName);

        return (string) preg_replace('/\s+/', ' ', Str::lower($trimmed));
    }

    private function generateUniqueWorkspaceName(string $ownerName): string
    {
        $baseName = $ownerName !== ''
            ? __(':name\'s workspace', ['name' => $ownerName])
            : __('Workspace');
        $candidate = $baseName;
        $counter = 2;

        while (Business::query()
            ->where('business_name_normalized', $this->normalizeBusinessName($candidate))
            ->exists()) {
            $candidate = "{$baseName} {$counter}";
            $counter++;
        }

        return $candidate;
    }
}
