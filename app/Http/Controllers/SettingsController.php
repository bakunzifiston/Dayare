<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $settings = Setting::where('user_id', $user->id)->pluck('value', 'key');

        return view('settings.edit', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'system_name' => ['nullable', 'string', 'max:255'],
            'default_language' => ['nullable', 'string', Rule::in(config('app.supported_locales', ['en', 'rw']))],
            'default_country' => ['nullable', 'string', 'max:100'],
            'default_daily_capacity' => ['nullable', 'integer', 'min:0'],
            'temperature_warning' => ['nullable', 'integer', 'min:-50', 'max:100'],
            'temperature_critical' => ['nullable', 'integer', 'min:-50', 'max:100'],
            'max_storage_days' => ['nullable', 'integer', 'min:0'],
            'alert_email' => ['nullable', 'email', 'max:255'],
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['user_id' => $user->id, 'key' => $key],
                ['value' => $value]
            );
        }

        return redirect()
            ->route('settings.edit')
            ->with('status', __('Settings updated successfully.'));
    }
}

