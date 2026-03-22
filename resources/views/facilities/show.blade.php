<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $facility->facility_name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('businesses.facilities.edit', [$business, $facility]) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <form method="POST" action="{{ route('businesses.facilities.destroy', [$business, $facility]) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this facility? This cannot be undone.') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                        {{ __('Delete') }}
                    </button>
                </form>
                <a href="{{ route('businesses.facilities.index', $business) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Back to Facilities') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Facility Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $facility->facility_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Facility Type') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $facility->facility_type }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Location') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $facility->location_display }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('GPS') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $facility->gps ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('License Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $facility->license_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('License Issue Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $facility->license_issue_date?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('License Expiry Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $facility->license_expiry_date?->format('d M Y') ?? '—' }}
                            @if ($facility->isLicenseExpired())
                                <span class="text-red-600">{{ __('(Expired)') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Daily Production Capacity') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $facility->daily_capacity ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($facility->status) }}</dd>
                    </div>
                </dl>
            </div>

            @if ($facility->employees->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 mt-6">
                    <h3 class="text-base font-semibold text-slate-800 mb-3">{{ __('Employees at this facility') }}</h3>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($facility->employees as $emp)
                            <li class="py-2.5 flex justify-between items-center">
                                <div>
                                    <a href="{{ route('employees.show', $emp) }}" class="font-medium text-slate-900 hover:text-bucha-primary">{{ $emp->first_name }} {{ $emp->last_name }}</a>
                                    <span class="text-sm text-slate-500 ml-1">— {{ $emp->job_title ? (\App\Models\Employee::JOB_TITLES[$emp->job_title] ?? $emp->job_title) : __('—') }}</span>
                                </div>
                                @if ($emp->status)
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $emp->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($emp->status) }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($facility->inspectors->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6 mt-6">
                    <h3 class="text-base font-semibold text-slate-800 mb-3">{{ __('Inspectors assigned to this facility') }}</h3>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($facility->inspectors as $insp)
                            <li class="py-2.5">
                                <a href="{{ route('inspectors.show', $insp) }}" class="font-medium text-slate-900 hover:text-bucha-primary">{{ $insp->full_name }}</a>
                                <span class="text-sm text-slate-500"> — {{ $insp->authorization_number }} · {{ ucfirst($insp->status) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
