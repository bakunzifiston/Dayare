@php
    $chain = $result['chain'] ?? [];
    $movements = $result['movements'] ?? [];
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
                <form method="get" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <label for="trace_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Batch or sale number') }}</label>
                        <input id="trace_q" type="text" name="q" value="{{ $result['query'] ?? '' }}" placeholder="BATCH-… or SALE-…" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary" autofocus>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti ti-search text-base leading-none" aria-hidden="true"></i>
                        {{ __('Trace') }}
                    </button>
                </form>
            </section>

            @if (! empty($result['error']))
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ $result['error'] }}
                </div>
            @endif

            @if (! empty($result['found']))
                <section class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100">
                                <i class="ti ti-route text-lg leading-none" aria-hidden="true"></i>
                            </span>
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Chain') }}</h3>
                        </div>
                        <span class="text-xs uppercase tracking-wide text-slate-500">
                            {{ ($result['mode'] ?? '') === 'sale' ? __('Sale lookup') : __('Batch lookup') }}
                        </span>
                    </div>

                    <ol class="mt-4 space-y-3">
                        @foreach ($chain as $index => $step)
                            <li class="flex gap-3">
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-700">{{ $index + 1 }}</span>
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $step['label'] }}</div>
                                    <div class="font-semibold text-slate-900">{{ $step['reference'] }}</div>
                                    @if (! empty($step['detail']))
                                        <div class="text-sm text-slate-600">{{ $step['detail'] }}</div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>

                @if (! empty($movements))
                    <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Related ledger movements') }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                                        <th class="px-4 py-3 sm:px-5">{{ __('When') }}</th>
                                        <th class="px-4 py-3 sm:px-5">{{ __('Type') }}</th>
                                        <th class="px-4 py-3 text-right sm:px-5">{{ __('Qty (kg)') }}</th>
                                        <th class="px-4 py-3 sm:px-5">{{ __('Batch') }}</th>
                                        <th class="px-4 py-3 sm:px-5">{{ __('Cut output') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($movements as $m)
                                        <tr class="border-b border-slate-50">
                                            <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $m['occurred_at'] ?? '—' }}</td>
                                            <td class="px-4 py-3 sm:px-5 font-medium text-slate-900">{{ $m['type'] }}</td>
                                            <td class="px-4 py-3 text-right tabular-nums text-slate-700 sm:px-5">{{ number_format((float) $m['quantity_kg'], 3) }}</td>
                                            <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $m['batch_id'] ?? '—' }}</td>
                                            <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $m['cut_output_id'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
