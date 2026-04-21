<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $normalizedEmail = $this->normalizeEmail((string) $request->input('email', ''));

        $request->merge([
            'email' => $normalizedEmail,
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'business_type' => ['required', 'string', Rule::in(Business::TYPES)],
        ], [
            'email.unique' => __('This email is already registered'),
        ]);

        try {
            $user = DB::transaction(function () use ($request, $normalizedEmail) {
                $user = User::create([
                    'name' => trim((string) $request->name),
                    'email' => $request->email,
                    'email_normalized' => $normalizedEmail,
                    'password' => Hash::make($request->password),
                ]);

                $nameTrim = trim((string) $request->name);
                $nameParts = preg_split('/\s+/', $nameTrim, 2, PREG_SPLIT_NO_EMPTY) ?: [];
                $ownerFirst = $nameParts[0] ?? '—';
                $ownerLast = $nameParts[1] ?? '—';
                $generatedBusinessName = $this->generateUniqueWorkspaceName($nameTrim);
                $normalizedBusinessName = $this->normalizeBusinessName($generatedBusinessName);

                $business = $user->businesses()->create([
                    'type' => $request->business_type,
                    'business_name' => $generatedBusinessName,
                    'business_name_normalized' => $normalizedBusinessName,
                    'registration_number' => 'PENDING-'.Str::uuid()->toString(),
                    'contact_phone' => '0000000000',
                    'email' => $request->email,
                    'status' => Business::STATUS_ACTIVE,
                    'owner_first_name' => $ownerFirst,
                    'owner_last_name' => $ownerLast,
                ]);
                BusinessUser::query()->updateOrCreate(
                    ['business_id' => $business->id, 'user_id' => $user->id],
                    ['role' => BusinessUser::ROLE_ORG_ADMIN]
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

        Auth::login($user);

        return redirect($user->tenantDashboardPath());
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
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
