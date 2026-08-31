@php
    $from = $from ?? 'invoices';
    $invoice = $invoice ?? null;
    $showView = $showView ?? true;
@endphp
@if ($invoice)
    <div class="inline-flex items-center justify-end gap-2 whitespace-nowrap text-sm">
        @if ($showView)
            <a href="{{ route('finance.invoices.show', ['invoice' => $invoice, 'from' => $from]) }}" class="font-medium text-bucha-primary hover:underline">{{ __('View') }}</a>
        @endif
        <a href="{{ route('finance.invoices.edit', $invoice) }}" class="font-medium text-bucha-primary hover:underline">{{ __('Edit') }}</a>
        <form method="POST" action="{{ route('finance.invoices.destroy', $invoice) }}" class="inline" onsubmit="return confirm(@js(__('Delete this invoice? Linked payments and EBM records will also be removed.')))">
            @csrf
            @method('DELETE')
            <input type="hidden" name="from" value="{{ $from }}">
            <button type="submit" class="font-medium text-red-700 hover:underline">{{ __('Delete') }}</button>
        </form>
    </div>
@endif
