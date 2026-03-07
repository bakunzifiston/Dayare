<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Employee') }} — {{ $employee->first_name }} {{ $employee->last_name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('employees.edit', $employee) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Edit') }}</a>
                <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this employee?') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">{{ __('Delete') }}</button>
                </form>
                <a href="{{ route('employees.index') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Back to list') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Business') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->business?->business_name ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Facility') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->facility?->facility_name ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('First name') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->first_name }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Last name') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->last_name }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('National ID') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->national_id ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Date of birth') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->date_of_birth?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Nationality') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->nationality ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Job title') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->job_title ? (__(\App\Models\Employee::JOB_TITLES[$employee->job_title] ?? $employee->job_title)) : '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Employment type') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ ucfirst(str_replace('_', ' ', $employee->employment_type ?? '')) }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Hire date') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->hire_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Termination date') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->termination_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Status') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ ucfirst($employee->status) }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Work email') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->work_email ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Personal email') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->personal_email ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Phone') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->phone ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Address / location') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $employee->village?->name ?? $employee->sectorDivision?->name ?? $employee->districtDivision?->name ?? $employee->province?->name ?? $employee->country?->name ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
