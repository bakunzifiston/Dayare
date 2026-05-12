@php
    $permit = $permit ?? null;
    $transport = old('transport', $permit?->transport?->only([
        'vehicle_type', 'vehicle_number', 'trailer_number', 'driver_name', 'driver_phone',
        'transporter_company', 'route_information', 'emergency_contact', 'transport_notes',
    ]) ?? []);
    $veterinary = old('veterinary', $permit?->veterinaryApproval?->only([
        'veterinarian_name', 'inspection_date', 'inspection_result', 'health_clearance',
        'disease_check', 'quarantine_check', 'recommendations', 'approval_status', 'notes',
    ]) ?? []);
    $lines = old('lines', $permit?->animals?->map(fn ($line) => [
        'animal_id' => $line->animal_id,
        'livestock_id' => $line->livestock_id,
        'animal_identifier' => $line->animal_identifier,
        'quantity' => $line->quantity,
        'movement_condition' => $line->movement_condition,
        'inspection_notes' => $line->inspection_notes,
        'notes' => $line->notes,
    ])->values()->all() ?? [['animal_id' => '', 'livestock_id' => '', 'animal_identifier' => '', 'quantity' => 1, 'movement_condition' => 'healthy', 'inspection_notes' => '', 'notes' => '']]);
@endphp

