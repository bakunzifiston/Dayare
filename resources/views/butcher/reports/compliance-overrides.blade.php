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
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.reports.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Reports') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Compliance overrides') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('Audit log of Manager/Owner overrides for breached or expired stock.') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="get" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="from" class="text-xs font-semibold uppercase text-slate-500">{{ __('From') }}</label>
                    <input id="from" type="date" name="from" value="{{ $from }}" class="mt-1 block rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                </div>
                <div>
                    <label for="to" class="text-xs font-semibold uppercase text-slate-500">{{ __('To') }}</label>
                    <input id="to" type="date" name="to" value="{{ $to }}" class="mt-1 block rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                </div>
            </form>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Overrides shown')" :value="$report['total']" />
            </div>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4">{{ __('When') }}</th>
                            <th class="py-2 pr-4">{{ __('Context') }}</th>
                            <th class="py-2 pr-4">{{ __('Batch') }}</th>
                            <th class="py-2 pr-4">{{ __('Issues') }}</th>
                            <th class="py-2 pr-4">{{ __('Reason') }}</th>
                            <th class="py-2">{{ __('By') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4 whitespace-nowrap">{{ $row['overridden_at'] ?? '—' }}</td>
                                <td class="py-3 pr-4">
                                    <div class="font-medium text-slate-900">{{ $contextLabel($row['context_type']) }}</div>
                                    <div class="text-xs text-slate-500">#{{ $row['context_id'] }}</div>
                                </td>
                                <td class="py-3 pr-4">
                                    {{ $row['batch_number'] ?? '—' }}
                                    @if (! empty($row['cut_type']))
                                        <div class="text-xs text-slate-500">{{ $row['cut_type'] }}</div>
                                    @endif
                                </td>
                                <td class="py-3 pr-4">
                                    @foreach ($row['issues'] as $issue)
                                        <span class="mr-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-red-800">{{ $issueLabel($issue) }}</span>
                                    @endforeach
                                </td>
                                <td class="py-3 pr-4 max-w-xs">{{ $row['reason'] }}</td>
                                <td class="py-3">{{ $row['overridden_by'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500">{{ __('No compliance overrides in this period.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>
