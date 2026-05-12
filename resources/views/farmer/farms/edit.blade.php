<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('farmer.farms.show', $farm) }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Farm') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ __('Edit farm') }}</h2>
    </x-slot>

    <div class="max-w-4xl">
        <form method="post" action="{{ route('farmer.farms.update', $farm) }}" class="space-y-8">
            @csrf
            @method('put')

            @include('farmer.farms.partials.registration-form', [
                'business' => $farm->business,
                'farm' => $farm,
            ])

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

        <form method="post" action="{{ route('farmer.farms.destroy', $farm) }}" class="mt-6" onsubmit="return confirm('{{ __('Delete this farm?') }}');">
            @csrf
            @method('delete')
            <button type="submit" class="inline-flex items-center rounded-bucha border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">{{ __('Delete farm') }}</button>
        </form>
    </div>
</x-app-layout>
