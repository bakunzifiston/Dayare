@if ($permit->imported_from_pdf || $permit->owner_name || $permit->owner_national_id)
    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Permit identification') }}</h3>
        <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div><dt class="text-slate-500">{{ __('Permit number') }}</dt><dd class="font-mono font-medium">{{ $permit->permit_number }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Issue date') }}</dt><dd class="font-medium">{{ $permit->issue_date?->format('d/m/Y') ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Expiry date') }}</dt><dd class="font-medium">{{ $permit->expiry_date?->format('d/m/Y') ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Movement reason') }}</dt><dd class="font-medium">{{ $permit->movement_reason ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Issuing officer') }}</dt><dd class="font-medium">{{ $permit->issued_by ?: '—' }}</dd></div>
        </dl>
    </section>

    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm text-sm">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Owner information') }}</h3>
        <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div><dt class="text-slate-500">{{ __('Owner name') }}</dt><dd class="font-medium">{{ $permit->owner_name ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('National ID') }}</dt><dd class="font-medium font-mono text-xs">{{ $permit->owner_national_id ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Phone') }}</dt><dd class="font-medium">{{ $permit->owner_phone ?: '—' }}</dd></div>
            <div class="sm:col-span-2 lg:col-span-3"><dt class="text-slate-500">{{ __('Owner address (origin)') }}</dt><dd class="font-medium">{{ $permit->owner_address ?: $permit->origin_location ?: '—' }}</dd></div>
        </dl>
    </section>
@endif
