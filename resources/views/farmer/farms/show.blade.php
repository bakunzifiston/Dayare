<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('farmer.farms.index') }}" class="text-sm text-bucha-primary hover:underline">{{ __('← Farms') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-slate-800">{{ $farm->name }}</h2>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        @if (session('status'))
            <div class="rounded-bucha border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <section class="rounded-bucha border border-slate-200/60 bg-white p-6 text-sm shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">{{ __('Farm owner information') }}</h3>
            <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">{{ __('First name') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->business?->owner_first_name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Last name') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->business?->owner_last_name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('National ID') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->business?->owner_national_id ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Phone number') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->business?->contact_phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Email') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->business?->email ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Emergency contact') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->business?->owner_emergency_contact ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Ownership structure') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->business?->ownership_type ? __(ucfirst(str_replace('_', ' ', $farm->business->ownership_type))) : '—' }}</dd>
                </div>
                @if (in_array($farm->business?->ownership_type, ['cooperative', 'company'], true))
                    <div>
                        <dt class="text-slate-500">{{ __('Cooperative or company name') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $farm->business?->business_name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">{{ __('TIN') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $farm->business?->tax_id ?: '—' }}</dd>
                    </div>
                @endif
                @if (in_array($farm->business?->ownership_type, ['cooperative', 'company'], true) && $farm->business?->ownershipMembers?->isNotEmpty())
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">{{ $farm->business->ownership_type === 'cooperative' ? __('Cooperative members') : __('Company members') }}</dt>
                        <dd class="mt-2 space-y-2">
                            @foreach ($farm->business->ownershipMembers->sortBy('sort_order') as $member)
                                <div class="rounded-lg border border-slate-200 bg-slate-50/70 px-3 py-2">
                                    <p class="font-medium text-slate-900">{{ trim($member->first_name.' '.$member->last_name) ?: '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-600">
                                        {{ __('Date of birth') }}: {{ $member->date_of_birth?->toDateString() ?: '—' }}
                                        · {{ __('Phone number') }}: {{ $member->phone ?: '—' }}
                                        · {{ __('Gender') }}: {{ $member->gender ? __(ucfirst($member->gender)) : '—' }}
                                    </p>
                                </div>
                            @endforeach
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-slate-500">{{ __('Date of birth') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->business?->owner_dob?->toDateString() ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Gender') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->business?->owner_gender ? __(ucfirst($farm->business->owner_gender)) : '—' }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-bucha border border-slate-200/60 bg-white p-6 text-sm shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">{{ __('Farm information') }}</h3>
            <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">{{ __('Business') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->business?->business_name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Farm code / registration number') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->registration_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('GPS coordinates') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        @if ($farm->gps_latitude !== null && $farm->gps_longitude !== null)
                            {{ number_format((float) $farm->gps_latitude, 6) }}, {{ number_format((float) $farm->gps_longitude, 6) }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Farm size (hectares)') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->farm_size_hectares !== null ? number_format((float) $farm->farm_size_hectares, 2) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Ownership type') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->land_ownership_type ? __(ucfirst(str_replace('_', ' ', $farm->land_ownership_type))) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Registration date') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $farm->registration_date?->toDateString() ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Farm status') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ __(ucfirst($farm->status)) }}</dd>
                </div>
                @if ($farm->animal_types)
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">{{ __('Animal types') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ collect($farm->animal_types)->map(fn ($t) => \App\Support\FarmerAnimalType::label($t))->join(', ') }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('farmer.farms.livestock.index', $farm) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">{{ __('Manage livestock') }}</a>
            <a href="{{ route('farmer.farms.health-records.index', $farm) }}" class="inline-flex items-center px-4 py-2 border border-slate-300 rounded-bucha text-sm">{{ __('Farm health') }}</a>
            <a href="{{ route('farmer.farms.edit', $farm) }}" class="inline-flex items-center px-4 py-2 border border-slate-300 rounded-bucha text-sm">{{ __('Edit farm') }}</a>
        </div>

        <form method="post" action="{{ route('farmer.farms.destroy', $farm) }}" onsubmit="return confirm('{{ __('Delete this farm?') }}');">
            @csrf
            @method('delete')
            <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('Delete farm') }}</button>
        </form>
    </div>
</x-app-layout>
