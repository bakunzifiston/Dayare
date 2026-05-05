@php
    use App\Http\Controllers\Finance\FinancePayableController;
    $t = $activeTab ?? FinancePayableController::TAB_SUPPLIERS;
    $filters = $filters ?? ['status' => '', 'q' => ''];
    $filterQuery = array_filter([
        'status' => $filters['status'] ?? null,
        'q' => $filters['q'] ?? null,
    ], fn ($v) => $v !== null && $v !== '');
@endphp
<nav class="flex flex-wrap gap-2 border-b border-slate-200 pb-3 mb-4" aria-label="{{ __('Accounts payable sections') }}">
    <a href="{{ route('finance.payables.index', array_merge($filterQuery, ['tab' => FinancePayableController::TAB_SUPPLIERS])) }}"
        class="rounded-lg px-3 py-2 text-sm font-semibold {{ $t === FinancePayableController::TAB_SUPPLIERS ? 'bg-bucha-primary text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
        {{ __('Suppliers') }}
    </a>
    <a href="{{ route('finance.payables.index', array_merge($filterQuery, ['tab' => FinancePayableController::TAB_EMPLOYEES])) }}"
        class="rounded-lg px-3 py-2 text-sm font-semibold {{ $t === FinancePayableController::TAB_EMPLOYEES ? 'bg-bucha-primary text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
        {{ __('Employees') }}
    </a>
    <a href="{{ route('finance.payables.index', array_merge($filterQuery, ['tab' => FinancePayableController::TAB_CASUAL])) }}"
        class="rounded-lg px-3 py-2 text-sm font-semibold {{ $t === FinancePayableController::TAB_CASUAL ? 'bg-bucha-primary text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
        {{ __('Casual workers') }}
    </a>
    <a href="{{ route('finance.casual-workers.index') }}" class="ml-auto text-sm text-bucha-primary hover:underline self-center">{{ __('Casual worker registry') }}</a>
</nav>
