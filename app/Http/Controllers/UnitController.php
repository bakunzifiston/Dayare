<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Unit::create($data);

        return redirect()->route('units.index')->with('status', __('Unit created.'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:units,code,' . $unit->id],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $unit->update($data);

        return redirect()->route('units.index')->with('status', __('Unit updated.'));
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        return redirect()->route('units.index')->with('status', __('Unit deleted.'));
    }
}
