<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Setting;
use App\Models\Species;
use App\Models\Unit;
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
        $businesses = Business::query()
            ->whereIn('id', $user->accessibleBusinessIds())
            ->orderBy('business_name')
            ->get(['id', 'business_name', 'type']);
        $species = Species::active()->get(['id', 'name']);
        $units = Unit::active()->get(['id', 'name', 'code']);
        $selectedSpeciesByBusiness = $businesses->mapWithKeys(function (Business $business) {
            return [$business->id => $business->configuredSpecies()->pluck('species.id')->all()];
        });
        $selectedUnitsByBusiness = $businesses->mapWithKeys(function (Business $business) {
            return [$business->id => $business->configuredUnits()->pluck('units.id')->all()];
        });

        return view('settings.edit', [
            'settings' => $settings,
            'businesses' => $businesses,
            'species' => $species,
            'units' => $units,
            'selectedSpeciesByBusiness' => $selectedSpeciesByBusiness,
            'selectedUnitsByBusiness' => $selectedUnitsByBusiness,
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
            'business_species' => ['nullable', 'array'],
            'business_species.*' => ['nullable', 'array'],
            'business_species.*.*' => [Rule::exists('species', 'id')->where('is_active', true)],
            'business_units' => ['nullable', 'array'],
            'business_units.*' => ['nullable', 'array'],
            'business_units.*.*' => [Rule::exists('units', 'id')->where('is_active', true)],
        ]);

        foreach ($data as $key => $value) {
            if (in_array($key, ['business_species', 'business_units'], true)) {
                continue;
            }
            Setting::updateOrCreate(
                ['user_id' => $user->id, 'key' => $key],
                ['value' => $value]
            );
        }

        $accessibleIds = $user->accessibleBusinessIds()->map(fn ($id) => (string) $id)->all();
        $speciesPayload = (array) ($data['business_species'] ?? []);
        $unitsPayload = (array) ($data['business_units'] ?? []);

        foreach ($accessibleIds as $businessId) {
            $business = Business::query()->find((int) $businessId);
            if ($business === null) {
                continue;
            }

            $speciesIds = array_filter(array_map('intval', (array) ($speciesPayload[$businessId] ?? [])));
            $unitIds = array_filter(array_map('intval', (array) ($unitsPayload[$businessId] ?? [])));
            $business->configuredSpecies()->sync($speciesIds);
            $business->configuredUnits()->sync($unitIds);
        }

        return redirect()
            ->route('settings.edit')
            ->with('status', __('Settings updated successfully.'));
    }
}

