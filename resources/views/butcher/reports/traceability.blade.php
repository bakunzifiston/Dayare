@php
    $chain = $result['chain'] ?? [];
    $movements = $result['movements'] ?? [];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.reports.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Reports') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Batch traceability') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('Look up a batch number or sale/receipt number to walk the full chain.') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="get" class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha flex flex-wrap items-end gap-3">
                <div class="min-w-[16rem] flex-1">
                    <label for="q" class="text-xs font-semibold uppercase text-slate-500">{{ __('Batch or sale number') }}</label>
                    <input id="q" type="text" name="q" value="{{ $result['query'] ?? '' }}" placeholder="BATCH-… or SALE-…" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" autofocus>
                </div>
                <button type="submit" class="inline-flex items-center rounded-lg bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                    {{ __('Trace') }}
                </button>
            </form>

            @if (! empty($result['error']))
                <div class="rounded-bucha border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ $result['error'] }}
                </div>
            @endif

            @if (! empty($result['found']))
                <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Chain') }}</h3>
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
                    <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Related ledger movements') }}</h3>
                        <table class="mt-4 min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                                    <th class="py-2 pr-4">{{ __('When') }}</th>
                                    <th class="py-2 pr-4">{{ __('Type') }}</th>
                                    <th class="py-2 pr-4 text-right">{{ __('Qty (kg)') }}</th>
                                    <th class="py-2 pr-4">{{ __('Batch') }}</th>
                                    <th class="py-2">{{ __('Cut output') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($movements as $m)
                                    <tr class="border-b border-slate-100">
                                        <td class="py-2 pr-4">{{ $m['occurred_at'] ?? '—' }}</td>
                                        <td class="py-2 pr-4">{{ $m['type'] }}</td>
                                        <td class="py-2 pr-4 text-right tabular-nums">{{ number_format((float) $m['quantity_kg'], 3) }}</td>
                                        <td class="py-2 pr-4">{{ $m['batch_id'] ?? '—' }}</td>
                                        <td class="py-2">{{ $m['cut_output_id'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
