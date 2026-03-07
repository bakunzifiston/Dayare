<?php

namespace App\Http\Controllers;

use App\Models\Species;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpeciesController extends Controller
{
    public function index(): View
    {
        $species = Species::orderBy('sort_order')->orderBy('name')->get();

        return view('species.index', compact('species'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:species,code'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Species::create($data);

        return redirect()->route('species.index')->with('status', __('Species created.'));
    }

    public function update(Request $request, Species $species): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:species,code,' . $species->id],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $species->update($data);

        return redirect()->route('species.index')->with('status', __('Species updated.'));
    }

    public function destroy(Species $species): RedirectResponse
    {
        $species->delete();

        return redirect()->route('species.index')->with('status', __('Species deleted.'));
    }
}

