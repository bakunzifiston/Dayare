@php $record = $record ?? null; @endphp
<section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        @isset($businesses)
            <div>
                <x-input-label for="business_id" :value="__('Business')" />
                <select id="business_id" name="business_id" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach ($businesses as $businessId)
                        <option value="{{ $businessId }}" @selected((int) old('business_id', $record?->business_id) === (int) $businessId)>#{{ $businessId }}</option>
                    @endforeach
                </select>
            </div>
        @endisset
        <div><x-input-label for="feed_name" :value="__('Feed name')" /><x-text-input id="feed_name" name="feed_name" class="mt-1 block w-full" :value="old('feed_name', $record?->feed_name)" required /></div>
        <div><x-input-label for="feed_category" :value="__('Category')" /><select id="feed_category" name="feed_category" class="mt-1 block w-full rounded-lg border-gray-300" required>@foreach (\App\Models\FeedType::CATEGORIES as $category)<option value="{{ $category }}" @selected(old('feed_category', $record?->feed_category) === $category)>{{ __(ucwords(str_replace('_', ' ', $category))) }}</option>@endforeach</select></div>
        <div><x-input-label for="feed_form" :value="__('Form')" /><select id="feed_form" name="feed_form" class="mt-1 block w-full rounded-lg border-gray-300" required>@foreach (\App\Models\FeedType::FORMS as $form)<option value="{{ $form }}" @selected(old('feed_form', $record?->feed_form) === $form)>{{ __(ucfirst($form)) }}</option>@endforeach</select></div>
        <div><x-input-label for="unit" :value="__('Unit')" /><x-text-input id="unit" name="unit" class="mt-1 block w-full" :value="old('unit', $record?->unit ?: 'kg')" required /></div>
        <div><x-input-label for="protein_percentage" :value="__('Protein %')" /><x-text-input id="protein_percentage" name="protein_percentage" type="number" step="0.01" class="mt-1 block w-full" :value="old('protein_percentage', $record?->protein_percentage)" /></div>
        <div><x-input-label for="energy_value" :value="__('Energy value')" /><x-text-input id="energy_value" name="energy_value" type="number" step="0.01" class="mt-1 block w-full" :value="old('energy_value', $record?->energy_value)" /></div>
        <div><x-input-label for="manufacturer" :value="__('Manufacturer')" /><x-text-input id="manufacturer" name="manufacturer" class="mt-1 block w-full" :value="old('manufacturer', $record?->manufacturer)" /></div>
        <div><x-input-label for="status" :value="__('Status')" /><select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300" required>@foreach (\App\Models\FeedType::STATUSES as $status)<option value="{{ $status }}" @selected(old('status', $record?->status) === $status)>{{ __(ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="sm:col-span-2"><x-input-label for="description" :value="__('Description')" /><textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">{{ old('description', $record?->description) }}</textarea></div>
    </div>
</section>
