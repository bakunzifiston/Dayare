<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-4 w-full"><h2 class="font-semibold text-xl text-slate-800">{{ $buyer->buyer_name }}</h2><a href="{{ route('farmer.sales.buyers.edit', $buyer) }}" class="text-sm font-medium text-bucha-primary hover:underline">{{ __('Edit') }}</a></div></x-slot>
    <div class="max-w-6xl space-y-6">
        @include('farmer.sales.partials.nav')
        <section class="grid gap-4 rounded-bucha border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2">
            <div><p class="text-xs uppercase text-slate-500">{{ __('Buyer code') }}</p><p class="font-mono text-sm">{{ $buyer->buyer_code }}</p></div>
            <div><p class="text-xs uppercase text-slate-500">{{ __('Type') }}</p><p class="capitalize">{{ str_replace('_', ' ', $buyer->buyer_type) }}</p></div>
            <div><p class="text-xs uppercase text-slate-500">{{ __('Phone') }}</p><p>{{ $buyer->phone ?: '—' }}</p></div>
            <div><p class="text-xs uppercase text-slate-500">{{ __('Email') }}</p><p>{{ $buyer->email ?: '—' }}</p></div>
            <div><p class="text-xs uppercase text-slate-500">{{ __('Trust level') }}</p><p class="capitalize">{{ str_replace('_', ' ', $buyer->trust_level) }}</p></div>
            <div><p class="text-xs uppercase text-slate-500">{{ __('Status') }}</p><x-sale-status-badge :status="$buyer->status" /></div>
            <div class="md:col-span-2"><p class="text-xs uppercase text-slate-500">{{ __('Address') }}</p><p>{{ $buyer->address ?: '—' }}</p></div>
        </section>
        <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent purchases') }}</h3>
            <div class="mt-4 divide-y divide-slate-100 text-sm">
                @forelse ($buyer->sales as $sale)
                    <div class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-medium">{{ $sale->sale_number }}</p>
                            <p class="text-slate-500">{{ $sale->sale_date?->toDateString() }}</p>
                        </div>
                        <a href="{{ route('farmer.sales.records.show', $sale) }}" class="text-bucha-primary hover:underline">{{ __('View sale') }}</a>
                    </div>
                @empty
                    <p class="py-4 text-slate-500">{{ __('No sales linked to this buyer yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
