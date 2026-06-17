@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Customers') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Profiles, tiers, and outstanding credit balances.') }}</p>
            </div>
            <a href="{{ route('butcher.sales.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Back to sales') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Add customer') }}</h3>
                <form method="post" action="{{ route('butcher.sales.customers.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-5">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Name') }}</label>
                        <input name="name" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Phone') }}</label>
                        <input name="phone" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Tier') }}</label>
                        <select name="tier" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($tiers as $tier)
                                <option value="{{ $tier }}">{{ ucfirst($tier) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Credit limit') }}</label>
                        <input name="credit_limit" type="number" min="0" value="0" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Add') }}</button>
                    </div>
                </form>
            </section>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4">{{ __('Name') }}</th>
                            <th class="py-2 pr-4">{{ __('Phone') }}</th>
                            <th class="py-2 pr-4">{{ __('Tier') }}</th>
                            <th class="py-2 pr-4">{{ __('Credit limit') }}</th>
                            <th class="py-2">{{ __('Outstanding') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4 font-medium">{{ $customer->name }}</td>
                                <td class="py-3 pr-4">{{ $customer->phone }}</td>
                                <td class="py-3 pr-4">{{ ucfirst($customer->tier) }}</td>
                                <td class="py-3 pr-4">{{ $fmtMoney($customer->credit_limit) }}</td>
                                <td class="py-3 @if((float)$customer->outstanding_balance > 0) text-amber-800 font-semibold @endif">{{ $fmtMoney($customer->outstanding_balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-500">{{ __('No customers yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $customers->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
