<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProcessorBusinessContextController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $businessIds = $request->user()->accessibleProcessorBusinessIds()->map(fn ($id) => (int) $id)->all();
        $validated = $request->validate([
            'business_id' => ['required', 'integer', Rule::in($businessIds)],
        ]);

        $request->user()->setActiveProcessorBusinessId((int) $validated['business_id']);

        return redirect()->back()->with('status', __('Active business switched.'));
    }
}
