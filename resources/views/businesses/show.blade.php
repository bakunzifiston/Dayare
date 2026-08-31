<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Businesses') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $business->business_name }}</p>
                    <p class="text-xs text-slate-500">{{ $business->registration_number }} · {{ ucfirst($business->status) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('businesses.facilities.index', $business) }}" class="inline-flex h-8 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Facilities') }}</a>
                    <a href="{{ route('businesses.edit', $business) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                    <form method="POST" action="{{ route('businesses.destroy', $business) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this business? This cannot be undone.') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-medium text-red-700 hover:bg-red-100">{{ __('Delete') }}</button>
                    </form>
                    <a href="{{ route('businesses.index') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <div class="px-4 py-4">
                <p class="mb-3 text-sm font-semibold text-slate-900">{{ __('Business info') }}</p>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Registration number') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->registration_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Tax ID') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->tax_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Contact phone') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->contact_phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Email') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Status') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst($business->status) }}</dd>
                    </div>
                    @if ($business->business_size)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Business size') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst($business->business_size) }}</dd>
                    </div>
                    @endif
                    @if ($business->baseline_revenue !== null && $business->baseline_revenue !== '')
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Baseline annual revenue (RWF)') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ \App\Models\Business::baselineRevenueBracketOptions()[$business->baseline_revenue] ?? $business->baseline_revenue }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </section>

            @if ($business->owner_first_name || $business->owner_last_name || $business->owner_name || $business->owner_phone || $business->owner_email || $business->ownership_type || $business->owner_gender || $business->owner_pwd_status || $business->ownershipMembers->isNotEmpty())
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Ownership info') }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Owner or legal representative details.') }}</p>
                </div>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                    @if ($business->owner_first_name || $business->owner_last_name)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500">{{ __('Owner / representative name') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ trim($business->owner_first_name . ' ' . $business->owner_last_name) ?: $business->owner_name }}</dd>
                    </div>
                    @elseif ($business->owner_name)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500">{{ __('Owner / representative name') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->owner_name }}</dd>
                    </div>
                    @endif
                    @if ($business->owner_dob)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Date of birth') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->owner_dob->format('d/m/Y') }}</dd>
                    </div>
                    @endif
                    @if ($business->owner_gender)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Owner gender') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst($business->owner_gender) }}</dd>
                    </div>
                    @endif
                    @if ($business->owner_pwd_status)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Disability status') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst($business->owner_pwd_status) }}</dd>
                    </div>
                    @endif
                    @if ($business->owner_phone)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Owner phone') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->owner_phone }}</dd>
                    </div>
                    @endif
                    @if ($business->owner_email)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Owner email') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->owner_email }}</dd>
                    </div>
                    @endif
                    @if ($business->ownership_type)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Ownership type') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst(str_replace('_', ' ', $business->ownership_type)) }}</dd>
                    </div>
                    @endif
                </dl>
                @if ($business->ownershipMembers->isNotEmpty())
                <div class="border-t border-slate-100 px-4 py-4">
                    <h4 class="text-sm font-semibold text-slate-700 mb-3">
                        @if ($business->ownership_type === 'partnership')
                            {{ __('Partnership members') }}
                        @elseif ($business->ownership_type === 'cooperative')
                            {{ __('Cooperative members') }}
                        @else
                            {{ __('Company members') }}
                        @endif
                    </h4>
                    <ul class="space-y-2">
                        @foreach ($business->ownershipMembers as $member)
                        <li class="flex justify-between items-start text-sm">
                            <div class="text-gray-900">
                                <div>{{ $member->full_name }}</div>
                                @if ($member->gender || $member->pwd_status)
                                <div class="text-xs text-gray-500">
                                    {{ $member->gender ? ucfirst($member->gender) : '' }}{{ $member->gender && $member->pwd_status ? ' · ' : '' }}{{ $member->pwd_status ? ucfirst($member->pwd_status) : '' }}
                                </div>
                                @endif
                                @if ($member->phone || $member->email)
                                <div class="text-xs text-gray-500">
                                    {{ $member->phone ?? '' }}{{ $member->phone && $member->email ? ' · ' : '' }}{{ $member->email ?? '' }}
                                </div>
                                @endif
                            </div>
                            @if ($member->date_of_birth)
                            <span class="text-gray-500">{{ $member->date_of_birth->format('d/m/Y') }}</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </section>
            @endif

            @if ($business->vibe_unique_id || $business->vibe_commencement_date || $business->pathway_status || $business->vibe_comments)
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('VIBE metadata') }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Progress tracking details for the VIBE pathway.') }}</p>
                </div>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                    @if ($business->vibe_unique_id)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('VIBE unique ID') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->vibe_unique_id }}</dd>
                    </div>
                    @endif
                    @if ($business->vibe_commencement_date)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('VIBE commencement date') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->vibe_commencement_date->format('d/m/Y') }}</dd>
                    </div>
                    @endif
                    @if ($business->pathway_status)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Pathway status') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst($business->pathway_status) }}</dd>
                    </div>
                    @endif
                    @if ($business->vibe_comments)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500">{{ __('VIBE comments') }}</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm text-slate-900">{{ $business->vibe_comments }}</dd>
                    </div>
                    @endif
                </dl>
            </section>
            @endif

            @if ($business->country_id || $business->city || $business->country)
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Location info') }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Business location.') }}</p>
                </div>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                    @if ($business->countryDivision)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Country') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->countryDivision->name }}</dd>
                    </div>
                    @endif
                    @if ($business->provinceDivision)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Province') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->provinceDivision->name }}</dd>
                    </div>
                    @endif
                    @if ($business->districtDivision)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('District') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->districtDivision->name }}</dd>
                    </div>
                    @endif
                    @if ($business->sectorDivision)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Sector') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->sectorDivision->name }}</dd>
                    </div>
                    @endif
                    @if ($business->cellDivision)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Cell') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->cellDivision->name }}</dd>
                    </div>
                    @endif
                    @if ($business->villageDivision)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Village') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->villageDivision->name }}</dd>
                    </div>
                    @endif
                    @if ($business->city)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('City') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->city }}</dd>
                    </div>
                    @endif
                    @if ($business->state_region)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('State / Region') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->state_region }}</dd>
                    </div>
                    @endif
                    @if ($business->postal_code)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Postal code') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->postal_code }}</dd>
                    </div>
                    @endif
                    @if ($business->country && !$business->countryDivision)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Country') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $business->country }}</dd>
                    </div>
                    @endif
                </dl>
            </section>
            @endif

            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Facilities') }}</p>
                    <a href="{{ route('businesses.facilities.create', $business) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Add facility') }}</a>
                </div>
                @if ($business->facilities->isEmpty())
                    <p class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No facilities registered yet.') }}</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($business->facilities as $facility)
                            <li class="flex items-center justify-between gap-3 px-4 py-3">
                                <div>
                                    <a href="{{ route('businesses.facilities.show', [$business, $facility]) }}" class="text-sm font-medium text-slate-900 hover:underline">
                                        {{ $facility->facility_name }}
                                    </a>
                                    <p class="text-xs text-slate-500">{{ $facility->facility_type }} · {{ $facility->location_display }}</p>
                                </div>
                                <a href="{{ route('businesses.facilities.edit', [$business, $facility]) }}" class="text-xs font-medium text-bucha-primary hover:underline">{{ __('Edit') }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
    </div>
</x-app-layout>
