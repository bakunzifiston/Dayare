@php
    $fmtMoney = static fn ($v): string => 'RWF '.number_format((float) $v, 0);
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.finance.expenses.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="expense_from" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('From') }}</label>
                            <input id="expense_from" type="date" name="from" value="{{ $from }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <div>
                            <label for="expense_to" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('To') }}</label>
                            <input id="expense_to" type="date" name="to" value="{{ $to }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.finance.expenses.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                        <a href="{{ route('butcher.finance.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Finance') }}</a>
                        <a href="{{ route('butcher.finance.expenses.create') }}" class="inline-flex items-center gap-1.5 rounded-bucha border border-bucha-primary/30 bg-bucha-primary/5 px-4 py-2 text-sm font-semibold text-bucha-burgundy hover:bg-bucha-primary/10">
                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                            {{ __('Log expense') }}
                        </a>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-butcher.kpi-card :label="__('Total expenses')" :value="$fmtMoney($totalAmount)" tone="bucha" icon="ti ti-receipt" />
                @foreach ($totalsByCategory as $category => $total)
                    <x-butcher.kpi-card :label="ucfirst($category)" :value="$fmtMoney($total)" tone="slate" icon="ti ti-tag" />
                @endforeach
            </div>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Expenses') }}</h3>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count expense|:count expenses', $expenses->total(), ['count' => $expenses->total()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Expense') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5">{{ __('Date') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Amount') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($expenses as $expense)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-receipt text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $expense->description }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ ucfirst($expense->category) }} · {{ $expense->expense_date?->toDateString() }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 tabular-nums text-slate-700">{{ $expense->expense_date?->toDateString() }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-right font-semibold tabular-nums text-slate-900">{{ $fmtMoney($expense->amount) }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <a href="{{ route('butcher.finance.expenses.edit', $expense) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                <i class="ti ti-pencil text-sm leading-none" aria-hidden="true"></i>
                                                {{ __('Edit') }}
                                            </a>
                                            <form method="post" action="{{ route('butcher.finance.expenses.destroy', $expense) }}" onsubmit="return confirm(@js(__('Delete this expense?')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">
                                                    <i class="ti ti-trash text-sm leading-none" aria-hidden="true"></i>
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center text-slate-500">{{ __('No expenses in this period.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($expenses->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $expenses->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
