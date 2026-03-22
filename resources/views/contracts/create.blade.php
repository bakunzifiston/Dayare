<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">
            {{ __('Add contract') }}
        </h1>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        @if (!isset($category) || !in_array($category, ['employee', 'supplier']))
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
                <h2 class="text-base font-semibold text-slate-800 mb-2">{{ __('Select contract type') }}</h2>
                <p class="text-sm text-slate-600 mb-6">{{ __('Choose whether this contract is for an employee or a supplier. The form will show the relevant fields.') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="{{ route('contracts.create', ['category' => 'employee']) }}" class="flex flex-col items-center justify-center p-6 rounded-xl border-2 border-slate-200 hover:border-bucha-primary hover:bg-indigo-50/50 transition">
                        <span class="text-3xl mb-2">👤</span>
                        <span class="font-semibold text-slate-800">{{ __('Employee contract') }}</span>
                        <span class="text-sm text-slate-500 mt-1">{{ __('Employment, temporary, consultant') }}</span>
                    </a>
                    <a href="{{ route('contracts.create', ['category' => 'supplier']) }}" class="flex flex-col items-center justify-center p-6 rounded-xl border-2 border-slate-200 hover:border-bucha-primary hover:bg-amber-50/50 transition">
                        <span class="text-3xl mb-2">🚚</span>
                        <span class="font-semibold text-slate-800">{{ __('Supplier contract') }}</span>
                        <span class="text-sm text-slate-500 mt-1">{{ __('Supply agreement, livestock supply') }}</span>
                    </a>
                </div>
                <p class="mt-6">
                    <a href="{{ route('contracts.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Back to contracts') }}</a>
                </p>
            </div>
        @else
        <form method="POST" action="{{ route('contracts.store') }}" class="space-y-6" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="contract_category" value="{{ $category }}" />

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Basic contract information') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="business_id" :value="__('Business')" />
                        <select id="business_id" name="business_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" required>
                            <option value="">{{ __('Select business') }}</option>
                            @foreach ($businesses as $b)
                                <option value="{{ $b->id }}" @selected(old('business_id') == $b->id)>{{ $b->business_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('business_id')" />
                    </div>
                    <div>
                        <x-input-label for="contract_number" :value="__('Contract number')" />
                        <x-text-input id="contract_number" name="contract_number" type="text" class="mt-1 block w-full" :value="old('contract_number')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('contract_number')" />
                    </div>
                </div>
                <div>
                    <x-input-label for="title" :value="__('Contract title / name')" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required />
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="type" :value="__('Contract type')" />
                        <select id="type" name="type" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" required>
                            @if ($category === 'employee')
                                @foreach (\App\Models\Contract::EMPLOYEE_TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', 'employment') == $value)>{{ __($label) }}</option>
                                @endforeach
                            @else
                                @foreach (\App\Models\Contract::SUPPLIER_TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', 'supply_agreement') == $value)>{{ __($label) }}</option>
                                @endforeach
                            @endif
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('type')" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Contract status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" required>
                            @foreach (\App\Models\Contract::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'draft') == $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="start_date" :value="__('Start date')" />
                        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
                    </div>
                    <div>
                        <x-input-label for="end_date" :value="__('End date (optional)')" />
                        <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date')" />
                        <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
                    </div>
                </div>
                <div>
                    <x-input-label for="description" :value="__('Contract description (optional)')" />
                    <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">{{ old('description') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('description')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="amount" :value="__('Amount (optional)')" />
                        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('amount')" />
                        <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                    </div>
                    <div>
                        <x-input-label for="renewal_date" :value="__('Renewal date (optional)')" />
                        <x-text-input id="renewal_date" name="renewal_date" type="date" class="mt-1 block w-full" :value="old('renewal_date')" />
                        <x-input-error class="mt-2" :messages="$errors->get('renewal_date')" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="termination_reason" :value="__('Termination reason (optional)')" />
                        <x-text-input id="termination_reason" name="termination_reason" type="text" class="mt-1 block w-full" :value="old('termination_reason')" />
                        <x-input-error class="mt-2" :messages="$errors->get('termination_reason')" />
                    </div>
                    <div>
                        <x-input-label for="contract_owner_id" :value="__('Contract owner (optional)')" />
                        <select id="contract_owner_id" name="contract_owner_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected(old('contract_owner_id') == $u->id)>{{ $u->name ?? $u->email }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('contract_owner_id')" />
                    </div>
                </div>
            </div>

            @if ($category === 'employee')
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-800">{{ __('Employee information') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="employee_id" :value="__('Employee')" />
                            <select id="employee_id" name="employee_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" required>
                                <option value="">{{ __('Select employee') }}</option>
                                @foreach ($employees as $e)
                                    <option value="{{ $e->id }}" @selected(old('employee_id') == $e->id)>{{ trim($e->first_name . ' ' . $e->last_name) }} @if($e->job_title) ({{ \App\Models\Employee::JOB_TITLES[$e->job_title] ?? $e->job_title }}) @endif</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('employee_id')" />
                        </div>
                        <div>
                            <x-input-label for="facility_id" :value="__('Assigned facility')" />
                            <select id="facility_id" name="facility_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="">{{ __('None') }}</option>
                                @foreach ($facilities as $f)
                                    <option value="{{ $f->id }}" @selected(old('facility_id') == $f->id)>{{ $f->facility_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="job_position" :value="__('Job position')" />
                            <x-text-input id="job_position" name="job_position" type="text" class="mt-1 block w-full" :value="old('job_position')" />
                            <x-input-error class="mt-2" :messages="$errors->get('job_position')" />
                        </div>
                        <div>
                            <x-input-label for="department" :value="__('Department')" />
                            <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department')" />
                            <x-input-error class="mt-2" :messages="$errors->get('department')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="supervisor_name" :value="__('Supervisor / reporting manager')" />
                        <x-text-input id="supervisor_name" name="supervisor_name" type="text" class="mt-1 block w-full" :value="old('supervisor_name')" />
                        <x-input-error class="mt-2" :messages="$errors->get('supervisor_name')" />
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-800">{{ __('Employment terms') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="employment_type" :value="__('Employment type')" />
                            <select id="employment_type" name="employment_type" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="">{{ __('Select') }}</option>
                                @foreach (\App\Models\Contract::EMPLOYMENT_TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('employment_type') == $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('employment_type')" />
                        </div>
                        <div>
                            <x-input-label for="work_schedule" :value="__('Work schedule')" />
                            <x-text-input id="work_schedule" name="work_schedule" type="text" class="mt-1 block w-full" :value="old('work_schedule')" />
                            <x-input-error class="mt-2" :messages="$errors->get('work_schedule')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="salary_payment_terms" :value="__('Salary / payment terms (optional)')" />
                        <textarea id="salary_payment_terms" name="salary_payment_terms" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">{{ old('salary_payment_terms') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('salary_payment_terms')" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="working_hours" :value="__('Working hours')" />
                            <x-text-input id="working_hours" name="working_hours" type="text" class="mt-1 block w-full" :value="old('working_hours')" />
                            <x-input-error class="mt-2" :messages="$errors->get('working_hours')" />
                        </div>
                        <div>
                            <x-input-label for="probation_period" :value="__('Probation period')" />
                            <x-text-input id="probation_period" name="probation_period" type="text" class="mt-1 block w-full" :value="old('probation_period')" />
                            <x-input-error class="mt-2" :messages="$errors->get('probation_period')" />
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-800">{{ __('Compliance & requirements') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="medical_certificate_number" :value="__('Medical certificate number')" />
                            <x-text-input id="medical_certificate_number" name="medical_certificate_number" type="text" class="mt-1 block w-full" :value="old('medical_certificate_number')" />
                            <x-input-error class="mt-2" :messages="$errors->get('medical_certificate_number')" />
                        </div>
                        <div>
                            <x-input-label for="medical_certificate_expiry_date" :value="__('Medical certificate expiry date')" />
                            <x-text-input id="medical_certificate_expiry_date" name="medical_certificate_expiry_date" type="date" class="mt-1 block w-full" :value="old('medical_certificate_expiry_date')" />
                            <x-input-error class="mt-2" :messages="$errors->get('medical_certificate_expiry_date')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="safety_training_date" :value="__('Safety training date')" />
                            <x-text-input id="safety_training_date" name="safety_training_date" type="date" class="mt-1 block w-full" :value="old('safety_training_date')" />
                            <x-input-error class="mt-2" :messages="$errors->get('safety_training_date')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="certification_requirements" :value="__('Certification requirements')" />
                        <textarea id="certification_requirements" name="certification_requirements" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">{{ old('certification_requirements') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('certification_requirements')" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="signed_contract_file" :value="__('Signed contract file')" />
                            <input id="signed_contract_file" name="signed_contract_file" type="file" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                            <p class="mt-1 text-xs text-slate-500">{{ __('PDF, DOC, DOCX, JPG, PNG. Max 10 MB.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('signed_contract_file')" />
                        </div>
                        <div>
                            <x-input-label for="supporting_documents" :value="__('Supporting documents')" />
                            <input id="supporting_documents" name="supporting_documents[]" type="file" multiple class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                            <p class="mt-1 text-xs text-slate-500">{{ __('Multiple files. PDF, DOC, DOCX, JPG, PNG. Max 10 MB each.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('supporting_documents')" />
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-800">{{ __('Supplier information') }}</h2>
                    <div>
                        <x-input-label for="supplier_id" :value="__('Supplier')" />
                        <select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary" required>
                            <option value="">{{ __('Select supplier') }}</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')) ?: ('Supplier #'.$s->id) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('supplier_id')" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="farm_name" :value="__('Farm name')" />
                            <x-text-input id="farm_name" name="farm_name" type="text" class="mt-1 block w-full" :value="old('farm_name')" />
                            <x-input-error class="mt-2" :messages="$errors->get('farm_name')" />
                        </div>
                        <div>
                            <x-input-label for="farm_registration_number" :value="__('Farm registration number')" />
                            <x-text-input id="farm_registration_number" name="farm_registration_number" type="text" class="mt-1 block w-full" :value="old('farm_registration_number')" />
                            <x-input-error class="mt-2" :messages="$errors->get('farm_registration_number')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="supplier_contact_person" :value="__('Supplier contact person')" />
                            <x-text-input id="supplier_contact_person" name="supplier_contact_person" type="text" class="mt-1 block w-full" :value="old('supplier_contact_person')" />
                            <x-input-error class="mt-2" :messages="$errors->get('supplier_contact_person')" />
                        </div>
                        <div>
                            <x-input-label for="supplier_phone" :value="__('Phone number')" />
                            <x-text-input id="supplier_phone" name="supplier_phone" type="text" class="mt-1 block w-full" :value="old('supplier_phone')" />
                            <x-input-error class="mt-2" :messages="$errors->get('supplier_phone')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="supplier_email" :value="__('Email address')" />
                            <x-text-input id="supplier_email" name="supplier_email" type="email" class="mt-1 block w-full" :value="old('supplier_email')" />
                            <x-input-error class="mt-2" :messages="$errors->get('supplier_email')" />
                        </div>
                        <div class="sm:col-span-1">
                            <x-input-label for="location_district" :value="__('Location (district / sector)')" />
                            <div class="flex gap-2 mt-1">
                                <x-text-input id="location_district" name="location_district" type="text" class="block w-full" :value="old('location_district')" placeholder="{{ __('District') }}" />
                                <x-text-input id="location_sector" name="location_sector" type="text" class="block w-full" :value="old('location_sector')" placeholder="{{ __('Sector') }}" />
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('location_district')" />
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-800">{{ __('Supply details') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="species_covered" :value="__('Species covered')" />
                            <x-text-input id="species_covered" name="species_covered" type="text" class="mt-1 block w-full" :value="old('species_covered')" placeholder="e.g. Cattle, Goat, Sheep, Poultry" />
                            <x-input-error class="mt-2" :messages="$errors->get('species_covered')" />
                        </div>
                        <div>
                            <x-input-label for="estimated_quantity" :value="__('Estimated quantity to supply')" />
                            <x-text-input id="estimated_quantity" name="estimated_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('estimated_quantity')" />
                            <x-input-error class="mt-2" :messages="$errors->get('estimated_quantity')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="delivery_frequency" :value="__('Delivery frequency')" />
                            <select id="delivery_frequency" name="delivery_frequency" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="">{{ __('Select') }}</option>
                                @foreach (\App\Models\Contract::DELIVERY_FREQUENCIES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('delivery_frequency') == $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('delivery_frequency')" />
                        </div>
                        <div>
                            <x-input-label for="facility_id" :value="__('Delivery location (facility)')" />
                            <select id="facility_id" name="facility_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="">{{ __('None') }}</option>
                                @foreach ($facilities as $f)
                                    <option value="{{ $f->id }}" @selected(old('facility_id') == $f->id)>{{ $f->facility_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-800">{{ __('Compliance requirements') }}</h2>
                    <div>
                        <x-input-label for="animal_health_cert_requirement" :value="__('Animal health certificate requirement')" />
                        <textarea id="animal_health_cert_requirement" name="animal_health_cert_requirement" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">{{ old('animal_health_cert_requirement') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('animal_health_cert_requirement')" />
                    </div>
                    <div>
                        <x-input-label for="veterinary_inspection_requirement" :value="__('Veterinary inspection requirement')" />
                        <textarea id="veterinary_inspection_requirement" name="veterinary_inspection_requirement" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">{{ old('veterinary_inspection_requirement') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('veterinary_inspection_requirement')" />
                    </div>
                    <div>
                        <x-input-label for="animal_welfare_compliance" :value="__('Animal welfare compliance')" />
                        <textarea id="animal_welfare_compliance" name="animal_welfare_compliance" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">{{ old('animal_welfare_compliance') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('animal_welfare_compliance')" />
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-800">{{ __('Logistics') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="transport_responsibility" :value="__('Transport responsibility')" />
                            <select id="transport_responsibility" name="transport_responsibility" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="">{{ __('Select') }}</option>
                                @foreach (\App\Models\Contract::TRANSPORT_RESPONSIBILITY as $value => $label)
                                    <option value="{{ $value }}" @selected(old('transport_responsibility') == $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('transport_responsibility')" />
                        </div>
                        <div>
                            <x-input-label for="vehicle_plate" :value="__('Vehicle plate (optional)')" />
                            <x-text-input id="vehicle_plate" name="vehicle_plate" type="text" class="mt-1 block w-full" :value="old('vehicle_plate')" />
                            <x-input-error class="mt-2" :messages="$errors->get('vehicle_plate')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="driver_name" :value="__('Driver name (optional)')" />
                        <x-text-input id="driver_name" name="driver_name" type="text" class="mt-1 block w-full" :value="old('driver_name')" />
                        <x-input-error class="mt-2" :messages="$errors->get('driver_name')" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="signed_contract_file_supplier" :value="__('Signed contract file')" />
                            <input id="signed_contract_file_supplier" name="signed_contract_file" type="file" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                            <p class="mt-1 text-xs text-slate-500">{{ __('PDF, DOC, DOCX, JPG, PNG. Max 10 MB.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('signed_contract_file')" />
                        </div>
                        <div>
                            <x-input-label for="supporting_documents_supplier" :value="__('Supporting documents')" />
                            <input id="supporting_documents_supplier" name="supporting_documents[]" type="file" multiple class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                            <p class="mt-1 text-xs text-slate-500">{{ __('Multiple files. PDF, DOC, DOCX, JPG, PNG. Max 10 MB each.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('supporting_documents')" />
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div>
                    <x-input-label for="notes" :value="__('Notes (optional)')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-bucha-primary focus:ring-bucha-primary">{{ old('notes') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Create contract') }}
                </button>
                <a href="{{ route('contracts.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-xs text-slate-700 hover:bg-slate-50">
                    {{ __('Change type') }}
                </a>
                <a href="{{ route('contracts.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-xs text-slate-700 hover:bg-slate-50">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
        @endif
    </div>
</x-app-layout>
