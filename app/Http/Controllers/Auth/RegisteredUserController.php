<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
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
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'business_type' => ['required', 'string', Rule::in(Business::TYPES)],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // New tenant: make them the Tenant Owner role so they start with full access for their workspace.
        if (! $user->hasRole('owner')) {
            $user->assignRole('owner');
        }

        $nameTrim = trim((string) $request->name);
        $nameParts = preg_split('/\s+/', $nameTrim, 2, PREG_SPLIT_NO_EMPTY) ?: [];
        $ownerFirst = $nameParts[0] ?? '—';
        $ownerLast = $nameParts[1] ?? '—';

        $user->businesses()->create([
            'type' => $request->business_type,
            'business_name' => __(':name\'s workspace', ['name' => $nameTrim]),
            'registration_number' => 'PENDING-'.Str::uuid()->toString(),
            'contact_phone' => '0000000000',
            'email' => $request->email,
            'status' => Business::STATUS_ACTIVE,
            'owner_first_name' => $ownerFirst,
            'owner_last_name' => $ownerLast,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect($user->tenantDashboardPath());
    }
}
