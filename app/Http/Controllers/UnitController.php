<?php

namespace App\Http\Controllers;

use App\Models\Demand;
use App\Models\Unit;
use App\Models\WarehouseStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        $units = Unit::orderBy('sort_order')->orderBy('name')->get();

        return view('units.index', compact('units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:units,code'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['name'] = trim((string) $data['name']);
        $data['code'] = Str::lower(trim((string) $data['code']));
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $nameExists = Unit::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->exists();
        if ($nameExists) {
            return redirect()->back()->withErrors(['name' => __('Unit name already exists.')])->withInput();
        }

        Unit::create($data);

        return redirect()->route('super-admin.units.index')->with('status', __('Unit created.'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:units,code,' . $unit->id],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['name'] = trim((string) $data['name']);
        $data['code'] = Str::lower(trim((string) $data['code']));
        $data['is_active'] = $request->boolean('is_active', false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $nameExists = Unit::query()
            ->whereKeyNot($unit->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->exists();
        if ($nameExists) {
            return redirect()->back()->withErrors(['name' => __('Unit name already exists.')])->withInput();
        }

        $unit->update($data);

        return redirect()->route('super-admin.units.index')->with('status', __('Unit updated.'));
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $inUse = Demand::query()->where('quantity_unit', $unit->code)->exists()
            || WarehouseStorage::query()->where('quantity_unit', $unit->code)->exists();

        if ($inUse) {
            $unit->update(['is_active' => false]);

            return redirect()->route('super-admin.units.index')->with('status', __('Unit was in use and has been deactivated.'));
        }

        $unit->delete();

        return redirect()->route('super-admin.units.index')->with('status', __('Unit deleted.'));
    }
}
