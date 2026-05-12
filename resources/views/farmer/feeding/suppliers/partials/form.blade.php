<section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <div><x-input-label for="business_id" :value="__('Business')" /><select id="business_id" name="business_id" class="mt-1 block w-full rounded-lg border-gray-300" required>@foreach ($businesses as $businessId)<option value="{{ $businessId }}">#{{ $businessId }}</option>@endforeach</select></div>
        <div><x-input-label for="supplier_name" :value="__('Supplier name')" /><x-text-input id="supplier_name" name="supplier_name" class="mt-1 block w-full" :value="old('supplier_name')" required /></div>
        <div><x-input-label for="contact_person" :value="__('Contact person')" /><x-text-input id="contact_person" name="contact_person" class="mt-1 block w-full" :value="old('contact_person')" /></div>
        <div><x-input-label for="phone" :value="__('Phone')" /><x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone')" /></div>
        <div><x-input-label for="email" :value="__('Email')" /><x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" /></div>
        <div><x-input-label for="status" :value="__('Status')" /><select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300" required>@foreach (\App\Models\FeedSupplier::STATUSES as $status)<option value="{{ $status }}">{{ __(ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="sm:col-span-2"><x-input-label for="address" :value="__('Address')" /><textarea id="address" name="address" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('address') }}</textarea></div>
        <div class="sm:col-span-2"><x-input-label for="notes" :value="__('Notes')" /><textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea></div>
    </div>
</section>
