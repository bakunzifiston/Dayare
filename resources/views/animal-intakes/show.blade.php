<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Animal intake') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $intake->intakeDatetimeLabel() }}</p>
                    <p class="text-xs text-slate-500">{{ $intake->facility->facility_name ?? '—' }} · {{ ucfirst($intake->status) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('animal-intakes.edit', $intake) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                    @if ($intake->isPlannableForSlaughter() && $intake->remainingAnimalsAvailable() > 0)
                        <a href="{{ route('slaughter-plans.create') }}?animal_intake_id={{ $intake->id }}&facility_id={{ $intake->facility_id }}" class="inline-flex h-8 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Schedule slaughter') }}</a>
                    @endif
                    <form method="POST" action="{{ route('animal-intakes.destroy', $intake) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this animal intake? This cannot be undone.') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-medium text-red-700 hover:bg-red-100">{{ __('Delete') }}</button>
                    </form>
                    <a href="{{ route('animal-intakes.hub') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <div><dt class="text-xs text-slate-500">{{ __('Facility') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->facility->facility_name ?? '' }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Intake date & time') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->intakeDatetimeLabel() }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Source type') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->isSupplierSource() ? __('Supplier (legacy)') : __('Client') }}</dd></div>
                @if ($intake->source_type === \App\Models\AnimalIntake::SOURCE_TYPE_CLIENT)
                    <div><dt class="text-xs text-slate-500">{{ __('Client') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->client?->name ?? '—' }}</dd></div>
                @else
                    <div><dt class="text-xs text-slate-500">{{ __('Supplier') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->supplier_firstname }} {{ $intake->supplier_lastname }}@if ($intake->supplier)<a href="{{ route('suppliers.show', $intake->supplier) }}" class="ml-2 text-bucha-primary hover:underline">{{ __('View supplier') }}</a>@endif</dd></div>
                    @if ($intake->contract)<div><dt class="text-xs text-slate-500">{{ __('Supplier contract') }}</dt><dd class="mt-0.5 text-sm text-slate-900"><a href="{{ route('contracts.show', $intake->contract) }}" class="text-bucha-primary hover:underline">{{ $intake->contract->contract_number }} — {{ $intake->contract->title }}</a></dd></div>@endif
                @endif
                <div><dt class="text-xs text-slate-500">{{ $intake->isSupplierSource() ? __('Supplier contact') : __('Client contact') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->supplier_contact ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Farm name') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->farm_name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Farm registration number') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->farm_registration_number ?? '—' }}</dd></div>
                @if ($intake->source_type === \App\Models\AnimalIntake::SOURCE_TYPE_CLIENT)
                    <div><dt class="text-xs text-slate-500">{{ __('Movement permit (document)') }}</dt><dd class="mt-0.5 text-sm text-slate-900">@if ($intake->movementPermitDocumentUrl())<a href="{{ $intake->movementPermitDocumentUrl() }}" target="_blank" rel="noopener noreferrer" class="text-bucha-primary hover:underline">{{ __('View uploaded permit') }}</a>@else {{ __('Not uploaded') }} @endif</dd></div>
                    <div><dt class="text-xs text-slate-500">{{ __('Receipt (document)') }}</dt><dd class="mt-0.5 text-sm text-slate-900">@if ($intake->receiptDocumentUrl())<a href="{{ $intake->receiptDocumentUrl() }}" target="_blank" rel="noopener noreferrer" class="text-bucha-primary hover:underline">{{ __('View uploaded receipt') }}</a>@else {{ __('Not uploaded') }} @endif</dd></div>
                @else
                    <div><dt class="text-xs text-slate-500">{{ __('Movement permit No') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->movement_permit_no ?? '—' }}</dd></div>
                @endif
                <div><dt class="text-xs text-slate-500">{{ __('Origin (location)') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->village?->name ?? $intake->sector?->name ?? $intake->district?->name ?? $intake->province?->name ?? $intake->country?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Species') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ __($intake->species) }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Tag number') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->species_ear_tag ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Sex') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->sex ? ucfirst($intake->sex) : '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Age') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->age ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Number of animals') }}</dt><dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ $intake->number_of_animals }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Remaining (for slaughter)') }}</dt><dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ $intake->remainingAnimalsAvailable() }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Unit price') }}</dt><dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ $intake->unit_price !== null ? number_format($intake->unit_price, 2) : '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Total price') }}</dt><dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ $intake->total_price !== null ? number_format($intake->total_price, 2) : '—' }}</dd></div>
                @if ($intake->source_type === \App\Models\AnimalIntake::SOURCE_TYPE_SUPPLIER)
                    <div><dt class="text-xs text-slate-500">{{ __('Vehicle plate') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->transport_vehicle_plate ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">{{ __('Driver name') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->driver_name ?? '—' }}</dd></div>
                @endif
                <div><dt class="text-xs text-slate-500">{{ __('Observation') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->observation ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Meat inspector name') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ $intake->meat_inspector_name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">{{ __('Status') }}</dt><dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst($intake->status) }}</dd></div>
            </dl>
        </section>

        @if ($intake->slaughterPlans->isNotEmpty())
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Slaughter plans') }}</p>
                </div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($intake->slaughterPlans as $plan)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <a href="{{ route('slaughter-plans.show', $plan) }}" class="text-sm font-medium text-bucha-primary hover:underline">{{ $plan->slaughter_date->format('d M Y') }}</a>
                            <span class="text-xs text-slate-500">{{ $plan->number_of_animals_scheduled }} {{ __('animals') }} · {{ ucfirst($plan->status) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-app-layout>
