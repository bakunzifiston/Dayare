@php
    $isEdit = $expense !== null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $isEdit ? __('Edit expense') : __('Log expense') }}</h2>
            </div>
            <a href="{{ route('butcher.finance.expenses.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Back') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ $isEdit ? route('butcher.finance.expenses.update', $expense) : route('butcher.finance.expenses.store') }}" enctype="multipart/form-data" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-5">
                @csrf
                @if ($isEdit) @method('PUT') @endif

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Category') }}</label>
                        <select name="category" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected(old('category', $expense?->category) === $category)>{{ ucfirst($category) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Outlet') }}</label>
                        <select name="outlet_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ __('All / head office') }}</option>
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id', $expense?->outlet_id) == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Description') }}</label>
                    <input name="description" required value="{{ old('description', $expense?->description) }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Amount (RWF)') }}</label>
                        <input type="number" name="amount" min="0.01" step="1" required value="{{ old('amount', $expense?->amount) }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Date') }}</label>
                        <input type="date" name="expense_date" required value="{{ old('expense_date', $expense?->expense_date?->toDateString() ?? now()->toDateString()) }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Payment') }}</label>
                        <select name="payment_method" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method }}" @selected(old('payment_method', $expense?->payment_method) === $method)>{{ str_replace('_', ' ', ucfirst($method)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Receipt (optional)') }}</label>
                    <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm">
                    @if ($expense?->receipt_path)
                        <p class="mt-1 text-xs text-slate-500"><a href="{{ $expense->receiptUrl() }}" target="_blank" class="text-bucha-primary hover:underline">{{ __('View current receipt') }}</a></p>
                    @endif
                </div>

                <button type="submit" class="rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ $isEdit ? __('Save changes') : __('Record expense') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
