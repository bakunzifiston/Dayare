<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Compliance') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3" aria-label="{{ __('Filters and actions') }}">
            <form method="get" action="{{ route('sales-compliance.hub') }}" class="flex flex-wrap items-center gap-2">
                <select name="assignee" class="h-9 rounded-lg border-slate-200 text-sm" aria-label="{{ __('Inspector') }}">
                    <option value="">{{ __('All inspectors') }}</option>
                    @foreach ($inspectors as $inspector)
                        <option value="inspector:{{ $inspector->id }}" @selected($filters['assignee'] === 'inspector:'.$inspector->id)>{{ $inspector->full_name }}</option>
                    @endforeach
                    @foreach ($inspectorUsers as $user)
                        <option value="user:{{ $user->id }}" @selected($filters['assignee'] === 'user:'.$user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                <select name="site_id" class="h-9 rounded-lg border-slate-200 text-sm" aria-label="{{ __('Site') }}">
                    <option value="">{{ __('All sites') }}</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}" @selected((string) $filters['site_id'] === (string) $site->id)>{{ $site->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="h-9 rounded-lg border-slate-200 text-sm" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (\App\Support\SalesComplianceCatalog::statusLabels() as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ $filters['from'] }}" class="h-9 rounded-lg border-slate-200 text-sm" aria-label="{{ __('From') }}">
                <span class="text-xs text-slate-400">–</span>
                <input type="date" name="to" value="{{ $filters['to'] }}" class="h-9 rounded-lg border-slate-200 text-sm" aria-label="{{ __('To') }}">
                <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Apply') }}</button>
                <a href="{{ route('sales-compliance.hub') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Clear') }}</a>
                <div class="ml-auto flex flex-wrap items-center gap-2">
                    <a href="{{ route('sales-compliance.sites.index') }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Sites') }}</a>
                    <a href="{{ route('sales-compliance.rules.index') }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Certificate rules') }}</a>
                    <a href="{{ route('sales-compliance.inspections.create') }}" class="inline-flex h-9 shrink-0 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Schedule visit') }}</a>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Compliance summary') }}">
            <x-kpi-card stat compact color="slate" :title="__('Upcoming')" :value="$kpis['upcoming']" glyph="calendar" :href="route('sales-compliance.hub', array_merge(request()->except('view', 'upcoming_page', 'completed_page'), ['view' => 'upcoming']))" />
            <x-kpi-card stat compact color="bucha-success" :title="__('Completed')" :value="$kpis['completed']" glyph="check" :href="route('sales-compliance.hub', array_merge(request()->except('view', 'upcoming_page', 'completed_page'), ['view' => 'completed']))" />
            <x-kpi-card stat compact color="amber" :title="__('Follow-up')" :value="$kpis['follow_up']" glyph="clipboard-list" :href="route('sales-compliance.hub', array_merge(request()->except('view'), ['view' => 'follow_up']))" />
            <x-kpi-card stat compact color="bucha" :title="__('Missing certificates')" :value="$kpis['missing_certs']" glyph="certificate" :href="route('sales-compliance.hub', array_merge(request()->except('view'), ['view' => 'missing']))" />
            <x-kpi-card stat compact color="bucha" :title="__('Repeat non-compliant')" :value="$kpis['repeat']" glyph="shield" :href="route('sales-compliance.hub', array_merge(request()->except('view'), ['view' => 'repeat']))" />
            <x-kpi-card stat compact color="amber" :title="__('Open escalations')" :value="$kpis['open_escalations']" glyph="clipboard" :href="route('sales-compliance.hub', array_merge(request()->except('view'), ['view' => 'escalations']))" />
            <x-kpi-card stat compact color="slate" :title="__('Failed')" :value="$kpis['failed']" glyph="clipboard-list" />
            <x-kpi-card stat compact color="slate" :title="__('Sites')" :value="$kpis['sites']" glyph="building" :href="route('sales-compliance.sites.index')" />
        </section>

        @php $view = $filters['view'] ?: 'upcoming'; @endphp

        @if (in_array($view, ['upcoming', 'completed'], true))
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Upcoming inspections') }}</p>
                    <p class="text-xs text-slate-500">{{ trans_choice(':count record|:count records', $upcoming->total(), ['count' => number_format($upcoming->total())]) }}</p>
                </div>
                @include('sales-compliance.partials.inspection-table', ['rows' => $upcoming, 'empty' => __('No upcoming inspections.')])
            </section>

            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Completed inspections') }}</p>
                    <p class="text-xs text-slate-500">{{ trans_choice(':count record|:count records', $completed->total(), ['count' => number_format($completed->total())]) }}</p>
                </div>
                @include('sales-compliance.partials.inspection-table', ['rows' => $completed, 'empty' => __('No completed inspections.')])
            </section>
        @endif

        @if ($view === 'follow_up')
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Requires follow-up') }}</p>
                    <p class="text-xs text-slate-500">{{ __('Failed, overdue, or repeatedly non-compliant sites.') }}</p>
                </div>
                @include('sales-compliance.partials.inspection-table', ['rows' => $followUp, 'empty' => __('No follow-up cases.')])
            </section>
        @endif

        @if ($view === 'missing')
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Sites with missing certificates') }}</p>
                </div>
                @include('sales-compliance.partials.inspection-table', ['rows' => $missingCertificates, 'empty' => __('No missing certificates.')])
            </section>
        @endif

        @if ($view === 'repeat')
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Repeatedly non-compliant sites') }}</p>
                    <p class="text-xs text-slate-500">{{ __('Two or more failed inspections.') }}</p>
                </div>
                @if ($repeatNonCompliant->isEmpty())
                    <div class="px-6 py-10 text-center">
                        <p class="text-sm font-medium text-slate-800">{{ __('No repeat non-compliance flags.') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5">{{ __('Site') }}</th>
                                    <th class="px-4 py-2.5">{{ __('Type') }}</th>
                                    <th class="px-4 py-2.5">{{ __('Failed inspections') }}</th>
                                    <th class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($repeatNonCompliant as $site)
                                    <tr class="border-t border-slate-100">
                                        <td class="px-4 py-2.5 text-slate-800">{{ $site->name }}</td>
                                        <td class="px-4 py-2.5 text-slate-700">{{ $site->siteTypeLabel() }}</td>
                                        <td class="px-4 py-2.5 tabular-nums text-red-700">{{ $site->failed_inspections_count }}</td>
                                        <td class="px-4 py-2.5 text-right">
                                            <a href="{{ route('sales-compliance.sites.show', $site) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('View') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif

        @if ($view === 'escalations')
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Escalated cases') }}</p>
                    <a href="{{ route('sales-compliance.escalations.create') }}" class="inline-flex h-8 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Open escalation') }}</a>
                </div>
                @if ($escalations->isEmpty())
                    <div class="px-6 py-10 text-center">
                        <p class="text-sm font-medium text-slate-800">{{ __('No escalations yet.') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5">{{ __('Site') }}</th>
                                    <th class="px-4 py-2.5">{{ __('Reason') }}</th>
                                    <th class="px-4 py-2.5">{{ __('Status') }}</th>
                                    <th class="px-4 py-2.5">{{ __('Opened by') }}</th>
                                    <th class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($escalations as $escalation)
                                    <tr class="border-t border-slate-100">
                                        <td class="px-4 py-2.5 text-slate-800">{{ $escalation->site->name ?? '—' }}</td>
                                        <td class="px-4 py-2.5 text-slate-700">{{ $escalation->reason }}</td>
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ \App\Support\SalesComplianceCatalog::escalationBadgeClass($escalation->status) }}">{{ $escalation->statusLabel() }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-slate-700">{{ $escalation->createdBy->name ?? '—' }}</td>
                                        <td class="px-4 py-2.5 text-right">
                                            <a href="{{ route('sales-compliance.escalations.show', $escalation) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('View') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif

        @if (! in_array($view, ['upcoming', 'completed', 'follow_up', 'missing', 'repeat', 'escalations'], true))
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="px-6 py-10 text-center">
                    <p class="text-sm font-medium text-slate-800">{{ __('Choose a dashboard view from the summary cards.') }}</p>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
