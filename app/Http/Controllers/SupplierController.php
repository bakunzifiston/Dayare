<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $businessIds = $request->user()->businesses()->pluck('id');

        $suppliers = Supplier::with('business')
            ->whereIn('business_id', $businessIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10);

        $baseQuery = Supplier::whereIn('business_id', $businessIds);
        $kpis = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'approved' => (clone $baseQuery)->where('supplier_status', 'approved')->count(),
        ];

        return view('suppliers.index', compact('suppliers', 'kpis'));
    }

    public function create(Request $request): View
    {
        $businesses = $request->user()->businesses()->get();

        return view('suppliers.create', compact('businesses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $businessIds = $request->user()->businesses()->pluck('id')->all();

        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'in:'.implode(',', $businessIds)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'country_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'province_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'district_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'sector_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'cell_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'village_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'supplier_status' => ['nullable', 'string', 'in:'.implode(',', array_keys(Supplier::STATUSES))],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['supplier_status'] = $validated['supplier_status'] ?? Supplier::STATUS_APPROVED;

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->with('status', __('Supplier created successfully.'));
    }

    public function show(Request $request, Supplier $supplier): View
    {
        if (! $request->user()->businesses()->whereKey($supplier->business_id)->exists()) {
            abort(404);
        }

        $supplier->load(['business', 'country', 'province', 'districtDivision', 'sectorDivision', 'cell', 'village']);

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Request $request, Supplier $supplier): View
    {
        if (! $request->user()->businesses()->whereKey($supplier->business_id)->exists()) {
            abort(404);
        }

        $businesses = $request->user()->businesses()->get();

        return view('suppliers.edit', compact('supplier', 'businesses'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        if (! $request->user()->businesses()->whereKey($supplier->business_id)->exists()) {
            abort(404);
        }

        $businessIds = $request->user()->businesses()->pluck('id')->all();

        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'in:'.implode(',', $businessIds)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'country_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'province_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'district_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'sector_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'cell_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'village_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'supplier_status' => ['nullable', 'string', 'in:'.implode(',', array_keys(Supplier::STATUSES))],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['supplier_status'] = $validated['supplier_status'] ?? $supplier->supplier_status;

        $supplier->update($validated);

        return redirect()->route('suppliers.index')->with('status', __('Supplier updated successfully.'));
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        if (! $request->user()->businesses()->whereKey($supplier->business_id)->exists()) {
            abort(404);
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('status', __('Supplier deleted.'));
    }
}

