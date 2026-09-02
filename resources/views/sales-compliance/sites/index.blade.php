<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Compliance') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
            <form method="get" action="{{ route('sales-compliance.sites.index') }}" class="flex flex-wrap items-center gap-2">
                <select name="site_type" class="h-9 rounded-lg border-slate-200 text-sm">
                    <option value="">{{ __('All site types') }}</option>
                    @foreach (\App\Support\SalesComplianceCatalog::siteTypeLabels() as $value => $label)
                        <option value="{{ $value }}" @selected($filters['site_type'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Apply') }}</button>
                <a href="{{ route('sales-compliance.hub') }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Dashboard') }}</a>
                <a href="{{ route('sales-compliance.sites.create') }}" class="ml-auto inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Add site') }}</a>
            </form>
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Sites') }}</p>
                <p class="text-xs text-slate-500">{{ trans_choice(':count record|:count records', $sites->total(), ['count' => number_format($sites->total())]) }}</p>
            </div>
            @if ($sites->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-medium text-slate-800">{{ __('No sites yet') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Add restaurants, bars, butcheries, or private events before scheduling visits.') }}</p>
                    <a href="{{ route('sales-compliance.sites.create') }}" class="mt-4 inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Add site') }}</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5">{{ __('Name') }}</th>
                                <th class="px-4 py-2.5">{{ __('Type') }}</th>
                                <th class="px-4 py-2.5">{{ __('Location') }}</th>
                                <th class="px-4 py-2.5">{{ __('Contact') }}</th>
                                <th class="px-4 py-2.5">{{ __('Status') }}</th>
                                <th class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sites as $site)
                                <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                                    <td class="px-4 py-2.5 font-medium text-slate-900">{{ $site->name }}</td>
                                    <td class="px-4 py-2.5 text-slate-700">{{ $site->siteTypeLabel() }}</td>
                                    <td class="px-4 py-2.5 text-slate-700">{{ $site->location_address }}</td>
                                    <td class="px-4 py-2.5 text-slate-700">{{ $site->contact_name ?: '—' }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $site->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $site->is_active ? __('Active') : __('Inactive') }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('sales-compliance.sites.show', $site) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('View') }}</a>
                                            <a href="{{ route('sales-compliance.sites.edit', $site) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                                            <a href="{{ route('sales-compliance.inspections.create', ['site_id' => $site->id]) }}" class="inline-flex h-8 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Schedule') }}</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">{{ $sites->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
