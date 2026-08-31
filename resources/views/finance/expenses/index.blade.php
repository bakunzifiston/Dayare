<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Operating expenses') }}</span>
    </x-slot>

    <div class="py-6 lg:py-8">
        <div class="max-w-[1400px] mx-auto space-y-4">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search number or description') }}" class="h-9 rounded-lg border border-slate-200 px-3 text-sm">
                        <select name="category" class="h-9 rounded-lg border border-slate-200 px-2 text-sm">
                            <option value="">{{ __('All categories') }}</option>
                            @foreach (\App\Models\FinanceExpense::CATEGORIES as $category)
                                <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ \App\Models\FinanceExpense::categoryLabel($category) }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="h-9 rounded-lg border border-slate-200 px-2 text-sm">
                            <option value="">{{ __('All payment states') }}</option>
                            @foreach (['paid' => __('Paid'), 'unpaid' => __('Unpaid'), 'pending' => __('Pending')] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="h-9 rounded-lg border border-slate-200 px-2 text-sm">
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="h-9 rounded-lg border border-slate-200 px-2 text-sm">
                        <button class="h-9 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white">{{ __('Filter') }}</button>
                    </form>
                    <a href="{{ route('finance.expenses.create') }}" class="h-9 inline-flex items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white">{{ __('New expense') }}</a>
                </div>
            </section>
            <section class="rounded-bucha border border-slate-200 bg-white overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="text-left px-4 py-2">{{ __('Date') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Reference') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Category') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Vendor') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Site') }}</th>
                                <th class="text-right px-4 py-2">{{ __('Amount') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Payment') }}</th>
                                <th class="text-right px-4 py-2">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expenses as $expense)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2">{{ optional($expense->expense_date)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $expense->expense_number }}</td>
                                    <td class="px-4 py-2">{{ \App\Models\FinanceExpense::categoryLabel($expense->category) }}</td>
                                    <td class="px-4 py-2">{{ $expense->supplier ? trim($expense->supplier->first_name.' '.$expense->supplier->last_name) : '—' }}</td>
                                    <td class="px-4 py-2">{{ $expense->facility?->facility_name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format((float) $expense->amount, 2) }}</td>
                                    <td class="px-4 py-2">{{ $expense->paymentStateLabel() }}</td>
                                    <td class="px-4 py-2 text-right"><a href="{{ route('finance.expenses.edit', $expense) }}" class="text-bucha-primary">{{ __('Edit') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">{{ __('No expenses recorded.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-slate-100">{{ $expenses->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
