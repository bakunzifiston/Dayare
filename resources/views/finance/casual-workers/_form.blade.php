<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <x-input-label for="first_name" :value="__('First name')" />
        <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $worker?->first_name)" required />
        <x-input-error class="mt-1" :messages="$errors->get('first_name')" />
    </div>
    <div>
        <x-input-label for="last_name" :value="__('Last name')" />
        <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $worker?->last_name)" required />
        <x-input-error class="mt-1" :messages="$errors->get('last_name')" />
    </div>
    <div>
        <x-input-label for="phone" :value="__('Phone')" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $worker?->phone)" />
    </div>
    <div>
        <x-input-label for="national_id" :value="__('National ID')" />
        <x-text-input id="national_id" name="national_id" type="text" class="mt-1 block w-full" :value="old('national_id', $worker?->national_id)" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="notes" :value="__('Notes')" />
        <textarea id="notes" name="notes" class="mt-1 block w-full rounded-lg border-slate-300" rows="3">{{ old('notes', $worker?->notes ?? '') }}</textarea>
    </div>
    <div class="md:col-span-2 flex items-center gap-2">
        <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-slate-300 text-bucha-primary" @checked(old('is_active', $worker?->is_active ?? true)) />
        <x-input-label for="is_active" :value="__('Active (show in payable dropdown)')" class="!mb-0" />
    </div>
</div>
