<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $businessIds = $request->user()->accessibleBusinessIds();

        $employees = Employee::with(['business', 'facility'])
            ->whereIn('business_id', $businessIds)
            ->orderByDesc('created_at')
            ->paginate(10);

        $baseQuery = Employee::whereIn('business_id', $businessIds);
        $kpis = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
        ];

        return view('employees.index', compact('employees', 'kpis'));
    }

    public function create(Request $request): View
    {
        $businesses = $request->user()->accessibleBusinesses()->with('facilities')->get();

        return view('employees.create', compact('businesses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $businessIds = $request->user()->accessibleBusinessIds()->all();

        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'in:'.implode(',', $businessIds)],
            'facility_id' => ['nullable', 'integer'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', 'string', Rule::in(Employee::GENDERS)],
            'pwd_status' => ['nullable', 'string', Rule::in(Employee::PWD_STATUSES)],
            'is_refugee' => ['nullable', 'boolean'],
            'is_host_community' => ['nullable', 'boolean'],
            'consent_given' => ['nullable', 'boolean'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'country_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'province_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'district_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'sector_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'cell_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'village_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'work_email' => ['nullable', 'email', 'max:255'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', Rule::in(array_merge([''], array_keys(Employee::JOB_TITLES)))],
            'employment_type' => ['required', 'string', 'max:50'],
            'hire_date' => ['required', 'date'],
            'termination_date' => ['nullable', 'date', 'after:hire_date'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $validated['job_title'] = $request->filled('job_title') ? $validated['job_title'] : null;

        Employee::create($validated);

        return redirect()->route('employees.index')->with('status', __('Employee created successfully.'));
    }

    public function show(Request $request, Employee $employee): View
    {
        if (! $request->user()->accessibleBusinessIds()->contains($employee->business_id)) {
            abort(404);
        }

        $employee->load(['business', 'facility', 'country', 'province', 'districtDivision', 'sectorDivision', 'cell', 'village']);

        return view('employees.show', compact('employee'));
    }

    public function edit(Request $request, Employee $employee): View
    {
        if (! $request->user()->accessibleBusinessIds()->contains($employee->business_id)) {
            abort(404);
        }

        $businesses = $request->user()->accessibleBusinesses()->with('facilities')->get();

        return view('employees.edit', compact('employee', 'businesses'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        if (! $request->user()->accessibleBusinessIds()->contains($employee->business_id)) {
            abort(404);
        }

        $businessIds = $request->user()->accessibleBusinessIds()->all();

        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'in:'.implode(',', $businessIds)],
            'facility_id' => ['nullable', 'integer'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', 'string', Rule::in(Employee::GENDERS)],
            'pwd_status' => ['nullable', 'string', Rule::in(Employee::PWD_STATUSES)],
            'is_refugee' => ['nullable', 'boolean'],
            'is_host_community' => ['nullable', 'boolean'],
            'consent_given' => ['nullable', 'boolean'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'country_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'province_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'district_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'sector_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'cell_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'village_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'work_email' => ['nullable', 'email', 'max:255'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', Rule::in(array_merge([''], array_keys(Employee::JOB_TITLES)))],
            'employment_type' => ['required', 'string', 'max:50'],
            'hire_date' => ['required', 'date'],
            'termination_date' => ['nullable', 'date', 'after:hire_date'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $validated['job_title'] = $request->filled('job_title') ? $validated['job_title'] : null;

        $employee->update($validated);

        return redirect()->route('employees.index')->with('status', __('Employee updated successfully.'));
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        if (! $request->user()->accessibleBusinessIds()->contains($employee->business_id)) {
            abort(404);
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('status', __('Employee deleted.'));
    }
}

