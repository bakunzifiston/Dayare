<section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <div><x-input-label for="feed_type_id" :value="__('Feed type')" /><select id="feed_type_id" name="feed_type_id" class="mt-1 block w-full rounded-lg border-gray-300" required><option value="">{{ __('Select feed type') }}</option>@foreach ($feedTypes as $feedType)<option value="{{ $feedType->id }}" @selected((int) old('feed_type_id') === $feedType->id)>{{ $feedType->feed_name }}</option>@endforeach</select></div>
        <div><x-input-label for="supplier_id" :value="__('Supplier')" /><select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-lg border-gray-300"><option value="">{{ __('Optional') }}</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((int) old('supplier_id') === $supplier->id)>{{ $supplier->supplier_name }}</option>@endforeach</select></div>
        <div><x-input-label for="quantity_received" :value="__('Quantity received')" /><x-text-input id="quantity_received" name="quantity_received" type="number" step="0.001" min="0.001" class="mt-1 block w-full" :value="old('quantity_received')" required /></div>
        <div><x-input-label for="unit_cost" :value="__('Unit cost')" /><x-text-input id="unit_cost" name="unit_cost" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('unit_cost')" /></div>
        <div><x-input-label for="purchase_date" :value="__('Purchase date')" /><x-text-input id="purchase_date" name="purchase_date" type="date" class="mt-1 block w-full" :value="old('purchase_date', date('Y-m-d'))" max="{{ date('Y-m-d') }}" required /></div>
        <div><x-input-label for="expiry_date" :value="__('Expiry date')" /><x-text-input id="expiry_date" name="expiry_date" type="date" class="mt-1 block w-full" :value="old('expiry_date')" /></div>
        <div><x-input-label for="storage_location" :value="__('Storage location')" /><x-text-input id="storage_location" name="storage_location" class="mt-1 block w-full" :value="old('storage_location')" /></div>
        <div><x-input-label for="reorder_level" :value="__('Reorder level')" /><x-text-input id="reorder_level" name="reorder_level" type="number" step="0.001" min="0" class="mt-1 block w-full" :value="old('reorder_level')" /></div>
        <div class="sm:col-span-2"><x-input-label for="notes" :value="__('Notes')" /><textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea></div>
    </div>
</section>
