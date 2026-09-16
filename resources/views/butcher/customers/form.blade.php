@php
    $isEdit = $customer !== null;
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.customers.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Customers') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-user text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $isEdit ? __('Edit customer') : __('Add customer') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $isEdit ? __('Update customer account details.') : __('Create a customer for sales and orders.') }}</p>
                    </div>
                </div>
            </div>

            <form
                method="post"
                action="{{ $isEdit ? route('butcher.customers.update', $customer) : route('butcher.customers.store') }}"
                class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm"
            >
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Customer') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="name" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Name') }}</label>
                                <input id="name" name="name" type="text" required value="{{ old('name', $customer?->name) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <label for="phone" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Phone') }}</label>
                                <input id="phone" name="phone" type="text" required value="{{ old('phone', $customer?->phone) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                            <div>
                                <label for="tier" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Tier') }}</label>
                                <select id="tier" name="tier" class="{{ $fieldClass }}">
                                    @foreach ($tiers as $tier)
                                        <option value="{{ $tier }}" @selected(old('tier', $customer?->tier ?? \App\Models\ButcherCustomer::TIER_RETAIL) === $tier)>{{ ucfirst($tier) }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('tier')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="credit_limit" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Credit limit') }}</label>
                                <input id="credit_limit" name="credit_limit" type="number" min="0" value="{{ old('credit_limit', $customer?->credit_limit ?? 0) }}" class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('credit_limit')" class="mt-2" />
                            </div>
                        </div>
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.customers.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti {{ $isEdit ? 'ti-device-floppy' : 'ti-plus' }} text-base leading-none" aria-hidden="true"></i>
                        {{ $isEdit ? __('Update customer') : __('Add customer') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
