<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800">{{ __('Upload movement permit') }}</h2>
    </x-slot>

    <div class="max-w-4xl">
        <form method="POST" action="{{ route('farmer.movement-permits.store') }}" enctype="multipart/form-data" class="bg-white rounded-bucha border border-slate-200/60 p-6 space-y-6">
            @csrf

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="permit_number" :value="__('Permit number')" />
                    <x-text-input id="permit_number" name="permit_number" type="text" class="mt-1 block w-full" :value="old('permit_number')" required />
                    <x-input-error :messages="$errors->get('permit_number')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="source_farm_id" :value="__('Source farm')" />
                    <select id="source_farm_id" name="source_farm_id" class="mt-1 block w-full rounded-lg border-gray-300" required>
                        <option value="">{{ __('Select farm') }}</option>
                        @foreach ($farms as $farm)
                            <option value="{{ $farm->id }}" @selected((string) old('source_farm_id') === (string) $farm->id)>{{ $farm->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('source_farm_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="transport_mode" :value="__('Transport mode')" />
                    <x-text-input id="transport_mode" name="transport_mode" type="text" class="mt-1 block w-full" :value="old('transport_mode')" />
                </div>
                <div>
                    <x-input-label for="vehicle_plate" :value="__('Vehicle plate')" />
                    <x-text-input id="vehicle_plate" name="vehicle_plate" type="text" class="mt-1 block w-full" :value="old('vehicle_plate')" />
                </div>
                <div>
                    <x-input-label for="issue_date" :value="__('Issue date')" />
                    <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full" :value="old('issue_date', now()->toDateString())" required />
                    <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="expiry_date" :value="__('Expiry date')" />
                    <x-text-input id="expiry_date" name="expiry_date" type="date" class="mt-1 block w-full" :value="old('expiry_date')" required />
                    <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="issued_by" :value="__('Issued by')" />
                    <x-text-input id="issued_by" name="issued_by" type="text" class="mt-1 block w-full" :value="old('issued_by')" required />
                    <x-input-error :messages="$errors->get('issued_by')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="file" :value="__('Permit document (PDF/image)')" />
                    <input id="file" name="file" type="file" class="mt-1 block w-full rounded-lg border-gray-300" required />
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>
            </div>

            <div x-data="destinationDivisions()" x-init="loadCountries()">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Destination details') }}</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="dest-country" :value="__('Country')" />
                        <select id="dest-country" x-model="countryId" @change="onCountryChange()" class="mt-1 block w-full rounded-lg border-gray-300">
                            <option value="">{{ __('Select country') }}</option>
                            <template x-for="d in countries" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="destination_district_id" :value="__('District')" />
                        <select id="destination_district_id" name="destination_district_id" x-model="districtId" @change="onDistrictChange()" class="mt-1 block w-full rounded-lg border-gray-300" required>
                            <option value="">{{ __('Select district') }}</option>
                            <template x-for="d in districts" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('destination_district_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="destination_sector_id" :value="__('Sector')" />
                        <select id="destination_sector_id" name="destination_sector_id" x-model="sectorId" @change="onSectorChange()" class="mt-1 block w-full rounded-lg border-gray-300" required>
                            <option value="">{{ __('Select sector') }}</option>
                            <template x-for="d in sectors" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('destination_sector_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="destination_cell_id" :value="__('Cell')" />
                        <select id="destination_cell_id" name="destination_cell_id" x-model="cellId" @change="onCellChange()" class="mt-1 block w-full rounded-lg border-gray-300" required>
                            <option value="">{{ __('Select cell') }}</option>
                            <template x-for="d in cells" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('destination_cell_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="destination_village_id" :value="__('Village')" />
                        <select id="destination_village_id" name="destination_village_id" x-model="villageId" class="mt-1 block w-full rounded-lg border-gray-300" required>
                            <option value="">{{ __('Select village') }}</option>
                            <template x-for="d in villages" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('destination_village_id')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Linked animals / quantities') }}</h3>
                <p class="text-xs text-slate-500 mb-3">{{ __('Provide at least one livestock row or animal identifier with quantity.') }}</p>
                <div class="space-y-3">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="grid sm:grid-cols-3 gap-3 p-3 rounded-lg border border-slate-200">
                            <div>
                                <x-input-label :for="'animals_'.$i.'_livestock_id'" :value="__('Livestock row')" />
                                <select :id="'animals_{{ $i }}_livestock_id'" name="animals[{{ $i }}][livestock_id]" class="mt-1 block w-full rounded-lg border-gray-300">
                                    <option value="">{{ __('Select livestock') }}</option>
                                    @foreach ($farms as $farm)
                                        @foreach ($farm->livestock as $row)
                                            <option value="{{ $row->id }}" @selected((string) old('animals.'.$i.'.livestock_id') === (string) $row->id)>
                                                {{ $farm->name }} — {{ \App\Support\FarmerAnimalType::label($row->type) }} #{{ $row->id }}
                                            </option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label :for="'animals_'.$i.'_animal_identifier'" :value="__('Animal identifier')" />
                                <x-text-input :id="'animals_'.$i.'_animal_identifier'" :name="'animals['.$i.'][animal_identifier]'" type="text" class="mt-1 block w-full" :value="old('animals.'.$i.'.animal_identifier')" />
                            </div>
                            <div>
                                <x-input-label :for="'animals_'.$i.'_quantity'" :value="__('Quantity')" />
                                <x-text-input :id="'animals_'.$i.'_quantity'" :name="'animals['.$i.'][quantity]'" type="number" min="1" class="mt-1 block w-full" :value="old('animals.'.$i.'.quantity', 1)" />
                            </div>
                        </div>
                    @endfor
                </div>
                <x-input-error :messages="$errors->get('animals')" class="mt-2" />
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">{{ __('Save permit') }}</button>
                <a href="{{ route('farmer.movement-permits.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-sm font-semibold rounded-bucha">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>

    <script>
        function destinationDivisions() {
            const baseUrl = '{{ route("divisions.index") }}';
            return {
                countries: [], districts: [], sectors: [], cells: [], villages: [],
                countryId: '',
                districtId: '{{ old('destination_district_id') }}',
                sectorId: '{{ old('destination_sector_id') }}',
                cellId: '{{ old('destination_cell_id') }}',
                villageId: '{{ old('destination_village_id') }}',
                async fetchChildren(parentId) {
                    const url = parentId ? `${baseUrl}?parent_id=${parentId}` : baseUrl;
                    const res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    return Array.isArray(data) ? data : [];
                },
                async loadCountries() {
                    this.countries = await this.fetchChildren(null);
                    if (this.countryId) await this.onCountryChange();
                },
                async onCountryChange() {
                    this.districtId = this.sectorId = this.cellId = this.villageId = '';
                    this.districts = this.sectors = this.cells = this.villages = [];
                    const provinces = await this.fetchChildren(this.countryId);
                    for (const p of provinces) {
                        const d = await this.fetchChildren(p.id);
                        this.districts.push(...d);
                    }
                },
                async onDistrictChange() {
                    this.sectorId = this.cellId = this.villageId = '';
                    this.sectors = this.cells = this.villages = [];
                    if (!this.districtId) return;
                    this.sectors = await this.fetchChildren(this.districtId);
                },
                async onSectorChange() {
                    this.cellId = this.villageId = '';
                    this.cells = this.villages = [];
                    if (!this.sectorId) return;
                    this.cells = await this.fetchChildren(this.sectorId);
                },
                async onCellChange() {
                    this.villageId = '';
                    this.villages = [];
                    if (!this.cellId) return;
                    this.villages = await this.fetchChildren(this.cellId);
                },
            };
        }
    </script>
</x-app-layout>

