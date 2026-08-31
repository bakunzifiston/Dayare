@php
    $t = $activeTab ?? \App\Http\Controllers\Finance\FinancePayableController::TAB_SUPPLIERS;
    $filters = $filters ?? ['status' => '', 'payment_state' => '', 'q' => ''];
    $filterQuery = array_filter([
        'status' => $filters['status'] ?? null,
        'payment_state' => $filters['payment_state'] ?? null,
        'q' => $filters['q'] ?? null,
    ], fn ($v) => $v !== null && $v !== '');
    $tabClass = 'rounded-md px-3 py-1.5 text-xs font-medium transition';
@endphp
<div class="flex flex-wrap items-center justify-between gap-2">
    <nav class="inline-flex flex-wrap rounded-lg border border-slate-200 bg-slate-50 p-0.5" aria-label="{{ __('Accounts payable sections') }}">
        <a href="{{ route('finance.payables.index', array_merge($filterQuery, ['tab' => \App\Http\Controllers\Finance\FinancePayableController::TAB_SUPPLIERS])) }}"
            class="{{ $tabClass }} {{ $t === \App\Http\Controllers\Finance\FinancePayableController::TAB_SUPPLIERS ? 'bg-bucha-primary text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
            {{ __('Suppliers') }}
        </a>
        <a href="{{ route('finance.payables.index', array_merge($filterQuery, ['tab' => \App\Http\Controllers\Finance\FinancePayableController::TAB_EMPLOYEES])) }}"
            class="{{ $tabClass }} {{ $t === \App\Http\Controllers\Finance\FinancePayableController::TAB_EMPLOYEES ? 'bg-bucha-primary text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
            {{ __('Employees') }}
        </a>
        <a href="{{ route('finance.payables.index', array_merge($filterQuery, ['tab' => \App\Http\Controllers\Finance\FinancePayableController::TAB_CASUAL])) }}"
            class="{{ $tabClass }} {{ $t === \App\Http\Controllers\Finance\FinancePayableController::TAB_CASUAL ? 'bg-bucha-primary text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
            {{ __('Casual workers') }}
        </a>
    </nav>
    <a href="{{ route('finance.casual-workers.index') }}" class="text-xs font-medium text-slate-500 hover:text-bucha-primary">{{ __('Casual worker registry') }}</a>
</div>
