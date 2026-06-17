<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ButcherBusinessController extends Controller
{
    public function edit(): RedirectResponse
    {
        return redirect()->route('butcher.onboarding.profile');
    }

    public function update(): RedirectResponse
    {
        return redirect()->route('butcher.onboarding.profile');
    }
}
