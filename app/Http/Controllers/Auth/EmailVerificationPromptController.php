<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            $defaultRoute = $request->user()->isSuperAdmin()
                ? route('super-admin.dashboard', absolute: false)
                : route('dashboard', absolute: false);
            return redirect()->intended($defaultRoute);
        }

        return view('auth.verify-email');
    }
}
