@php
    $editingSupplier = $editing ?? null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.onboarding.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Onboarding') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Suppliers') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('butcher.onboarding.partials.progress', ['progress' => $progress])

            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <section class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Supplier directory') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('Manual entries only — not linked to other workspaces.') }}</p>
                <div class="mt-4 space-y-3">
                    @forelse ($suppliers as $supplier)
                        <div class="rounded-lg border border-slate-200 px-4 py-3 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold text-slate-900">{{ $supplier->name }}</p>
                                <span class="text-xs text-slate-500">{{ str_replace('_', ' ', ucfirst($supplier->supplier_type)) }}</span>
                            </div>
                            @if ($supplier->contact_person)
                                <p class="mt-1 text-slate-600">{{ $supplier->contact_person }}</p>
                            @endif
                            @if ($supplier->phone)
                                <p class="mt-1 text-slate-500">{{ $supplier->phone }}</p>
                            @endif
                            <div class="mt-2 flex gap-3 text-xs font-semibold">
                                <a href="{{ route('butcher.onboarding.suppliers', ['edit' => $supplier->id]) }}" class="text-bucha-primary hover:underline">{{ __('Edit') }}</a>
                                <form method="post" action="{{ route('butcher.onboarding.suppliers.destroy', $supplier) }}" onsubmit="return confirm(@js(__('Remove this supplier?')))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">{{ __('Remove') }}</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No suppliers yet.') }}</p>
                    @endforelse
                </div>
            </section>

            <form
                method="post"
                action="{{ $editingSupplier ? route('butcher.onboarding.suppliers.update', $editingSupplier) : route('butcher.onboarding.suppliers.store') }}"
                class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4"
            >
                @csrf
                @if ($editingSupplier)
                    @method('PUT')
                @endif

                <h3 class="text-sm font-semibold text-slate-900">
                    {{ $editingSupplier ? __('Edit supplier') : __('Add supplier') }}
                </h3>

                <div>
                    <x-input-label for="name" :value="__('Supplier / company name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $editingSupplier?->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="supplier_type" :value="__('Supplier type')" />
                        <select id="supplier_type" name="supplier_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            @foreach (\App\Models\ButcherSupplier::SUPPLIER_TYPES as $type)
                                <option value="{{ $type }}" @selected(old('supplier_type', $editingSupplier?->supplier_type) === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('supplier_type')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="contact_person" :value="__('Contact person')" />
                        <x-text-input id="contact_person" name="contact_person" type="text" class="mt-1 block w-full" :value="old('contact_person', $editingSupplier?->contact_person)" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="phone" :value="__('Phone')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" placeholder="+250788123456" :value="old('phone', $editingSupplier?->phone)" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $editingSupplier?->email)" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="district" :value="__('District')" />
                        <select id="district" name="district" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ __('Optional') }}</option>
                            @foreach ($districts as $district)
                                <option value="{{ $district }}" @selected(old('district', $editingSupplier?->district) === $district)>{{ $district }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('district')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="sector" :value="__('Sector')" />
                        <x-text-input id="sector" name="sector" type="text" class="mt-1 block w-full" :value="old('sector', $editingSupplier?->sector)" />
                    </div>
                </div>

                <div>
                    <x-input-label for="notes" :value="__('Notes')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $editingSupplier?->notes) }}</textarea>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingSupplier?->is_active ?? true)) class="rounded border-gray-300 text-bucha-primary focus:ring-bucha-primary">
                    {{ __('Active supplier') }}
                </label>

                <div class="flex flex-wrap justify-between gap-3 pt-2">
                    @if ($editingSupplier)
                        <a href="{{ route('butcher.onboarding.suppliers') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('Cancel edit') }}
                        </a>
                    @else
                        <a href="{{ route('butcher.onboarding.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('Back to checklist') }}
                        </a>
                    @endif
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        {{ $editingSupplier ? __('Update supplier') : __('Add supplier') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
