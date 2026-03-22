<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Employees') }}
            </h2>
            <a href="{{ route('employees.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                {{ __('Add employee') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('Active') }}" :value="$kpis['active']" color="green" />
            </div>
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($employees->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No employees yet.') }}</p>
                    <p class="text-sm mb-4">{{ __('List of employees for your businesses.') }}</p>
                    <a href="{{ route('employees.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Add first employee') }}</a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <th class="px-4 py-3">{{ __('Name') }}</th>
                                    <th class="px-4 py-3">{{ __('Business') }}</th>
                                    <th class="px-4 py-3">{{ __('Facility') }}</th>
                                    <th class="px-4 py-3">{{ __('Job title') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Hire date') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($employees as $employee)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('employees.show', $employee) }}" class="font-medium text-slate-900 hover:text-bucha-primary">{{ $employee->first_name }} {{ $employee->last_name }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ $employee->business?->business_name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $employee->facility?->facility_name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $employee->job_title ? (__(\App\Models\Employee::JOB_TITLES[$employee->job_title] ?? $employee->job_title)) : '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $employee->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                {{ ucfirst($employee->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ optional($employee->hire_date)->format('d M Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('employees.show', $employee) }}" class="text-bucha-primary hover:text-bucha-burgundy text-xs font-medium">{{ __('View') }}</a>
                                            <span class="text-slate-300 mx-1">|</span>
                                            <a href="{{ route('employees.edit', $employee) }}" class="text-slate-600 hover:text-slate-800 text-xs font-medium">{{ __('Edit') }}</a>
                                            <span class="text-slate-300 mx-1">|</span>
                                            <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this employee?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('Delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $employees->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
