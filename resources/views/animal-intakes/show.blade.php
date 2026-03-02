<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Animal intake') }} — {{ $intake->intake_date->format('d M Y') }} · {{ $intake->facility->facility_name ?? '' }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('animal-intakes.edit', $intake) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Edit') }}</a>
                @if ($intake->status === \App\Models\AnimalIntake::STATUS_APPROVED && !$intake->isHealthCertificateExpired() && $intake->remainingAnimalsAvailable() > 0)
                    <a href="{{ route('slaughter-plans.create') }}?animal_intake_id={{ $intake->id }}&facility_id={{ $intake->facility_id }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Schedule slaughter') }}</a>
                @endif
                <a href="{{ route('animal-intakes.index') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Back to list') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            @if ($intake->isHealthCertificateExpired())
                <div class="p-4 rounded-md bg-amber-50 text-amber-800 border border-amber-200">
                    {{ __('Health certificate has expired. Slaughter cannot be scheduled until certificate is renewed.') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Facility') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->facility->facility_name ?? '' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Intake date') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->intake_date->format('d M Y') }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Supplier') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->supplier_firstname }} {{ $intake->supplier_lastname }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Supplier contact') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->supplier_contact ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Farm name') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->farm_name ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Farm registration number') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->farm_registration_number ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Origin (location)') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->village?->name ?? $intake->sector?->name ?? $intake->district?->name ?? $intake->province?->name ?? $intake->country?->name ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Species') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->species }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Number of animals') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->number_of_animals }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Remaining (for slaughter)') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->remainingAnimalsAvailable() }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Unit price') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->unit_price !== null ? number_format($intake->unit_price, 2) : '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Total price') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->total_price !== null ? number_format($intake->total_price, 2) : '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Vehicle plate') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->transport_vehicle_plate ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Driver name') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->driver_name ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Health certificate number') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->animal_health_certificate_number ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Health cert. issue date') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->health_certificate_issue_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Health cert. expiry date') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $intake->health_certificate_expiry_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Status') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ ucfirst($intake->status) }}</dd></div>
                </dl>
            </div>

            @if ($intake->slaughterPlans->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                    <h3 class="text-lg font-medium text-slate-900 mb-4">{{ __('Slaughter plans linked to this intake') }}</h3>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($intake->slaughterPlans as $plan)
                            <li class="py-2">
                                <a href="{{ route('slaughter-plans.show', $plan) }}" class="font-medium text-indigo-600 hover:underline">{{ $plan->slaughter_date->format('d M Y') }}</a>
                                <span class="text-sm text-slate-500"> — {{ $plan->number_of_animals_scheduled }} {{ __('animals') }} · {{ ucfirst($plan->status) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
