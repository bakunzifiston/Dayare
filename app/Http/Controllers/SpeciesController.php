<?php

namespace App\Http\Controllers;

use App\Models\AnimalIntake;
use App\Models\AnteMortemInspection;
use App\Models\Batch;
use App\Models\Contract;
use App\Models\Demand;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\SlaughterPlan;
use App\Models\Species;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        $data['name'] = trim((string) $data['name']);
        $data['code'] = Str::lower(trim((string) $data['code']));
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $nameExists = Species::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->exists();
        if ($nameExists) {
            return redirect()->back()->withErrors(['name' => __('Species name already exists.')])->withInput();
        }

        Species::create($data);

        return redirect()->route('super-admin.species.index')->with('status', __('Species created.'));
    }

    public function update(Request $request, Species $species): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:species,code,' . $species->id],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['name'] = trim((string) $data['name']);
        $data['code'] = Str::lower(trim((string) $data['code']));
        $data['is_active'] = $request->boolean('is_active', false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $nameExists = Species::query()
            ->whereKeyNot($species->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->exists();
        if ($nameExists) {
            return redirect()->back()->withErrors(['name' => __('Species name already exists.')])->withInput();
        }

        $species->update($data);

        return redirect()->route('super-admin.species.index')->with('status', __('Species updated.'));
    }

    public function destroy(Species $species): RedirectResponse
    {
        $speciesName = mb_strtolower(trim($species->name));
        $inUse = AnimalIntake::query()->whereRaw('LOWER(species) = ?', [$speciesName])->exists()
            || SlaughterPlan::query()->whereRaw('LOWER(species) = ?', [$speciesName])->exists()
            || AnteMortemInspection::query()->whereRaw('LOWER(species) = ?', [$speciesName])->exists()
            || Batch::query()->whereRaw('LOWER(species) = ?', [$speciesName])->exists()
            || PostMortemInspection::query()->whereRaw('LOWER(species) = ?', [$speciesName])->exists()
            || Demand::query()->whereRaw('LOWER(species) = ?', [$speciesName])->exists()
            || Contract::query()->whereRaw("LOWER(COALESCE(species_covered, '')) = ?", [$speciesName])->exists()
            || Inspector::query()->whereRaw("LOWER(COALESCE(species_allowed, '')) LIKE ?", ['%'.$speciesName.'%'])->exists();

        if ($inUse) {
            $species->update(['is_active' => false]);

            return redirect()->route('super-admin.species.index')->with('status', __('Species was in use and has been deactivated.'));
        }

        $species->delete();

        return redirect()->route('super-admin.species.index')->with('status', __('Species deleted.'));
    }
}

