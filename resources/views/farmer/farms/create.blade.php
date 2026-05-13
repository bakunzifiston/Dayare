<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('farmer.farms.index') }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Farms') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('New farm') }}</h2>
    </x-slot>

    <div class="max-w-4xl">
        <form method="post" action="{{ route('farmer.farms.store') }}" class="space-y-8">
            @csrf

            @isset($selectedBusiness)
                <input type="hidden" name="business_id" value="{{ old('business_id', $selectedBusiness->id) }}" />
            @endisset

            @include('farmer.farms.partials.registration-form', [
                'business' => $selectedBusiness,
                'farmerBusinesses' => $farmerBusinesses,
                'selectedBusiness' => $selectedBusiness,
            ])

            @include('partials.rwanda-administrative-location', [
                'countryId' => old('country_id', ''),
                'provinceId' => old('province_id', ''),
                'districtId' => old('district_id', ''),
                'sectorId' => old('sector_id', ''),
                'cellId' => old('cell_id', ''),
                'villageId' => old('village_id', ''),
            ])

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">{{ __('Save farm') }}</button>
                <a href="{{ route('farmer.farms.index') }}" class="inline-flex items-center px-4 py-2 border border-slate-300 rounded-bucha text-sm">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
