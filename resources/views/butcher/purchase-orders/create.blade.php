<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.purchase-orders.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Purchase orders') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('New purchase order') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('butcher.purchase-orders.store') }}" class="rounded-bucha border border-slate-200/80 bg-white p-6 shadow-bucha space-y-4">
                @csrf

                <div>
                    <x-input-label for="supplier_id" :value="__('Supplier')" />
                    <select id="supplier_id" name="supplier_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="meat_type" :value="__('Meat type')" />
                    <select id="meat_type" name="meat_type" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        @foreach ($meatTypes as $type)
                            <option value="{{ $type }}" @selected(old('meat_type') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('meat_type')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="requested_weight_kg" :value="__('Requested weight (kg)')" />
                        <x-text-input id="requested_weight_kg" name="requested_weight_kg" type="number" step="0.001" min="0.1" class="mt-1 block w-full" :value="old('requested_weight_kg')" required />
                        <x-input-error :messages="$errors->get('requested_weight_kg')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="requested_date" :value="__('Requested date')" />
                        <x-text-input id="requested_date" name="requested_date" type="date" class="mt-1 block w-full" :value="old('requested_date', now()->toDateString())" required />
                        <x-input-error :messages="$errors->get('requested_date')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="notes" :value="__('Notes (optional)')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <p class="text-xs text-slate-500">{{ __('Orders start as draft. Send, then confirm with the supplier before receiving goods.') }}</p>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        {{ __('Create purchase order') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
