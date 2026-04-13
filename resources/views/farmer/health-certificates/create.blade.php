<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800">{{ __('Create health certificate') }}</h2>
    </x-slot>

    <div class="max-w-4xl">
        <form method="POST" action="{{ route('farmer.health-certificates.store') }}" enctype="multipart/form-data" class="bg-white rounded-bucha border border-slate-200/60 p-6 space-y-5">
            @csrf

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="certificate_number" :value="__('Certificate number')" />
                    <x-text-input id="certificate_number" name="certificate_number" type="text" class="mt-1 block w-full" :value="old('certificate_number')" required />
                    <x-input-error :messages="$errors->get('certificate_number')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="certificate_type" :value="__('Certificate type')" />
                    <select id="certificate_type" name="certificate_type" class="mt-1 block w-full rounded-lg border-gray-300" required>
                        @foreach (\App\Models\FarmerHealthCertificate::TYPES as $type)
                            <option value="{{ $type }}" @selected(old('certificate_type', ($healthRecord?->event_type === \App\Models\AnimalHealthRecord::EVENT_VACCINATION ? \App\Models\FarmerHealthCertificate::TYPE_VACCINATION : \App\Models\FarmerHealthCertificate::TYPE_HEALTH)) === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('certificate_type')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="farm_id" :value="__('Farm')" />
                    <select id="farm_id" name="farm_id" class="mt-1 block w-full rounded-lg border-gray-300" required>
                        <option value="">{{ __('Select farm') }}</option>
                        @foreach ($farms as $farm)
                            <option value="{{ $farm->id }}" @selected((string) old('farm_id', request('farm_id', $healthRecord?->farm_id)) === (string) $farm->id)>{{ $farm->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('farm_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="livestock_id" :value="__('Livestock row (optional if batch set)')" />
                    <select id="livestock_id" name="livestock_id" class="mt-1 block w-full rounded-lg border-gray-300">
                        <option value="">{{ __('Select livestock') }}</option>
                        @foreach ($farms as $farm)
                            @foreach ($farm->livestock as $row)
                                <option value="{{ $row->id }}" @selected((string) old('livestock_id', request('livestock_id', $healthRecord?->livestock_id)) === (string) $row->id)>
                                    {{ $farm->name }} — {{ \App\Support\FarmerAnimalType::label($row->type) }} #{{ $row->id }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('livestock_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="batch_reference" :value="__('Batch reference (optional if livestock set)')" />
                    <x-text-input id="batch_reference" name="batch_reference" type="text" class="mt-1 block w-full" :value="old('batch_reference')" />
                    <x-input-error :messages="$errors->get('batch_reference')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="issued_by" :value="__('Issued by')" />
                    <x-text-input id="issued_by" name="issued_by" type="text" class="mt-1 block w-full" :value="old('issued_by')" required />
                    <x-input-error :messages="$errors->get('issued_by')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="issue_date" :value="__('Issue date')" />
                    <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full" :value="old('issue_date', now()->toDateString())" required />
                    <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="expiry_date" :value="__('Expiry date')" />
                    <x-text-input id="expiry_date" name="expiry_date" type="date" class="mt-1 block w-full" :value="old('expiry_date')" />
                    <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300" required>
                        @foreach (\App\Models\FarmerHealthCertificate::STATUSES as $status)
                            <option value="{{ $status }}" @selected(old('status', \App\Models\FarmerHealthCertificate::STATUS_VALID) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="source_health_record_id" :value="__('Source health record (optional)')" />
                    <x-text-input id="source_health_record_id" name="source_health_record_id" type="number" min="1" class="mt-1 block w-full" :value="old('source_health_record_id', request('health_record_id', $healthRecord?->id))" />
                    <x-input-error :messages="$errors->get('source_health_record_id')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="file" :value="__('Certificate file (PDF/image)')" />
                    <input id="file" name="file" type="file" class="mt-1 block w-full rounded-lg border-gray-300" required />
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="notes" :value="__('Notes')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">{{ __('Save certificate') }}</button>
                <a href="{{ route('farmer.health-certificates.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-sm font-semibold rounded-bucha">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>

