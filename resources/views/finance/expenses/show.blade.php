<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('View operating expense') }}</span>
    </x-slot>

    @php
        $paymentTone = match ($expense->paymentState()) {
            'paid' => 'bg-emerald-50 text-emerald-800',
            'pending' => 'bg-amber-50 text-amber-900',
            default => 'bg-slate-100 text-slate-700',
        };
        $vendor = $expense->supplier
            ? trim($expense->supplier->first_name.' '.$expense->supplier->last_name)
            : '—';
    @endphp

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="rounded-bucha border border-slate-200 bg-white px-5 py-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs text-slate-500">{{ __('Expense') }}</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $expense->expense_number }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $expense->description }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @include('finance.expenses._row-actions', ['expense' => $expense, 'showView' => false])
                    <a href="{{ route('finance.expenses.index') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs text-slate-500">{{ __('Date') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($expense->expense_date)->format('d M Y') ?? '—' }}</p>
            </div>
            <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs text-slate-500">{{ __('Amount') }}</p>
                <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">{{ number_format((float) $expense->amount, 0) }} {{ $expense->currency }}</p>
            </div>
            <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs text-slate-500">{{ __('Payment') }}</p>
                <p class="mt-1"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $paymentTone }}">{{ $expense->paymentStateLabel() }}</span></p>
            </div>
            <div class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs text-slate-500">{{ __('Category') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ \App\Models\FinanceExpense::categoryLabel($expense->category) }}</p>
            </div>
        </section>

        <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
            <p class="text-sm font-semibold text-slate-900">{{ __('Expense details') }}</p>
            <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Vendor') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $vendor !== '' ? $vendor : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Site') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $expense->facility?->facility_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Reference') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $expense->reference_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Paid') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ number_format((float) $expense->amount_paid, 0) }} {{ $expense->currency }}</dd>
                </div>
                @if ($expense->hasAttachment())
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Attachment') }}</dt>
                        <dd class="mt-0.5 text-sm">
                            <a href="{{ route('finance.expenses.attachment', $expense) }}" class="font-medium text-bucha-primary hover:underline">{{ __('Download') }}</a>
                        </dd>
                    </div>
                @endif
                @if ($expense->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500">{{ __('Notes') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $expense->notes }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        @include('finance.partials.payment-panel', ['document' => $expense, 'documentType' => 'expense', 'readonly' => true])
    </div>
</x-app-layout>
