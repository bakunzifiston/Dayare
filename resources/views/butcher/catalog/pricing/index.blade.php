@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Price rules') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Outlet-specific, tier, and promotional pricing.') }}</p>
            </div>
            <a href="{{ route('butcher.catalog.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Back to catalog') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Add price rule') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('Priority: outlet + tier → outlet → tier → default product price.') }}</p>
                <form method="post" action="{{ route('butcher.catalog.pricing.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
                    @csrf
                    <div class="lg:col-span-2">
                        <label for="product_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Product') }}</label>
                        <select id="product_id" name="product_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('product_id')" class="mt-1" />
                    </div>
                    <div>
                        <label for="outlet_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Outlet') }}</label>
                        <select id="outlet_id" name="outlet_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ __('All outlets') }}</option>
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="customer_tier" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customer tier') }}</label>
                        <select id="customer_tier" name="customer_tier" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ __('All tiers') }}</option>
                            @foreach ($tiers as $tier)
                                <option value="{{ $tier }}" @selected(old('customer_tier') === $tier)>{{ ucfirst($tier) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="price" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Price (RWF)') }}</label>
                        <input id="price" name="price" type="number" step="1" min="0" value="{{ old('price') }}" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <x-input-error :messages="$errors->get('price')" class="mt-1" />
                    </div>
                    <div>
                        <label for="valid_from" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Valid from') }}</label>
                        <input id="valid_from" name="valid_from" type="date" value="{{ old('valid_from', now()->toDateString()) }}" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label for="valid_until" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Valid until') }}</label>
                        <input id="valid_until" name="valid_until" type="date" value="{{ old('valid_until') }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <p class="mt-1 text-xs text-slate-500">{{ __('Leave empty for no expiry.') }}</p>
                    </div>
                    <div class="md:col-span-3 lg:col-span-6">
                        <button type="submit" class="rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Save rule') }}</button>
                    </div>
                </form>
            </section>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="py-2 pr-4">{{ __('Product') }}</th>
                            <th class="py-2 pr-4">{{ __('Scope') }}</th>
                            <th class="py-2 pr-4">{{ __('Price') }}</th>
                            <th class="py-2 pr-4">{{ __('Valid') }}</th>
                            <th class="py-2 pr-4">{{ __('Status') }}</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($priceRules as $rule)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4 font-medium">{{ $rule->product?->name }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $rule->labelDescription() }}</td>
                                <td class="py-3 pr-4 font-semibold">{{ $fmtMoney($rule->price) }}</td>
                                <td class="py-3 pr-4 text-xs text-slate-500">
                                    {{ $rule->valid_from?->toDateString() }}
                                    @if ($rule->valid_until)
                                        → {{ $rule->valid_until->toDateString() }}
                                    @else
                                        · {{ __('No end') }}
                                    @endif
                                </td>
                                <td class="py-3 pr-4">
                                    @if ($rule->isCurrentlyValid())
                                        <x-butcher.status-badge status="in_storage" />
                                    @else
                                        <x-butcher.status-badge status="expired" />
                                    @endif
                                </td>
                                <td class="py-3 text-right">
                                    <form method="post" action="{{ route('butcher.catalog.pricing.destroy', $rule) }}" onsubmit="return confirm(@json(__('Remove this price rule?')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">{{ __('Remove') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-slate-500">{{ __('No price rules yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $priceRules->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
