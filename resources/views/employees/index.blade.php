<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900">
                {{ __('Employees') }}
            </h1>
            <a href="{{ route('employees.create') }}" class="inline-flex items-center px-3 py-2 rounded-lg bg-[#3B82F6] text-white text-xs font-semibold hover:bg-[#2563eb]">
                {{ __('Add employee') }}
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-slate-600">
                {{ __('List of employees for your businesses.') }}
            </p>
        </div>

        @if ($employees->isEmpty())
            <p class="text-sm text-slate-500">
                {{ __('No employees found yet.') }}
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold text-slate-500 uppercase">
                            <th class="py-2 pr-4">{{ __('Employee ID') }}</th>
                            <th class="py-2 pr-4">{{ __('Name') }}</th>
                            <th class="py-2 pr-4">{{ __('Business') }}</th>
                            <th class="py-2 pr-4">{{ __('Facility') }}</th>
                            <th class="py-2 pr-4">{{ __('Job title') }}</th>
                            <th class="py-2 pr-4">{{ __('Status') }}</th>
                            <th class="py-2 pr-4">{{ __('Hire date') }}</th>
                            <th class="py-2 pr-4 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($employees as $employee)
                            <tr>
                                <td class="py-2 pr-4 font-mono text-xs text-slate-700">
                                    {{ $employee->id }}
                                </td>
                                <td class="py-2 pr-4">
                                    <a href="{{ route('employees.show', $employee) }}" class="text-indigo-600 hover:underline font-medium">{{ $employee->first_name }} {{ $employee->last_name }}</a>
                                </td>
                                <td class="py-2 pr-4">
                                    {{ $employee->business?->business_name ?? '—' }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ $employee->facility?->facility_name ?? '—' }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ $employee->job_title ? (__(\App\Models\Employee::JOB_TITLES[$employee->job_title] ?? $employee->job_title)) : '—' }}
                                </td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $employee->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst($employee->status) }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4">
                                    {{ optional($employee->hire_date)->format('Y-m-d') ?? '—' }}
                                </td>
                                <td class="py-2 pr-4 text-right whitespace-nowrap">
                                    <a href="{{ route('employees.show', $employee) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">{{ __('View') }}</a>
                                    <span class="text-slate-300 mx-1">|</span>
                                    <a href="{{ route('employees.edit', $employee) }}" class="text-slate-600 hover:text-slate-900 text-xs font-medium">{{ __('Edit') }}</a>
                                    <span class="text-slate-300 mx-1">|</span>
                                    <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this employee?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-xs font-medium">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</x-app-layout>

