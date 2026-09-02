<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Compliance') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $site->name }}</p>
                    <p class="text-xs text-slate-500">{{ $site->siteTypeLabel() }} · {{ $site->locationDisplay() }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('sales-compliance.inspections.create', ['site_id' => $site->id]) }}" class="inline-flex h-8 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Schedule visit') }}</a>
                    <a href="{{ route('sales-compliance.sites.edit', $site) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                    <form method="POST" action="{{ route('sales-compliance.sites.destroy', $site) }}" onsubmit="return confirm(@js(__('Delete this site and its inspections?')));">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-medium text-red-700 hover:bg-red-100">{{ __('Delete') }}</button>
                    </form>
                    <a href="{{ route('sales-compliance.sites.index') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Site type') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $site->siteTypeLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Location') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $site->locationDisplay() }}</dd>
                </div>
                @if ($site->site_type === \App\Support\SalesComplianceCatalog::SITE_PRIVATE_EVENT)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Event') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ collect([$site->event_type, $site->event_name])->filter()->implode(' · ') ?: '—' }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Contact') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $site->contact_name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Phone') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $site->contact_phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Email') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $site->contact_email ?: '—' }}</dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Recent inspections') }}</p>
            </div>
            @include('sales-compliance.partials.inspection-table', ['rows' => $site->inspections, 'empty' => __('No inspections for this site.')])
        </section>
    </div>
</x-app-layout>
