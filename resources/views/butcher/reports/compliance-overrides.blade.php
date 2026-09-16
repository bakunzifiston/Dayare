@php
    $issueLabel = static function (string $issue): string {
        return match ($issue) {
            'temperature_breach' => __('Temperature breach'),
            'expired' => __('Expired'),
            default => $issue,
        };
    };
    $contextLabel = static function (string $type): string {
        return match ($type) {
            'cutting_session' => __('Cutting session'),
            'cutting_source' => __('Cutting source'),
            'sale' => __('Sale'),
            'order_fulfillment' => __('Order fulfillment'),
            default => $type,
        };
    };
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.reports.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Reports') }}
                </a>
            </div>

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="overrides_from" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('From') }}</label>
                            <input id="overrides_from" type="date" name="from" value="{{ $from }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="overrides_to" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('To') }}</label>
                            <input id="overrides_to" type="date" name="to" value="{{ $to }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.reports.compliance-overrides') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Overrides shown')" :value="(string) $report['total']" tone="amber" icon="ti ti-alert-triangle" />
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Compliance overrides') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3 sm:px-5">{{ __('When') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Context') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Batch') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Issues') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Reason') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('By') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['rows'] as $row)
                                <tr class="border-b border-slate-50">
                                    <td class="px-4 py-3 sm:px-5 whitespace-nowrap text-slate-700">{{ $row['overridden_at'] ?? '—' }}</td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="font-medium text-slate-900">{{ $contextLabel($row['context_type']) }}</div>
                                        <div class="text-xs text-slate-500">#{{ $row['context_id'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <span class="font-medium text-slate-900">{{ $row['batch_number'] ?? '—' }}</span>
                                        @if (! empty($row['cut_type']))
                                            <div class="text-xs text-slate-500">{{ $row['cut_type'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 sm:px-5">
                                        @foreach ($row['issues'] as $issue)
                                            <span class="mr-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-red-800">{{ $issueLabel($issue) }}</span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 max-w-xs text-slate-700">{{ $row['reason'] }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $row['overridden_by'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-slate-500 sm:px-5">{{ __('No compliance overrides in this period.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
