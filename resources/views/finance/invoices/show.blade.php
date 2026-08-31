<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('View AR invoice') }}</span>
    </x-slot>

    @php
        $from = $from ?? 'invoices';
        $backRoute = $from === 'sales' ? route('finance.sales.index') : route('finance.invoices.index');
        $customer = $invoice->client?->name ?? $invoice->animalIntake?->clientSourceDisplayName() ?? '—';
        $paymentTone = match ($invoice->paymentState()) {
            'paid' => 'bg-emerald-50 text-emerald-800',
            'pending' => 'bg-amber-50 text-amber-900',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp

    <div class="py-6 lg:py-8">
        <div class="max-w-[1100px] mx-auto space-y-4">
            @if (session('status'))
                <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs text-slate-500">{{ __('Invoice') }}</p>
                        <p class="text-lg font-semibold text-slate-900">{{ $invoice->invoice_number }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $customer }} · {{ $invoice->facility?->facility_name ?? __('No site') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @include('finance.invoices._row-actions', ['invoice' => $invoice, 'from' => $from, 'showView' => false])
                        <a href="{{ $backRoute }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">{{ __('Back') }}</a>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">{{ __('Date') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($invoice->issued_at)->format('d M Y') ?? optional($invoice->created_at)->format('d M Y') }}</p>
                </div>
                <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">{{ __('Amount') }}</p>
                    <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">{{ number_format((float) $invoice->total_amount, 0) }} {{ $invoice->currency }}</p>
                </div>
                <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">{{ __('Payment') }}</p>
                    <p class="mt-1"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $paymentTone }}">{{ $invoice->paymentStateLabel() }}</span></p>
                </div>
                <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">{{ __('EBM') }}</p>
                    @if ($invoice->ebmRecord)
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $invoice->ebmRecord->ebm_receipt_number ?: $invoice->ebmRecord->ebm_invoice_number }}</p>
                    @else
                        <a href="{{ route('finance.ebm.create', ['finance_invoice_id' => $invoice->id]) }}" class="mt-1 inline-flex text-sm font-semibold text-amber-800 hover:underline">{{ __('Needs EBM') }}</a>
                    @endif
                </div>
            </section>

            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
                <p class="text-sm font-semibold text-slate-900">{{ __('Sale details') }}</p>
                <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Item') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $line?->description ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Quantity') }}</dt>
                        <dd class="mt-0.5 text-sm tabular-nums text-slate-900">
                            @if ($line)
                                {{ number_format((float) $line->quantity, 2) }} {{ $line->quantity_unit }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Unit price') }}</dt>
                        <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ $line ? number_format((float) $line->unit_price, 2).' '.$invoice->currency : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Document status') }}</dt>
                        <dd class="mt-0.5 text-sm capitalize text-slate-900">{{ $invoice->status }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Due date') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ optional($invoice->due_date)->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Paid') }}</dt>
                        <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ number_format((float) $invoice->amount_paid, 0) }} {{ $invoice->currency }}</dd>
                    </div>
                    @if ($invoice->notes)
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-slate-500">{{ __('Notes') }}</dt>
                            <dd class="mt-0.5 text-sm text-slate-900">{{ $invoice->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            @include('finance.partials.payment-panel', ['document' => $invoice, 'documentType' => 'invoice', 'readonly' => true])
        </div>
    </div>
</x-app-layout>
