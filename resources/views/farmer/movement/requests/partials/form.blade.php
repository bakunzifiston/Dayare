@php $req = $request ?? null; @endphp
<section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="farm_id" :value="__('Farm')" />
            <select id="farm_id" name="farm_id" class="mt-1 block w-full rounded-lg border-gray-300" required @disabled($req)>
                <option value="">{{ __('Select farm') }}</option>
                @foreach ($farms as $farm)
                    <option value="{{ $farm->id }}" @selected((int) old('farm_id', $req?->farm_id ?: request('source_farm_id')) === $farm->id)>{{ $farm->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="movement_purpose" :value="__('Movement purpose')" />
            <select id="movement_purpose" name="movement_purpose" class="mt-1 block w-full rounded-lg border-gray-300" required>
                @foreach (\App\Models\PermitRequest::PURPOSES as $purpose)
                    <option value="{{ $purpose }}" @selected(old('movement_purpose', $req?->movement_purpose) === $purpose)>{{ ucwords($purpose) }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="destination_type" :value="__('Destination type')" />
            <select id="destination_type" name="destination_type" class="mt-1 block w-full rounded-lg border-gray-300" required>
                @foreach (\App\Models\PermitRequest::DESTINATION_TYPES as $type)
                    <option value="{{ $type }}" @selected(old('destination_type', $req?->destination_type) === $type)>{{ ucwords($type) }}</option>
                @endforeach
            </select>
        </div>
        <div><x-input-label for="destination_name" :value="__('Destination name')" /><x-text-input id="destination_name" name="destination_name" class="mt-1 block w-full" :value="old('destination_name', $req?->destination_name)" /></div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div><x-input-label for="destination_district" :value="__('District')" /><x-text-input id="destination_district" name="destination_district" class="mt-1 block w-full" :value="old('destination_district', $req?->destination_district)" /></div>
        <div><x-input-label for="destination_sector" :value="__('Sector')" /><x-text-input id="destination_sector" name="destination_sector" class="mt-1 block w-full" :value="old('destination_sector', $req?->destination_sector)" /></div>
        <div><x-input-label for="destination_cell" :value="__('Cell')" /><x-text-input id="destination_cell" name="destination_cell" class="mt-1 block w-full" :value="old('destination_cell', $req?->destination_cell)" /></div>
        <div><x-input-label for="destination_village" :value="__('Village')" /><x-text-input id="destination_village" name="destination_village" class="mt-1 block w-full" :value="old('destination_village', $req?->destination_village)" /></div>
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="transport_method" :value="__('Transport method')" />
            <select id="transport_method" name="transport_method" class="mt-1 block w-full rounded-lg border-gray-300">
                <option value="">{{ __('Select') }}</option>
                @foreach (\App\Models\PermitRequest::TRANSPORT_METHODS as $method)
                    <option value="{{ $method }}" @selected(old('transport_method', $req?->transport_method) === $method)>{{ ucwords($method) }}</option>
                @endforeach
            </select>
        </div>
        <div><x-input-label for="vehicle_plate_number" :value="__('Vehicle plate')" /><x-text-input id="vehicle_plate_number" name="vehicle_plate_number" class="mt-1 block w-full" :value="old('vehicle_plate_number', $req?->vehicle_plate_number)" /></div>
        <div><x-input-label for="proposed_departure_date" :value="__('Proposed departure')" /><x-text-input id="proposed_departure_date" name="proposed_departure_date" type="date" class="mt-1 block w-full" :value="old('proposed_departure_date', $req?->proposed_departure_date?->format('Y-m-d') ?? now()->toDateString())" required /></div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><x-input-label for="expected_arrival_date" :value="__('Expected arrival')" /><x-text-input id="expected_arrival_date" name="expected_arrival_date" type="date" class="mt-1 block w-full" :value="old('expected_arrival_date', $req?->expected_arrival_date?->format('Y-m-d') ?? now()->addDay()->toDateString())" required /></div>
        <div><x-input-label for="remarks" :value="__('Remarks')" /><textarea id="remarks" name="remarks" rows="2" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('remarks', $req?->remarks) }}</textarea></div>
    </div>
    <div>
        <x-input-label :value="__('Animals to move')" />
        <p class="text-xs text-slate-500 mb-2">{{ __('Only eligible animals can be submitted.') }}</p>
        <div class="max-h-64 overflow-y-auto rounded-lg border border-slate-200 p-3 space-y-2">
            @foreach ($animals as $animal)
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="animal_ids[]" value="{{ $animal->id }}" class="mt-1 rounded border-gray-300" @checked(in_array($animal->id, old('animal_ids', $req?->animals?->pluck('animal_id')->all() ?? []))) />
                    <span>{{ $animal->selectionLabel() }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('animal_ids')" class="mt-2" />
    </div>
</section>
