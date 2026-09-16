@php
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.purchase-orders.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Purchase orders') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-file-invoice text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('New purchase order') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ __('Create a draft order for a supplier.') }}</p>
                    </div>
                </div>
            </div>

            <form
                method="post"
                action="{{ route('butcher.purchase-orders.store') }}"
                class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm"
            >
                @csrf

                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Order') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="supplier_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Supplier') }}</label>
                                <select id="supplier_id" name="supplier_id" required class="{{ $fieldClass }}">
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                            </div>
                            <div>
                                <label for="meat_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Meat type') }}</label>
                                <select id="meat_type" name="meat_type" required class="{{ $fieldClass }}">
                                    @foreach ($meatTypes as $type)
                                        <option value="{{ $type }}" @selected(old('meat_type') === $type)>{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('meat_type')" class="mt-2" />
                            </div>
                            <div>
                                <label for="requested_date" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Requested date') }}</label>
                                <input id="requested_date" name="requested_date" type="date" required value="{{ old('requested_date', now()->toDateString()) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('requested_date')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="requested_weight_kg" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Requested weight (kg)') }}</label>
                                <input id="requested_weight_kg" name="requested_weight_kg" type="number" step="0.001" min="0.1" required value="{{ old('requested_weight_kg') }}" class="{{ $fieldClass }}" placeholder="0.000">
                                <x-input-error :messages="$errors->get('requested_weight_kg')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Notes') }}</h3>
                        <div>
                            <label for="notes" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Notes') }}</label>
                            <textarea id="notes" name="notes" rows="3" class="{{ $fieldClass }}" placeholder="{{ __('Delivery terms or special instructions…') }}">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.purchase-orders.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                        {{ __('Create purchase order') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
