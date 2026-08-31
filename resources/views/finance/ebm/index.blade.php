<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('EBM invoices') }}</span>
    </x-slot>
    <div class="py-6 lg:py-8">
        <div class="max-w-[1400px] mx-auto space-y-4">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            <section class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div class="rounded-bucha border border-slate-200 bg-white px-3 py-3">
                    <p class="text-xs text-slate-500">{{ __('Matched') }}</p>
                    <p class="text-lg font-semibold">{{ $summary['matched'] }}</p>
                </div>
                <div class="rounded-bucha border border-amber-200 bg-white px-3 py-3">
                    <p class="text-xs text-slate-500">{{ __('Missing EBM') }}</p>
                    <p class="text-lg font-semibold text-amber-800">{{ $summary['missing_ebm'] }}</p>
                </div>
                <div class="rounded-bucha border border-slate-200 bg-white px-3 py-3">
                    <p class="text-xs text-slate-500">{{ __('Orphan EBM') }}</p>
                    <p class="text-lg font-semibold">{{ $summary['orphan_ebm'] }}</p>
                </div>
                <div class="rounded-bucha border border-slate-200 bg-white px-3 py-3">
                    <p class="text-xs text-slate-500">{{ __('Amount mismatch') }}</p>
                    <p class="text-lg font-semibold">{{ $summary['amount_mismatch'] }}</p>
                </div>
                <div class="rounded-bucha border border-red-200 bg-white px-3 py-3">
                    <p class="text-xs text-slate-500">{{ __('Needs follow-up') }}</p>
                    <p class="text-lg font-semibold text-red-800">{{ $summary['follow_up'] }}</p>
                </div>
            </section>
            <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <select name="state" class="h-9 rounded-lg border border-slate-200 px-2 text-sm">
                        <option value="">{{ __('All reconciliation states') }}</option>
                        @foreach ([
                            \App\Models\FinanceEbmRecord::RECON_MATCHED => __('Matched'),
                            \App\Models\FinanceEbmRecord::RECON_MISSING_EBM => __('Missing EBM'),
                            \App\Models\FinanceEbmRecord::RECON_ORPHAN_EBM => __('Orphan EBM'),
                            \App\Models\FinanceEbmRecord::RECON_AMOUNT_MISMATCH => __('Amount mismatch'),
                            \App\Models\FinanceEbmRecord::RECON_REFERENCE_MISMATCH => __('Reference mismatch'),
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['state'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="h-9 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white">{{ __('Filter') }}</button>
                </form>
                <a href="{{ route('finance.ebm.create') }}" class="h-9 inline-flex items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white">{{ __('Add EBM record') }}</a>
            </section>
            <section class="rounded-bucha border border-slate-200 bg-white overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-2">{{ __('State') }}</th>
                            <th class="text-left px-4 py-2">{{ __('Sale / invoice') }}</th>
                            <th class="text-right px-4 py-2">{{ __('Invoice amount') }}</th>
                            <th class="text-left px-4 py-2">{{ __('EBM invoice') }}</th>
                            <th class="text-left px-4 py-2">{{ __('EBM receipt') }}</th>
                            <th class="text-right px-4 py-2">{{ __('EBM amount') }}</th>
                            <th class="text-right px-4 py-2">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-2 font-medium">{{ str_replace('_', ' ', $row['state']) }}</td>
                                <td class="px-4 py-2">
                                    @if ($row['invoice'])
                                        <a href="{{ route('finance.invoices.edit', $row['invoice']) }}" class="text-bucha-primary">{{ $row['invoice']->invoice_number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right">{{ $row['invoice'] ? number_format((float) $row['invoice']->total_amount, 2) : '—' }}</td>
                                <td class="px-4 py-2">{{ $row['ebm']?->ebm_invoice_number ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $row['ebm']?->ebm_receipt_number ?? '—' }}</td>
                                <td class="px-4 py-2 text-right">{{ $row['ebm'] && $row['ebm']->amount !== null ? number_format((float) $row['ebm']->amount, 2) : '—' }}</td>
                                <td class="px-4 py-2 text-right">
                                    @if ($row['ebm'])
                                        <a href="{{ route('finance.ebm.edit', $row['ebm']) }}" class="text-bucha-primary">{{ __('Edit') }}</a>
                                    @elseif ($row['invoice'])
                                        <a href="{{ route('finance.ebm.create', ['finance_invoice_id' => $row['invoice']->id]) }}" class="text-bucha-primary">{{ __('Add EBM') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ __('No EBM reconciliation rows.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>
