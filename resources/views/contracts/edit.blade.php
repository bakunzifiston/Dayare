<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h1 class="text-lg font-semibold text-slate-900">
                {{ __('Edit contract') }}
            </h1>
            <a href="{{ route('contracts.show', $contract) }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Back to contract') }}</a>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('contracts.update', $contract) }}" class="space-y-6" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="contract_category" value="{{ $contract->contract_category ?? 'supplier' }}" />

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Basic contract information') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="business_id" :value="__('Business')" />
                        <select id="business_id" name="business_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]" required>
                            @foreach ($businesses as $b)
                                <option value="{{ $b->id }}" @selected(old('business_id', $contract->business_id) == $b->id)>{{ $b->business_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('business_id')" />
                    </div>
                    <div>
                        <x-input-label for="contract_number" :value="__('Contract number')" />
                        <x-text-input id="contract_number" name="contract_number" type="text" class="mt-1 block w-full" :value="old('contract_number', $contract->contract_number)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('contract_number')" />
                    </div>
                </div>
                <div>
                    <x-input-label for="title" :value="__('Title')" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $contract->title)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <select id="type" name="type" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]" required>
                            @if (($contract->contract_category ?? 'supplier') === 'employee')
                                @foreach (\App\Models\Contract::EMPLOYEE_TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $contract->type) == $value)>{{ __($label) }}</option>
                                @endforeach
                            @else
                                @foreach (\App\Models\Contract::SUPPLIER_TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $contract->type) == $value)>{{ __($label) }}</option>
                                @endforeach
                            @endif
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('type')" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]" required>
                            @foreach (\App\Models\Contract::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $contract->status) == $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="start_date" :value="__('Start date')" />
                        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', $contract->start_date?->format('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
                    </div>
                    <div>
                        <x-input-label for="end_date" :value="__('End date (optional)')" />
                        <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date', $contract->end_date?->format('Y-m-d'))" />
                        <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
                    </div>
                </div>
                <div>
                    <x-input-label for="description" :value="__('Description (optional)')" />
                    <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">{{ old('description', $contract->description) }}</textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="amount" :value="__('Amount')" />
                        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('amount', $contract->amount)" />
                    </div>
                    <div>
                        <x-input-label for="renewal_date" :value="__('Renewal date')" />
                        <x-text-input id="renewal_date" name="renewal_date" type="date" class="mt-1 block w-full" :value="old('renewal_date', $contract->renewal_date?->format('Y-m-d'))" />
                    </div>
                    <div>
                        <x-input-label for="termination_reason" :value="__('Termination reason')" />
                        <x-text-input id="termination_reason" name="termination_reason" type="text" class="mt-1 block w-full" :value="old('termination_reason', $contract->termination_reason)" />
                    </div>
                </div>
                <div>
                    <x-input-label for="contract_owner_id" :value="__('Contract owner')" />
                    <select id="contract_owner_id" name="contract_owner_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected(old('contract_owner_id', $contract->contract_owner_id) == $u->id)>{{ $u->name ?? $u->email }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if (($contract->contract_category ?? 'supplier') === 'employee')
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Employee information') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="employee_id" :value="__('Employee')" />
                        <select id="employee_id" name="employee_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]" required>
                            @foreach ($employees as $e)
                                <option value="{{ $e->id }}" @selected(old('employee_id', $contract->employee_id) == $e->id)>{{ trim($e->first_name . ' ' . $e->last_name) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('employee_id')" />
                    </div>
                    <div>
                        <x-input-label for="facility_id" :value="__('Assigned facility')" />
                        <select id="facility_id" name="facility_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f->id }}" @selected(old('facility_id', $contract->facility_id) == $f->id)>{{ $f->facility_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label for="job_position" :value="__('Job position')" /><x-text-input id="job_position" name="job_position" type="text" class="mt-1 block w-full" :value="old('job_position', $contract->job_position)" /></div>
                    <div><x-input-label for="department" :value="__('Department')" /><x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department', $contract->department)" /></div>
                </div>
                <div><x-input-label for="supervisor_name" :value="__('Supervisor')" /><x-text-input id="supervisor_name" name="supervisor_name" type="text" class="mt-1 block w-full" :value="old('supervisor_name', $contract->supervisor_name)" /></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="employment_type" :value="__('Employment type')" />
                        <select id="employment_type" name="employment_type" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">
                            <option value="">{{ __('Select') }}</option>
                            @foreach (\App\Models\Contract::EMPLOYMENT_TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('employment_type', $contract->employment_type) == $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><x-input-label for="work_schedule" :value="__('Work schedule')" /><x-text-input id="work_schedule" name="work_schedule" type="text" class="mt-1 block w-full" :value="old('work_schedule', $contract->work_schedule)" /></div>
                </div>
                <div><x-input-label for="salary_payment_terms" :value="__('Salary / payment terms')" /><textarea id="salary_payment_terms" name="salary_payment_terms" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">{{ old('salary_payment_terms', $contract->salary_payment_terms) }}</textarea></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label for="working_hours" :value="__('Working hours')" /><x-text-input id="working_hours" name="working_hours" type="text" class="mt-1 block w-full" :value="old('working_hours', $contract->working_hours)" /></div>
                    <div><x-input-label for="probation_period" :value="__('Probation period')" /><x-text-input id="probation_period" name="probation_period" type="text" class="mt-1 block w-full" :value="old('probation_period', $contract->probation_period)" /></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label for="medical_certificate_number" :value="__('Medical certificate number')" /><x-text-input id="medical_certificate_number" name="medical_certificate_number" type="text" class="mt-1 block w-full" :value="old('medical_certificate_number', $contract->medical_certificate_number)" /></div>
                    <div><x-input-label for="medical_certificate_expiry_date" :value="__('Medical cert. expiry')" /><x-text-input id="medical_certificate_expiry_date" name="medical_certificate_expiry_date" type="date" class="mt-1 block w-full" :value="old('medical_certificate_expiry_date', $contract->medical_certificate_expiry_date?->format('Y-m-d'))" /></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label for="safety_training_date" :value="__('Safety training date')" /><x-text-input id="safety_training_date" name="safety_training_date" type="date" class="mt-1 block w-full" :value="old('safety_training_date', $contract->safety_training_date?->format('Y-m-d'))" /></div>
                </div>
                <div><x-input-label for="certification_requirements" :value="__('Certification requirements')" /><textarea id="certification_requirements" name="certification_requirements" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">{{ old('certification_requirements', $contract->certification_requirements) }}</textarea></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="signed_contract_file" :value="__('Signed contract file')" />
                        @if ($contract->signed_contract_file)
                            <p class="mt-1 text-sm text-slate-600">{{ __('Current:') }} <a href="{{ route('contracts.file.download', [$contract, 'signed', basename($contract->signed_contract_file)]) }}" class="text-indigo-600 hover:underline">{{ basename($contract->signed_contract_file) }}</a></p>
                        @endif
                        <input id="signed_contract_file" name="signed_contract_file" type="file" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                        <p class="mt-1 text-xs text-slate-500">{{ __('Leave empty to keep current. PDF, DOC, DOCX, JPG, PNG. Max 10 MB.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('signed_contract_file')" />
                    </div>
                    <div>
                        <x-input-label for="supporting_documents" :value="__('Supporting documents')" />
                        @if ($contract->supporting_documents && count($contract->supporting_documents) > 0)
                            <p class="mt-1 text-sm text-slate-600">{{ __('Current:') }} @foreach ($contract->supporting_documents as $path) <a href="{{ route('contracts.file.download', [$contract, 'supporting', basename($path)]) }}" class="text-indigo-600 hover:underline mr-2">{{ basename($path) }}</a> @endforeach</p>
                        @endif
                        <input id="supporting_documents" name="supporting_documents[]" type="file" multiple class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                        <p class="mt-1 text-xs text-slate-500">{{ __('Add more files. Existing files are kept. PDF, DOC, DOCX, JPG, PNG. Max 10 MB each.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('supporting_documents')" />
                    </div>
                </div>
            </div>
            @else
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Supplier information') }}</h2>
                <div>
                    <x-input-label for="supplier_id" :value="__('Supplier')" />
                    <select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]" required>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id', $contract->supplier_id) == $s->id)>{{ trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')) ?: ('Supplier #'.$s->id) }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('supplier_id')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label for="farm_name" :value="__('Farm name')" /><x-text-input id="farm_name" name="farm_name" type="text" class="mt-1 block w-full" :value="old('farm_name', $contract->farm_name)" /></div>
                    <div><x-input-label for="farm_registration_number" :value="__('Farm registration number')" /><x-text-input id="farm_registration_number" name="farm_registration_number" type="text" class="mt-1 block w-full" :value="old('farm_registration_number', $contract->farm_registration_number)" /></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label for="supplier_contact_person" :value="__('Contact person')" /><x-text-input id="supplier_contact_person" name="supplier_contact_person" type="text" class="mt-1 block w-full" :value="old('supplier_contact_person', $contract->supplier_contact_person)" /></div>
                    <div><x-input-label for="supplier_phone" :value="__('Phone')" /><x-text-input id="supplier_phone" name="supplier_phone" type="text" class="mt-1 block w-full" :value="old('supplier_phone', $contract->supplier_phone)" /></div>
                </div>
                <div><x-input-label for="supplier_email" :value="__('Email')" /><x-text-input id="supplier_email" name="supplier_email" type="email" class="mt-1 block w-full" :value="old('supplier_email', $contract->supplier_email)" /></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label for="location_district" :value="__('District')" /><x-text-input id="location_district" name="location_district" type="text" class="mt-1 block w-full" :value="old('location_district', $contract->location_district)" /></div>
                    <div><x-input-label for="location_sector" :value="__('Sector')" /><x-text-input id="location_sector" name="location_sector" type="text" class="mt-1 block w-full" :value="old('location_sector', $contract->location_sector)" /></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label for="species_covered" :value="__('Species covered')" /><x-text-input id="species_covered" name="species_covered" type="text" class="mt-1 block w-full" :value="old('species_covered', $contract->species_covered)" /></div>
                    <div><x-input-label for="estimated_quantity" :value="__('Estimated quantity')" /><x-text-input id="estimated_quantity" name="estimated_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('estimated_quantity', $contract->estimated_quantity)" /></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="delivery_frequency" :value="__('Delivery frequency')" />
                        <select id="delivery_frequency" name="delivery_frequency" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">
                            <option value="">{{ __('Select') }}</option>
                            @foreach (\App\Models\Contract::DELIVERY_FREQUENCIES as $value => $label)
                                <option value="{{ $value }}" @selected(old('delivery_frequency', $contract->delivery_frequency) == $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="facility_id" :value="__('Delivery location (facility)')" />
                        <select id="facility_id" name="facility_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f->id }}" @selected(old('facility_id', $contract->facility_id) == $f->id)>{{ $f->facility_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div><x-input-label for="animal_health_cert_requirement" :value="__('Animal health cert. requirement')" /><textarea id="animal_health_cert_requirement" name="animal_health_cert_requirement" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">{{ old('animal_health_cert_requirement', $contract->animal_health_cert_requirement) }}</textarea></div>
                <div><x-input-label for="veterinary_inspection_requirement" :value="__('Veterinary inspection requirement')" /><textarea id="veterinary_inspection_requirement" name="veterinary_inspection_requirement" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">{{ old('veterinary_inspection_requirement', $contract->veterinary_inspection_requirement) }}</textarea></div>
                <div><x-input-label for="animal_welfare_compliance" :value="__('Animal welfare compliance')" /><textarea id="animal_welfare_compliance" name="animal_welfare_compliance" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">{{ old('animal_welfare_compliance', $contract->animal_welfare_compliance) }}</textarea></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="transport_responsibility" :value="__('Transport responsibility')" />
                        <select id="transport_responsibility" name="transport_responsibility" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">
                            <option value="">{{ __('Select') }}</option>
                            @foreach (\App\Models\Contract::TRANSPORT_RESPONSIBILITY as $value => $label)
                                <option value="{{ $value }}" @selected(old('transport_responsibility', $contract->transport_responsibility) == $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><x-input-label for="vehicle_plate" :value="__('Vehicle plate')" /><x-text-input id="vehicle_plate" name="vehicle_plate" type="text" class="mt-1 block w-full" :value="old('vehicle_plate', $contract->vehicle_plate)" /></div>
                </div>
                <div><x-input-label for="driver_name" :value="__('Driver name')" /><x-text-input id="driver_name" name="driver_name" type="text" class="mt-1 block w-full" :value="old('driver_name', $contract->driver_name)" /></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="signed_contract_file_supplier" :value="__('Signed contract file')" />
                        @if ($contract->signed_contract_file)
                            <p class="mt-1 text-sm text-slate-600">{{ __('Current:') }} <a href="{{ route('contracts.file.download', [$contract, 'signed', basename($contract->signed_contract_file)]) }}" class="text-indigo-600 hover:underline">{{ basename($contract->signed_contract_file) }}</a></p>
                        @endif
                        <input id="signed_contract_file_supplier" name="signed_contract_file" type="file" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                        <p class="mt-1 text-xs text-slate-500">{{ __('Leave empty to keep current. PDF, DOC, DOCX, JPG, PNG. Max 10 MB.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('signed_contract_file')" />
                    </div>
                    <div>
                        <x-input-label for="supporting_documents_supplier" :value="__('Supporting documents')" />
                        @if ($contract->supporting_documents && count($contract->supporting_documents) > 0)
                            <p class="mt-1 text-sm text-slate-600">{{ __('Current:') }} @foreach ($contract->supporting_documents as $path) <a href="{{ route('contracts.file.download', [$contract, 'supporting', basename($path)]) }}" class="text-indigo-600 hover:underline mr-2">{{ basename($path) }}</a> @endforeach</p>
                        @endif
                        <input id="supporting_documents_supplier" name="supporting_documents[]" type="file" multiple class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                        <p class="mt-1 text-xs text-slate-500">{{ __('Add more files. Existing files are kept. PDF, DOC, DOCX, JPG, PNG. Max 10 MB each.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('supporting_documents')" />
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div>
                    <x-input-label for="notes" :value="__('Notes (optional)')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">{{ old('notes', $contract->notes) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                    {{ __('Update contract') }}
                </button>
                <a href="{{ route('contracts.show', $contract) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-xs text-slate-700 hover:bg-slate-50">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
