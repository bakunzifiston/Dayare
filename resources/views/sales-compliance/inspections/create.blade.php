<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Compliance') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Schedule visit') }}</p>
                    <p class="text-xs text-slate-500">{{ __('Assign an inspector and a site. The checklist is recorded after the visit.') }}</p>
                </div>
                <a href="{{ route('sales-compliance.hub') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
            </div>
            <div class="px-4 py-4">
                @if ($sites->isEmpty())
                    <p class="text-sm text-slate-600">{{ __('Add a site before scheduling a visit.') }}</p>
                    <a href="{{ route('sales-compliance.sites.create') }}" class="mt-3 inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Add site') }}</a>
                @elseif ($inspectors->isEmpty() && $inspectorUsers->isEmpty())
                    <p class="text-sm text-slate-600">{{ __('Add an inspector (or a staff user with the Inspector role) before scheduling visits.') }}</p>
                @else
                    <form method="post" action="{{ route('sales-compliance.inspections.store') }}" class="space-y-5">
                        @csrf
                        @include('sales-compliance.partials.schedule-fields', ['inspection' => null, 'selectedSiteId' => $selectedSiteId])
                        <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                            <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Schedule visit') }}</button>
                            <a href="{{ route('sales-compliance.hub') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
