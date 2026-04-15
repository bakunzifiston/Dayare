@component('layouts.logistics', [
    'pageTitle' => __('Dashboard'),
    'pageSubtitle' => __('Single workspace shell with predictable module switching.'),
    'selectedCompanyId' => $selectedCompanyId,
])
    <div class="space-y-4">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm text-slate-700">{{ __('Welcome, :name', ['name' => $user->name]) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ __('Use one company context and move through modules without stale state.') }}</p>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <a data-logistics-nav href="{{ route('logistics.company.index', $selectedCompanyId ? ['company_id' => $selectedCompanyId] : []) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Company') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Manage profile') }}</p>
            </a>
            <a data-logistics-nav href="{{ route('logistics.assets.index', $selectedCompanyId ? ['company_id' => $selectedCompanyId] : []) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Assets') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Manage vehicles and drivers') }}</p>
            </a>
            <a data-logistics-nav href="{{ route('logistics.orders.index', $selectedCompanyId ? ['company_id' => $selectedCompanyId] : []) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Orders') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Manage requests') }}</p>
            </a>
            <a data-logistics-nav href="{{ route('logistics.planning.index', $selectedCompanyId ? ['company_id' => $selectedCompanyId] : []) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Trip Planning') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Allocate assets') }}</p>
            </a>
            <a data-logistics-nav href="{{ route('logistics.trips.index', $selectedCompanyId ? ['company_id' => $selectedCompanyId] : []) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Active Trips') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Execute delivery') }}</p>
            </a>
            <a data-logistics-nav href="{{ route('logistics.tracking.index', $selectedCompanyId ? ['company_id' => $selectedCompanyId] : []) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Tracking') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Log trip movements') }}</p>
            </a>
            <a data-logistics-nav href="{{ route('logistics.compliance.index', $selectedCompanyId ? ['company_id' => $selectedCompanyId] : []) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Compliance') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Attach required documents') }}</p>
            </a>
            <a data-logistics-nav href="{{ route('logistics.billing.index', $selectedCompanyId ? ['company_id' => $selectedCompanyId] : []) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#7A1C22]">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Billing') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ __('Generate invoices') }}</p>
            </a>
        </div>
    </div>
@endcomponent
