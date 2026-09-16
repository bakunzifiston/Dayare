@php
    $isEdit = $expense !== null;
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.finance.expenses.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Expenses') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-receipt text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $isEdit ? __('Edit expense') : __('Log expense') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $isEdit ? __('Update operating expense details.') : __('Record an operating expense for P&L and cash flow.') }}</p>
                    </div>
                </div>
            </div>

            <form
                method="post"
                action="{{ $isEdit ? route('butcher.finance.expenses.update', $expense) : route('butcher.finance.expenses.store') }}"
                enctype="multipart/form-data"
                class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm"
            >
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Expense') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="category" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Category') }}</label>
                                <select id="category" name="category" required class="{{ $fieldClass }}">
                                    @foreach ($categories as $category)
                                        <option value="{{ $category }}" @selected(old('category', $expense?->category) === $category)>{{ ucfirst($category) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="outlet_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Outlet') }}</label>
                                <select id="outlet_id" name="outlet_id" class="{{ $fieldClass }}">
                                    <option value="">{{ __('All / head office') }}</option>
                                    @foreach ($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" @selected(old('outlet_id', $expense?->outlet_id) == $outlet->id)>{{ $outlet->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="description" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Description') }}</label>
                                <input id="description" name="description" type="text" required value="{{ old('description', $expense?->description) }}" class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label for="amount" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Amount (RWF)') }}</label>
                                <input id="amount" type="number" name="amount" min="0.01" step="1" required value="{{ old('amount', $expense?->amount) }}" class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label for="expense_date" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Date') }}</label>
                                <input id="expense_date" type="date" name="expense_date" required value="{{ old('expense_date', $expense?->expense_date?->toDateString() ?? now()->toDateString()) }}" class="{{ $fieldClass }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="payment_method" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Payment') }}</label>
                                <select id="payment_method" name="payment_method" required class="{{ $fieldClass }}">
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method }}" @selected(old('payment_method', $expense?->payment_method) === $method)>{{ str_replace('_', ' ', ucfirst($method)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="receipt" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Receipt (optional)') }}</label>
                                <input id="receipt" type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-bucha file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                                @if ($expense?->receipt_path)
                                    <p class="mt-2 text-xs"><a href="{{ $expense->receiptUrl() }}" target="_blank" class="font-semibold text-bucha-primary hover:text-bucha-burgundy">{{ __('View current receipt') }}</a></p>
                                @endif
                            </div>
                        </div>
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.finance.expenses.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti {{ $isEdit ? 'ti-device-floppy' : 'ti-plus' }} text-base leading-none" aria-hidden="true"></i>
                        {{ $isEdit ? __('Save changes') : __('Record expense') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
