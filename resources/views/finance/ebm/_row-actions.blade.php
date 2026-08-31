@php
    $invoice = $invoice ?? null;
    $ebm = $ebm ?? null;
    $actionClass = 'inline-flex h-8 items-center gap-1.5 px-2.5 text-xs font-medium transition';
@endphp
<div class="flex items-center justify-end gap-2">
    @if ($invoice || $ebm)
        <div class="inline-flex divide-x divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        @if ($invoice)
            <a href="{{ route('finance.invoices.show', ['invoice' => $invoice, 'from' => 'invoices']) }}" class="{{ $actionClass }} text-slate-700 hover:bg-slate-50">
                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                {{ __('View') }}
            </a>
        @endif
        @if ($ebm)
            <a href="{{ route('finance.ebm.edit', $ebm) }}" class="{{ $actionClass }} text-slate-700 hover:bg-slate-50">
                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                {{ __('Edit') }}
            </a>
            <form method="POST" action="{{ route('finance.ebm.destroy', $ebm) }}" class="flex" onsubmit="return confirm(@js(__('Delete this EBM record?')))">
                @csrf
                @method('DELETE')
                <button type="submit" class="{{ $actionClass }} text-red-700 hover:bg-red-50">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10"/></svg>
                    {{ __('Delete') }}
                </button>
            </form>
        @endif
        </div>
    @endif
    @if ($invoice && ! $ebm)
        <a href="{{ route('finance.ebm.create', ['finance_invoice_id' => $invoice->id]) }}" class="inline-flex h-8 items-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 text-xs font-medium text-amber-900 hover:bg-amber-100">{{ __('Add EBM') }}</a>
    @endif
</div>