<div class="space-y-8" x-data="movementForm(@js($lines))">
    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-base font-semibold text-slate-800">{{ __('Permit details') }}</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="source_farm_id" :value="__('Origin farm')" />
                <select id="source_farm_id" name="source_farm_id" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    <option value="">{{ __('Select farm') }}</option>
                    @foreach ($farms as $farm)
                        <option value="{{ $farm->id }}" @selected((int) old('source_farm_id', $permit?->source_farm_id) === $farm->id)>{{ $farm->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('source_farm_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="permit_type" :value="__('Permit type')" />
                <select id="permit_type" name="permit_type" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach (\App\Models\MovementPermit::TYPES as $type)
                        <option value="{{ $type }}" @selected(old('permit_type', $permit?->permit_type ?? \App\Models\MovementPermit::TYPE_FARM_TRANSFER) === $type)>{{ __(ucwords(str_replace('_', ' ', $type))) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="movement_reason" :value="__('Movement reason')" />
                <x-text-input id="movement_reason" name="movement_reason" type="text" class="mt-1 block w-full" :value="old('movement_reason', $permit?->movement_reason)" />
            </div>
            <div>
                <x-input-label for="issued_by" :value="__('Issued by')" />
                <x-text-input id="issued_by" name="issued_by" type="text" class="mt-1 block w-full" :value="old('issued_by', $permit?->issued_by ?? auth()->user()?->name)" required />
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="origin_location" :value="__('Origin location')" />
                <x-text-input id="origin_location" name="origin_location" type="text" class="mt-1 block w-full" :value="old('origin_location', $permit?->origin_location)" />
            </div>
            <div>
                <x-input-label for="destination_location" :value="__('Destination location')" />
                <x-text-input id="destination_location" name="destination_location" type="text" class="mt-1 block w-full" :value="old('destination_location', $permit?->destination_location)" />
                <x-input-error :messages="$errors->get('destination_location')" class="mt-2" />
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="departure_date" :value="__('Departure date')" />
                <x-text-input id="departure_date" name="departure_date" type="date" class="mt-1 block w-full" :value="old('departure_date', $permit?->departure_date?->format('Y-m-d') ?? now()->toDateString())" required />
            </div>
            <div>
                <x-input-label for="expected_arrival_date" :value="__('Expected arrival date')" />
                <x-text-input id="expected_arrival_date" name="expected_arrival_date" type="date" class="mt-1 block w-full" :value="old('expected_arrival_date', $permit?->expected_arrival_date?->format('Y-m-d') ?? now()->addDay()->toDateString())" required />
            </div>
            <div>
                <x-input-label for="permit_status" :value="__('Permit status')" />
                <select id="permit_status" name="permit_status" class="mt-1 block w-full rounded-lg border-gray-300">
                    @foreach (\App\Models\MovementPermit::STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('permit_status', $permit?->permit_status ?? \App\Models\MovementPermit::STATUS_DRAFT) === $status)>{{ __(ucwords(str_replace('_', ' ', $status))) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <x-input-label for="attachment" :value="__('Attachment')" />
            <input id="attachment" name="attachment" type="file" accept=".pdf,image/*" class="mt-1 block w-full text-sm text-slate-600" />
        </div>
        <div>
            <x-input-label for="notes" :value="__('Notes')" />
            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('notes', $permit?->notes) }}</textarea>
        </div>
    </section>

    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-base font-semibold text-slate-800">{{ __('Movement animals') }}</h3>
            <button type="button" @click="addLine()" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-700">{{ __('Add animal') }}</button>
        </div>
        <x-input-error :messages="$errors->get('lines')" class="mt-2" />
        <template x-for="(line, index) in lines" :key="index">
            <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-100 bg-slate-50 p-4 md:grid-cols-6">
                <div class="md:col-span-2">
                    <label class="text-xs font-medium text-slate-600">{{ __('Animal') }}</label>
                    <select class="mt-1 block w-full rounded-lg border-gray-300 text-sm" :name="`lines[${index}][animal_id]`" x-model="line.animal_id">
                        <option value="">{{ __('Select animal') }}</option>
                        @foreach ($animals as $animal)
                            <option value="{{ $animal->id }}" data-identifier="{{ $animal->displayIdentifier() }}">{{ $animal->selectionLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600">{{ __('Tag / identifier') }}</label>
                    <input type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" :name="`lines[${index}][animal_identifier]`" x-model="line.animal_identifier" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600">{{ __('Quantity') }}</label>
                    <input type="number" min="1" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" :name="`lines[${index}][quantity]`" x-model="line.quantity" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600">{{ __('Condition') }}</label>
                    <select class="mt-1 block w-full rounded-lg border-gray-300 text-sm" :name="`lines[${index}][movement_condition]`" x-model="line.movement_condition">
                        @foreach (\App\Models\MovementPermitAnimal::CONDITIONS as $condition)
                            <option value="{{ $condition }}">{{ __(ucwords(str_replace('_', ' ', $condition))) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end justify-end">
                    <button type="button" @click="removeLine(index)" class="text-xs font-semibold uppercase tracking-wide text-red-600" x-show="lines.length > 1">{{ __('Remove') }}</button>
                </div>
            </div>
        </template>
    </section>

    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-base font-semibold text-slate-800">{{ __('Transport information') }}</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div><x-input-label for="transport_vehicle_type" :value="__('Vehicle type')" /><x-text-input id="transport_vehicle_type" name="transport[vehicle_type]" type="text" class="mt-1 block w-full" :value="old('transport.vehicle_type', $transport['vehicle_type'] ?? '')" /></div>
            <div><x-input-label for="transport_vehicle_number" :value="__('Vehicle number')" /><x-text-input id="transport_vehicle_number" name="transport[vehicle_number]" type="text" class="mt-1 block w-full" :value="old('transport.vehicle_number', $transport['vehicle_number'] ?? '')" /></div>
            <div><x-input-label for="transport_trailer_number" :value="__('Trailer number')" /><x-text-input id="transport_trailer_number" name="transport[trailer_number]" type="text" class="mt-1 block w-full" :value="old('transport.trailer_number', $transport['trailer_number'] ?? '')" /></div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div><x-input-label for="transport_driver_name" :value="__('Driver name')" /><x-text-input id="transport_driver_name" name="transport[driver_name]" type="text" class="mt-1 block w-full" :value="old('transport.driver_name', $transport['driver_name'] ?? '')" /></div>
            <div><x-input-label for="transport_driver_phone" :value="__('Driver phone')" /><x-text-input id="transport_driver_phone" name="transport[driver_phone]" type="text" class="mt-1 block w-full" :value="old('transport.driver_phone', $transport['driver_phone'] ?? '')" /></div>
            <div><x-input-label for="transport_transporter_company" :value="__('Transporter company')" /><x-text-input id="transport_transporter_company" name="transport[transporter_company]" type="text" class="mt-1 block w-full" :value="old('transport.transporter_company', $transport['transporter_company'] ?? '')" /></div>
        </div>
        <div><x-input-label for="transport_route_information" :value="__('Route information')" /><textarea id="transport_route_information" name="transport[route_information]" rows="2" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('transport.route_information', $transport['route_information'] ?? '') }}</textarea></div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div><x-input-label for="transport_emergency_contact" :value="__('Emergency contact')" /><x-text-input id="transport_emergency_contact" name="transport[emergency_contact]" type="text" class="mt-1 block w-full" :value="old('transport.emergency_contact', $transport['emergency_contact'] ?? '')" /></div>
            <div><x-input-label for="transport_transport_notes" :value="__('Transport notes')" /><x-text-input id="transport_transport_notes" name="transport[transport_notes]" type="text" class="mt-1 block w-full" :value="old('transport.transport_notes', $transport['transport_notes'] ?? '')" /></div>
        </div>
    </section>

    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-base font-semibold text-slate-800">{{ __('Veterinary approval') }}</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div><x-input-label for="veterinary_veterinarian_name" :value="__('Veterinarian name')" /><x-text-input id="veterinary_veterinarian_name" name="veterinary[veterinarian_name]" type="text" class="mt-1 block w-full" :value="old('veterinary.veterinarian_name', $veterinary['veterinarian_name'] ?? '')" /></div>
            <div><x-input-label for="veterinary_inspection_date" :value="__('Inspection date')" /><x-text-input id="veterinary_inspection_date" name="veterinary[inspection_date]" type="date" class="mt-1 block w-full" :value="old('veterinary.inspection_date', isset($veterinary['inspection_date']) ? \Illuminate\Support\Carbon::parse($veterinary['inspection_date'])->format('Y-m-d') : '')" /></div>
            <div>
                <x-input-label for="veterinary_approval_status" :value="__('Approval status')" />
                <select id="veterinary_approval_status" name="veterinary[approval_status]" class="mt-1 block w-full rounded-lg border-gray-300">
                    <option value="">{{ __('Pending') }}</option>
                    @foreach (\App\Models\MovementVeterinaryApproval::APPROVAL_STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('veterinary.approval_status', $veterinary['approval_status'] ?? '') === $status)>{{ __(ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="veterinary_inspection_result" :value="__('Inspection result')" />
                <select id="veterinary_inspection_result" name="veterinary[inspection_result]" class="mt-1 block w-full rounded-lg border-gray-300">
                    <option value="">{{ __('Select result') }}</option>
                    @foreach (\App\Models\MovementVeterinaryApproval::RESULTS as $result)
                        <option value="{{ $result }}" @selected(old('veterinary.inspection_result', $veterinary['inspection_result'] ?? '') === $result)>{{ __(ucwords(str_replace('_', ' ', $result))) }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="hidden" name="veterinary[health_clearance]" value="0" /><input type="checkbox" name="veterinary[health_clearance]" value="1" @checked(old('veterinary.health_clearance', $veterinary['health_clearance'] ?? false)) /> {{ __('Health clearance') }}</label>
            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="hidden" name="veterinary[disease_check]" value="0" /><input type="checkbox" name="veterinary[disease_check]" value="1" @checked(old('veterinary.disease_check', $veterinary['disease_check'] ?? false)) /> {{ __('Disease check') }}</label>
            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="hidden" name="veterinary[quarantine_check]" value="0" /><input type="checkbox" name="veterinary[quarantine_check]" value="1" @checked(old('veterinary.quarantine_check', $veterinary['quarantine_check'] ?? false)) /> {{ __('Quarantine check') }}</label>
        </div>
        <div><x-input-label for="veterinary_recommendations" :value="__('Recommendations')" /><textarea id="veterinary_recommendations" name="veterinary[recommendations]" rows="2" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('veterinary.recommendations', $veterinary['recommendations'] ?? '') }}</textarea></div>
        <div><x-input-label for="veterinary_notes" :value="__('Veterinary notes')" /><textarea id="veterinary_notes" name="veterinary[notes]" rows="2" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('veterinary.notes', $veterinary['notes'] ?? '') }}</textarea></div>
    </section>
</div>

@push('scripts')
<script>
    function movementForm(initialLines) {
        return {
            lines: initialLines.length ? initialLines : [{ animal_id: '', livestock_id: '', animal_identifier: '', quantity: 1, movement_condition: 'healthy', inspection_notes: '', notes: '' }],
            addLine() { this.lines.push({ animal_id: '', livestock_id: '', animal_identifier: '', quantity: 1, movement_condition: 'healthy', inspection_notes: '', notes: '' }); },
            removeLine(index) { if (this.lines.length > 1) { this.lines.splice(index, 1); } },
        };
    }
</script>
@endpush
