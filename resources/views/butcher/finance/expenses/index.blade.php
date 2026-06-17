@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Operating expenses') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Rent, wages, utilities, and other costs.') }}</p>
            </div>
            <a href="{{ route('butcher.finance.expenses.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Log expense') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <form method="get" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="text-xs font-semibold uppercase text-slate-500">{{ __('From') }}</label>
                    <input type="date" name="from" value="{{ $from }}" class="mt-1 block rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase text-slate-500">{{ __('To') }}</label>
                    <input type="date" name="to" value="{{ $to }}" class="mt-1 block rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                </div>
            </form>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-kpi-card stat :title="__('Total expenses')" :value="$fmtMoney($totalAmount)" />
                @foreach ($totalsByCategory as $category => $total)
                    <x-kpi-card stat :title="ucfirst($category)" :value="$fmtMoney($total)" />
                @endforeach
            </div>

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4">{{ __('Date') }}</th>
                            <th class="py-2 pr-4">{{ __('Category') }}</th>
                            <th class="py-2 pr-4">{{ __('Description') }}</th>
                            <th class="py-2 pr-4">{{ __('Amount') }}</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expenses as $expense)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4">{{ $expense->expense_date?->toDateString() }}</td>
                                <td class="py-3 pr-4">{{ ucfirst($expense->category) }}</td>
                                <td class="py-3 pr-4">{{ $expense->description }}</td>
                                <td class="py-3 pr-4 font-semibold">{{ $fmtMoney($expense->amount) }}</td>
                                <td class="py-3 text-right space-x-2">
                                    <a href="{{ route('butcher.finance.expenses.edit', $expense) }}" class="text-bucha-primary hover:underline">{{ __('Edit') }}</a>
                                    <form method="post" action="{{ route('butcher.finance.expenses.destroy', $expense) }}" class="inline" onsubmit="return confirm(@json(__('Delete this expense?')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-500">{{ __('No expenses in this period.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $expenses->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
