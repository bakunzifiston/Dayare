<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.compliance.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Compliance') }}
                </a>
            </div>

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="compliance_from" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('From') }}</label>
                            <input id="compliance_from" type="date" name="from" value="{{ $from }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="compliance_to" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('To') }}</label>
                            <input id="compliance_to" type="date" name="to" value="{{ $to }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.compliance.report') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a href="{{ route('butcher.compliance.report.export', ['from' => $from, 'to' => $to]) }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                            <i class="ti ti-download text-base leading-none" aria-hidden="true"></i>
                            {{ __('Export CSV') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Hygiene logs')" :value="(string) $report['hygiene_total']" tone="bucha" icon="ti ti-clipboard-check" />
                <x-butcher.kpi-card :label="__('Pass rate')" :value="number_format((float) $report['hygiene_pass_rate'], 1).'%'" tone="emerald" icon="ti ti-percentage" />
                <x-butcher.kpi-card :label="__('Sanitation records')" :value="(string) $report['sanitation_total']" tone="sky" icon="ti ti-droplet" />
                <x-butcher.kpi-card :label="__('Health alerts')" :value="(string) $report['health_expiring_30d']" tone="rose" icon="ti ti-heartbeat" />
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <section class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Hygiene summary') }}</h3>
                    <dl class="mt-4 grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <dt class="text-slate-500">{{ __('Pass') }}</dt>
                            <dd class="mt-1 text-lg font-bold text-emerald-700">{{ $report['hygiene_pass_count'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">{{ __('Partial') }}</dt>
                            <dd class="mt-1 text-lg font-bold text-amber-700">{{ $report['hygiene_partial_count'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">{{ __('Fail') }}</dt>
                            <dd class="mt-1 text-lg font-bold text-red-700">{{ $report['hygiene_fail_count'] }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Permits & certifications') }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ __(':count permit(s) expiring within 60 days.', ['count' => $report['permits_expiring_60d']]) }}</p>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($report['permits'] as $permit)
                            <li class="flex justify-between gap-3 rounded-lg border border-slate-100 px-3 py-2">
                                <span class="font-medium text-slate-900">{{ str_replace('_', ' ', ucfirst($permit->permit_type)) }}</span>
                                <span class="tabular-nums text-slate-600">{{ $permit->expiry_date?->toDateString() ?? '—' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Hygiene logs in period') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3 sm:px-5">{{ __('Date') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Outlet') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['hygiene_logs'] as $log)
                                <tr class="border-b border-slate-50">
                                    <td class="px-4 py-3 sm:px-5 font-medium text-slate-900">{{ $log->log_date?->toDateString() }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $log->outlet?->name }}</td>
                                    <td class="px-4 py-3 sm:px-5"><x-butcher.status-badge :status="$log->status" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-10 text-center text-slate-500 sm:px-5">{{ __('No logs in this period.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
