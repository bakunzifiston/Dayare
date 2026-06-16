<div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
    <p class="font-medium">{{ __('Transport workflow') }}</p>
    <ol class="mt-2 list-decimal list-inside space-y-1 text-sky-800">
        <li>{{ __('Record trip') }} — {{ __('select an active certificate, then origin, destination, vehicle, and dates.') }}</li>
        <li>
            <a href="{{ route('delivery-confirmations.create') }}" class="font-medium text-bucha-primary hover:underline">{{ __('Confirm delivery') }}</a>
            — {{ __('quantity unit, customer contract, and international export documents (when the receiver is outside :country).', ['country' => config('processor.domestic_country', 'RW')]) }}
        </li>
        <li>{{ __('Export') }} — {{ __('CSV / PDF / traceability from the Transport hub or All trips list (requires export permission).') }}</li>
    </ol>
</div>
