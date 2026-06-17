<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Compliance audit report') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('RFA inspection-ready summary for the selected period.') }}</p>
            </div>
            <a href="{{ route('butcher.compliance.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="get" class="flex flex-wrap items-end gap-3 rounded-bucha border border-slate-200/80 bg-white p-4 shadow-bucha">
                <div>
                    <label for="from" class="text-xs font-semibold uppercase text-slate-500">{{ __('From') }}</label>
                    <input id="from" type="date" name="from" value="{{ $from }}" class="mt-1 block rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label for="to" class="text-xs font-semibold uppercase text-slate-500">{{ __('To') }}</label>
                    <input id="to" type="date" name="to" value="{{ $to }}" class="mt-1 block rounded-lg border-gray-300 text-sm">
                </div>
                <button type="submit" class="rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Update range') }}</button>
                <a href="{{ route('butcher.compliance.report.export', ['from' => $from, 'to' => $to]) }}" class="rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Export CSV') }}</a>
            </form>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Hygiene logs')" :value="$report['hygiene_total']" />
                <x-kpi-card stat :title="__('Pass rate')" :value="number_format((float) $report['hygiene_pass_rate'], 1).'%'" />
                <x-kpi-card stat :title="__('Sanitation records')" :value="$report['sanitation_total']" />
                <x-kpi-card stat :title="__('Health alerts')" :value="$report['health_expiring_30d']" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Hygiene summary') }}</h3>
                    <dl class="mt-4 grid grid-cols-3 gap-4 text-sm">
                        <div><dt class="text-slate-500">{{ __('Pass') }}</dt><dd class="text-lg font-bold text-emerald-700">{{ $report['hygiene_pass_count'] }}</dd></div>
                        <div><dt class="text-slate-500">{{ __('Partial') }}</dt><dd class="text-lg font-bold text-amber-700">{{ $report['hygiene_partial_count'] }}</dd></div>
                        <div><dt class="text-slate-500">{{ __('Fail') }}</dt><dd class="text-lg font-bold text-red-700">{{ $report['hygiene_fail_count'] }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Permits & certifications') }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ __(':count permit(s) expiring within 60 days.', ['count' => $report['permits_expiring_60d']]) }}</p>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($report['permits'] as $permit)
                            <li class="flex justify-between rounded-lg border border-slate-200 px-3 py-2">
                                <span>{{ str_replace('_', ' ', ucfirst($permit->permit_type)) }}</span>
                                <span>{{ $permit->expiry_date?->toDateString() ?? '—' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Hygiene logs in period') }}</h3>
                <table class="mt-4 min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4">{{ __('Date') }}</th>
                            <th class="py-2 pr-4">{{ __('Outlet') }}</th>
                            <th class="py-2">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['hygiene_logs'] as $log)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-4">{{ $log->log_date?->toDateString() }}</td>
                                <td class="py-2 pr-4">{{ $log->outlet?->name }}</td>
                                <td class="py-2"><x-butcher.status-badge :status="$log->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-slate-500">{{ __('No logs in this period.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>
