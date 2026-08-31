<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Facilities') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $facility->facility_name }}</p>
                    <p class="text-xs text-slate-500">{{ $facility->facility_type }} · {{ ucfirst($facility->status) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('businesses.facilities.edit', [$business, $facility]) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                    <form method="POST" action="{{ route('businesses.facilities.destroy', [$business, $facility]) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this facility? This cannot be undone.') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-medium text-red-700 hover:bg-red-100">{{ __('Delete') }}</button>
                    </form>
                    <a href="{{ route('businesses.facilities.index', $business) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Facility Name') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $facility->facility_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Facility Type') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $facility->facility_type }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500">{{ __('Location') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $facility->location_display }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500">{{ __('GPS') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $facility->gps ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('License Number') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $facility->license_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('License Issue Date') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $facility->license_issue_date?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('License Expiry Date') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">
                            {{ $facility->license_expiry_date?->format('d M Y') ?? '—' }}
                            @if ($facility->isLicenseExpired())
                                <span class="text-red-600">{{ __('(Expired)') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Daily Production Capacity') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $facility->daily_capacity ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Status') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst($facility->status) }}</dd>
                    </div>
                </dl>
        </section>

            @if ($facility->employees->isNotEmpty())
                <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Employees at this facility') }}</p>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($facility->employees as $emp)
                            <li class="flex items-center justify-between gap-3 px-4 py-2.5">
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
                </section>
            @endif

            @if ($facility->inspectors->isNotEmpty())
                <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Inspectors assigned to this facility') }}</p>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($facility->inspectors as $insp)
                            <li class="px-4 py-2.5">
                                <a href="{{ route('inspectors.show', $insp) }}" class="font-medium text-slate-900 hover:text-bucha-primary">{{ $insp->full_name }}</a>
                                <span class="text-sm text-slate-500"> — {{ $insp->authorization_number }} · {{ ucfirst($insp->status) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
    </div>
</x-app-layout>
