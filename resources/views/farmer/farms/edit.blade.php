<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('farmer.farms.show', $farm) }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Farm') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('Edit farm') }}</h2>
    </x-slot>

    <div class="max-w-3xl">
        <form method="post" action="{{ route('farmer.farms.update', $farm) }}" class="space-y-8">
            @csrf
            @method('put')
            <div class="bg-white rounded-bucha border border-slate-200/60 p-6 space-y-4">
                <div>
                    <x-input-label for="name" :value="__('Farm name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $farm->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <span class="block text-sm font-medium text-slate-700 mb-2">{{ __('Animal types') }}</span>
                    <div class="flex flex-wrap gap-3">
                        @foreach (\App\Support\FarmerAnimalType::ALL as $t)
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="animal_types[]" value="{{ $t }}" @checked(collect(old('animal_types', $farm->animal_types ?? []))->contains($t)) />
                                {{ \App\Support\FarmerAnimalType::label($t) }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select name="status" id="status" class="mt-1 block w-full rounded-lg border-gray-300">
                        @foreach (\App\Models\Farm::STATUSES as $s)
                            <option value="{{ $s }}" @selected(old('status', $farm->status) === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @include('partials.rwanda-administrative-location', [
                'countryId' => old('country_id', $farm->country_id ?? ''),
                'provinceId' => old('province_id', $farm->province_id ?? ''),
                'districtId' => old('district_id', $farm->district_id ?? ''),
                'sectorId' => old('sector_id', $farm->sector_id ?? ''),
                'cellId' => old('cell_id', $farm->cell_id ?? ''),
                'villageId' => old('village_id', $farm->village_id ?? ''),
            ])

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">{{ __('Update') }}</button>
                <a href="{{ route('farmer.farms.show', $farm) }}" class="inline-flex items-center px-4 py-2 border border-slate-300 rounded-bucha text-sm">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
