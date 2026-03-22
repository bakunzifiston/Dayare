<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Contract') }} — {{ $contract->contract_number }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('contracts.edit', $contract) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Edit') }}</a>
                <form method="POST" action="{{ route('contracts.destroy', $contract) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this contract?') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">{{ __('Delete') }}</button>
                </form>
                <a href="{{ route('contracts.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Back to list') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            @if ($contract->isExpired() && $contract->status !== \App\Models\Contract::STATUS_EXPIRED)
                <div class="p-4 rounded-md bg-amber-50 text-amber-800 border border-amber-200">
                    {{ __('This contract has passed its end date. Consider updating the status to Expired.') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Contract number') }}</dt><dd class="mt-1 text-sm text-slate-900 font-medium">{{ $contract->contract_number }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Category') }}</dt><dd class="mt-1"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ ($contract->contract_category ?? 'supplier') === 'employee' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">{{ \App\Models\Contract::CATEGORIES[$contract->contract_category ?? 'supplier'] ?? $contract->contract_category }}</span></dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Title') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->title }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Type') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->type_label }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Business') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->business?->business_name ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Counterparty') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->counterparty_name }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Status') }}</dt><dd class="mt-1"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                        @if($contract->status === 'active') bg-emerald-50 text-emerald-700
                        @elseif($contract->status === 'draft') bg-slate-100 text-slate-700
                        @elseif($contract->status === 'expired') bg-amber-50 text-amber-700
                        @else bg-slate-100 text-slate-600 @endif">{{ \App\Models\Contract::STATUSES[$contract->status] ?? $contract->status }}</span></dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Start date') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->start_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('End date') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->end_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Amount') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->amount !== null ? number_format($contract->amount, 2) : '—' }}</dd></div>
                    @if ($contract->renewal_date)<div><dt class="text-sm font-medium text-slate-500">{{ __('Renewal date') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->renewal_date->format('d M Y') }}</dd></div>@endif
                    @if ($contract->termination_reason)<div><dt class="text-sm font-medium text-slate-500">{{ __('Termination reason') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->termination_reason }}</dd></div>@endif
                    @if ($contract->contractOwner)<div><dt class="text-sm font-medium text-slate-500">{{ __('Contract owner') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->contractOwner->name ?? $contract->contractOwner->email }}</dd></div>@endif
                    @if ($contract->description)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Description') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->description }}</dd></div>@endif
                    @if ($contract->notes)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Notes') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->notes }}</dd></div>@endif
                </dl>
            </div>

            @if ($contract->isEmployeeContract() && $contract->employee)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                    <h3 class="text-sm font-semibold text-slate-800 mb-3">{{ __('Employee information') }}</h3>
                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div><dt class="text-sm font-medium text-slate-500">{{ __('Employee') }}</dt><dd class="mt-1 text-sm text-slate-900"><a href="{{ route('employees.show', $contract->employee) }}" class="text-bucha-primary hover:underline">{{ $contract->counterparty_name }}</a></dd></div>
                        @if ($contract->facility)<div><dt class="text-sm font-medium text-slate-500">{{ __('Assigned facility') }}</dt><dd class="mt-1 text-sm text-slate-900"><a href="{{ route('businesses.facilities.show', [$contract->facility->business, $contract->facility]) }}" class="text-bucha-primary hover:underline">{{ $contract->facility->facility_name }}</a></dd></div>@endif
                        @if ($contract->job_position)<div><dt class="text-sm font-medium text-slate-500">{{ __('Job position') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->job_position }}</dd></div>@endif
                        @if ($contract->department)<div><dt class="text-sm font-medium text-slate-500">{{ __('Department') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->department }}</dd></div>@endif
                        @if ($contract->supervisor_name)<div><dt class="text-sm font-medium text-slate-500">{{ __('Supervisor') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->supervisor_name }}</dd></div>@endif
                        @if ($contract->employment_type)<div><dt class="text-sm font-medium text-slate-500">{{ __('Employment type') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ \App\Models\Contract::EMPLOYMENT_TYPES[$contract->employment_type] ?? $contract->employment_type }}</dd></div>@endif
                        @if ($contract->work_schedule)<div><dt class="text-sm font-medium text-slate-500">{{ __('Work schedule') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->work_schedule }}</dd></div>@endif
                        @if ($contract->working_hours)<div><dt class="text-sm font-medium text-slate-500">{{ __('Working hours') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->working_hours }}</dd></div>@endif
                        @if ($contract->probation_period)<div><dt class="text-sm font-medium text-slate-500">{{ __('Probation period') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->probation_period }}</dd></div>@endif
                        @if ($contract->salary_payment_terms)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Salary / payment terms') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->salary_payment_terms }}</dd></div>@endif
                        @if ($contract->medical_certificate_number)<div><dt class="text-sm font-medium text-slate-500">{{ __('Medical certificate number') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->medical_certificate_number }}</dd></div>@endif
                        @if ($contract->medical_certificate_expiry_date)<div><dt class="text-sm font-medium text-slate-500">{{ __('Medical cert. expiry') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->medical_certificate_expiry_date->format('d M Y') }}</dd></div>@endif
                        @if ($contract->safety_training_date)<div><dt class="text-sm font-medium text-slate-500">{{ __('Safety training date') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->safety_training_date->format('d M Y') }}</dd></div>@endif
                        @if ($contract->certification_requirements)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Certification requirements') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->certification_requirements }}</dd></div>@endif
                        @if ($contract->signed_contract_file)<div><dt class="text-sm font-medium text-slate-500">{{ __('Signed contract file') }}</dt><dd class="mt-1 text-sm text-slate-900"><a href="{{ route('contracts.file.download', [$contract, 'signed', basename($contract->signed_contract_file)]) }}" class="text-bucha-primary hover:underline inline-flex items-center gap-1">{{ basename($contract->signed_contract_file) }} <span aria-hidden="true">↓</span></a></dd></div>@endif
                        @if ($contract->supporting_documents && count($contract->supporting_documents) > 0)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Supporting documents') }}</dt><dd class="mt-1 text-sm text-slate-900 flex flex-wrap gap-2">@foreach ($contract->supporting_documents as $path)<a href="{{ route('contracts.file.download', [$contract, 'supporting', basename($path)]) }}" class="text-bucha-primary hover:underline inline-flex items-center gap-1">{{ basename($path) }} <span aria-hidden="true">↓</span></a>@endforeach</dd></div>@endif
                    </dl>
                </div>
            @endif

            @if ($contract->isSupplierContract())
                @if ($contract->supplier)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                    <h3 class="text-sm font-semibold text-slate-800 mb-3">{{ __('Supplier information') }}</h3>
                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div><dt class="text-sm font-medium text-slate-500">{{ __('Supplier') }}</dt><dd class="mt-1 text-sm text-slate-900"><a href="{{ route('suppliers.show', $contract->supplier) }}" class="text-bucha-primary hover:underline">{{ $contract->counterparty_name }}</a></dd></div>
                        @if ($contract->farm_name)<div><dt class="text-sm font-medium text-slate-500">{{ __('Farm name') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->farm_name }}</dd></div>@endif
                        @if ($contract->farm_registration_number)<div><dt class="text-sm font-medium text-slate-500">{{ __('Farm registration number') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->farm_registration_number }}</dd></div>@endif
                        @if ($contract->supplier_contact_person)<div><dt class="text-sm font-medium text-slate-500">{{ __('Contact person') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->supplier_contact_person }}</dd></div>@endif
                        @if ($contract->supplier_phone)<div><dt class="text-sm font-medium text-slate-500">{{ __('Phone') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->supplier_phone }}</dd></div>@endif
                        @if ($contract->supplier_email)<div><dt class="text-sm font-medium text-slate-500">{{ __('Email') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->supplier_email }}</dd></div>@endif
                        @if ($contract->location_district || $contract->location_sector)<div><dt class="text-sm font-medium text-slate-500">{{ __('Location') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ trim(($contract->location_district ?? '') . ' / ' . ($contract->location_sector ?? ''), ' /') ?: '—' }}</dd></div>@endif
                        @if ($contract->species_covered)<div><dt class="text-sm font-medium text-slate-500">{{ __('Species covered') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->species_covered }}</dd></div>@endif
                        @if ($contract->estimated_quantity !== null)<div><dt class="text-sm font-medium text-slate-500">{{ __('Estimated quantity') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->estimated_quantity }}</dd></div>@endif
                        @if ($contract->delivery_frequency)<div><dt class="text-sm font-medium text-slate-500">{{ __('Delivery frequency') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ \App\Models\Contract::DELIVERY_FREQUENCIES[$contract->delivery_frequency] ?? $contract->delivery_frequency }}</dd></div>@endif
                        @if ($contract->facility)<div><dt class="text-sm font-medium text-slate-500">{{ __('Delivery location') }}</dt><dd class="mt-1 text-sm text-slate-900"><a href="{{ route('businesses.facilities.show', [$contract->facility->business, $contract->facility]) }}" class="text-bucha-primary hover:underline">{{ $contract->facility->facility_name }}</a></dd></div>@endif
                        @if ($contract->transport_responsibility)<div><dt class="text-sm font-medium text-slate-500">{{ __('Transport responsibility') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ \App\Models\Contract::TRANSPORT_RESPONSIBILITY[$contract->transport_responsibility] ?? $contract->transport_responsibility }}</dd></div>@endif
                        @if ($contract->vehicle_plate)<div><dt class="text-sm font-medium text-slate-500">{{ __('Vehicle plate') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->vehicle_plate }}</dd></div>@endif
                        @if ($contract->driver_name)<div><dt class="text-sm font-medium text-slate-500">{{ __('Driver name') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->driver_name }}</dd></div>@endif
                        @if ($contract->animal_health_cert_requirement)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Animal health cert. requirement') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->animal_health_cert_requirement }}</dd></div>@endif
                        @if ($contract->veterinary_inspection_requirement)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Veterinary inspection requirement') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->veterinary_inspection_requirement }}</dd></div>@endif
                        @if ($contract->animal_welfare_compliance)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Animal welfare compliance') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->animal_welfare_compliance }}</dd></div>@endif
                        @if ($contract->signed_contract_file)<div><dt class="text-sm font-medium text-slate-500">{{ __('Signed contract file') }}</dt><dd class="mt-1 text-sm text-slate-900"><a href="{{ route('contracts.file.download', [$contract, 'signed', basename($contract->signed_contract_file)]) }}" class="text-bucha-primary hover:underline inline-flex items-center gap-1">{{ basename($contract->signed_contract_file) }} <span aria-hidden="true">↓</span></a></dd></div>@endif
                        @if ($contract->supporting_documents && count($contract->supporting_documents) > 0)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Supporting documents') }}</dt><dd class="mt-1 text-sm text-slate-900 flex flex-wrap gap-2">@foreach ($contract->supporting_documents as $path)<a href="{{ route('contracts.file.download', [$contract, 'supporting', basename($path)]) }}" class="text-bucha-primary hover:underline inline-flex items-center gap-1">{{ basename($path) }} <span aria-hidden="true">↓</span></a>@endforeach</dd></div>@endif
                    </dl>
                </div>
                @endif
            @endif

            @if ($contract->facility && !$contract->isEmployeeContract() && !$contract->isSupplierContract())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                    <h3 class="text-sm font-semibold text-slate-800 mb-2">{{ __('Linked facility') }}</h3>
                    <p class="text-sm text-slate-600"><a href="{{ route('businesses.facilities.show', [$contract->facility->business, $contract->facility]) }}" class="text-bucha-primary hover:underline">{{ $contract->facility->facility_name }}</a></p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
