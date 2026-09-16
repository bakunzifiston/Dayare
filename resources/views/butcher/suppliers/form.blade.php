@php
    $isEdit = $supplier !== null;
    $typeLabel = static fn (string $type): string => str_replace('_', ' ', ucfirst($type));
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <a href="{{ route('butcher.suppliers.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                        <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                        {{ __('Suppliers') }}
                    </a>
                    <div class="mt-3 flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                            <i class="ti ti-truck text-lg leading-none"></i>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">
                                {{ $isEdit ? __('Edit supplier') : __('Add supplier') }}
                            </h2>
                            <p class="mt-0.5 text-sm text-slate-500">
                                {{ $isEdit ? __('Update details used in procurement and receiving.') : __('Create a supplier for purchase orders and deliveries.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <form
                method="post"
                action="{{ $isEdit ? route('butcher.suppliers.update', $supplier) : route('butcher.suppliers.store') }}"
                class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm"
            >
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Identity') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="name" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Supplier / company name') }}</label>
                                <input id="name" name="name" type="text" required value="{{ old('name', $supplier?->name) }}" class="{{ $fieldClass }}" placeholder="{{ __('e.g. Nyagatare Farm Co-op') }}">
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <label for="supplier_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Supplier type') }}</label>
                                <select id="supplier_type" name="supplier_type" required class="{{ $fieldClass }}">
                                    @foreach (\App\Models\ButcherSupplier::SUPPLIER_TYPES as $type)
                                        <option value="{{ $type }}" @selected(old('supplier_type', $supplier?->supplier_type) === $type)>{{ $typeLabel($type) }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('supplier_type')" class="mt-2" />
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex w-full items-center gap-3 rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2.5 text-sm text-slate-700">
                                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier?->is_active ?? true)) class="rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary">
                                    <span class="font-medium text-slate-900">{{ __('Active supplier') }}</span>
                                </label>
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Contact') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="contact_person" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Contact person') }}</label>
                                <input id="contact_person" name="contact_person" type="text" value="{{ old('contact_person', $supplier?->contact_person) }}" class="{{ $fieldClass }}" placeholder="{{ __('Full name') }}">
                            </div>
                            <div>
                                <label for="phone" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Phone') }}</label>
                                <input id="phone" name="phone" type="text" value="{{ old('phone', $supplier?->phone) }}" class="{{ $fieldClass }}" placeholder="+250788123456">
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="email" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Email') }}</label>
                                <input id="email" name="email" type="email" value="{{ old('email', $supplier?->email) }}" class="{{ $fieldClass }}" placeholder="name@example.com">
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Location') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="district" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('District') }}</label>
                                <select id="district" name="district" class="{{ $fieldClass }}">
                                    <option value="">{{ __('Optional') }}</option>
                                    @foreach ($districts as $district)
                                        <option value="{{ $district }}" @selected(old('district', $supplier?->district) === $district)>{{ $district }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('district')" class="mt-2" />
                            </div>
                            <div>
                                <label for="sector" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Sector') }}</label>
                                <input id="sector" name="sector" type="text" value="{{ old('sector', $supplier?->sector) }}" class="{{ $fieldClass }}" placeholder="{{ __('Optional') }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="notes" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Notes') }}</label>
                                <textarea id="notes" name="notes" rows="3" class="{{ $fieldClass }}" placeholder="{{ __('Delivery terms, preferred cuts, or other notes…') }}">{{ old('notes', $supplier?->notes) }}</textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.suppliers.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti {{ $isEdit ? 'ti-device-floppy' : 'ti-plus' }} text-base leading-none" aria-hidden="true"></i>
                        {{ $isEdit ? __('Update supplier') : __('Add supplier') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
