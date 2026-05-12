<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-slate-800">{{ __('Record payment') }}</h2></x-slot>
    <div class="max-w-3xl space-y-6">
        @include('farmer.sales.partials.nav')
        <div class="rounded-bucha border border-slate-200 bg-white p-4 text-sm">
            <p class="font-medium">{{ $sale->sale_number }}</p>
            <p class="text-slate-500">{{ __('Outstanding balance: :amount', ['amount' => number_format($sale->remainingBalance(), 2).' '.$sale->currency]) }}</p>
        </div>
        <form method="POST" action="{{ route('farmer.sales.payments.store', $sale) }}" class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            @csrf
            <div>
                <x-input-label for="payment_date" :value="__('Payment date')" />
                <x-text-input id="payment_date" name="payment_date" type="date" class="mt-1 block w-full" :value="old('payment_date', now()->toDateString())" required />
            </div>
            <div>
                <x-input-label for="payment_method" :value="__('Payment method')" />
                <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach (\App\Models\Sale::PAYMENT_METHODS as $method)
                        <option value="{{ $method }}">{{ __(ucwords(str_replace('_', ' ', $method))) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="amount_paid" :value="__('Amount paid')" />
                <x-text-input id="amount_paid" name="amount_paid" type="number" step="0.01" min="0.01" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="transaction_reference" :value="__('Transaction reference')" />
                <x-text-input id="transaction_reference" name="transaction_reference" type="text" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="notes" :value="__('Notes')" />
                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300"></textarea>
            </div>
            <div class="flex gap-3">
                <x-primary-button>{{ __('Record payment') }}</x-primary-button>
                <a href="{{ route('farmer.sales.records.show', $sale) }}" class="text-sm text-slate-600 hover:underline">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
