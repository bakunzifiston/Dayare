<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="{{ route('businesses.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Businesses') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                    {{ $business->business_name }}
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('businesses.facilities.index', $business) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Facilities') }}
                </a>
                <a href="{{ route('businesses.edit', $business) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <form method="POST" action="{{ route('businesses.destroy', $business) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this business? This cannot be undone.') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                        {{ __('Delete') }}
                    </button>
                </form>
                <a href="{{ route('businesses.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('All businesses') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <h3 class="text-base font-semibold text-slate-800 mb-1">{{ __('Business info') }}</h3>
                <p class="text-sm text-slate-500 mb-4">{{ __('Official business details and contact.') }}</p>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Registration number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->registration_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Tax ID') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->tax_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Contact phone') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->contact_phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Email') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($business->status) }}</dd>
                    </div>
                    @if ($business->business_size)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Business size') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($business->business_size) }}</dd>
                    </div>
                    @endif
                    @if ($business->baseline_revenue !== null && $business->baseline_revenue !== '')
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Baseline annual revenue (RWF)') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ \App\Models\Business::baselineRevenueBracketOptions()[$business->baseline_revenue] ?? $business->baseline_revenue }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            @if ($business->owner_first_name || $business->owner_last_name || $business->owner_name || $business->owner_phone || $business->owner_email || $business->ownership_type || $business->owner_gender || $business->owner_pwd_status || $business->ownershipMembers->isNotEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <h3 class="text-base font-semibold text-slate-800 mb-1">{{ __('Ownership info') }}</h3>
                <p class="text-sm text-slate-500 mb-4">{{ __('Owner or legal representative details.') }}</p>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @if ($business->owner_first_name || $business->owner_last_name)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Owner / representative name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ trim($business->owner_first_name . ' ' . $business->owner_last_name) ?: $business->owner_name }}</dd>
                    </div>
                    @elseif ($business->owner_name)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Owner / representative name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->owner_name }}</dd>
                    </div>
                    @endif
                    @if ($business->owner_dob)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Date of birth') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->owner_dob->format('d/m/Y') }}</dd>
                    </div>
                    @endif
                    @if ($business->owner_gender)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Owner gender') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($business->owner_gender) }}</dd>
                    </div>
                    @endif
                    @if ($business->owner_pwd_status)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Disability status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($business->owner_pwd_status) }}</dd>
                    </div>
                    @endif
                    @if ($business->owner_phone)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Owner phone') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->owner_phone }}</dd>
                    </div>
                    @endif
                    @if ($business->owner_email)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Owner email') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->owner_email }}</dd>
                    </div>
                    @endif
                    @if ($business->ownership_type)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Ownership type') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $business->ownership_type)) }}</dd>
                    </div>
                    @endif
                </dl>
                @if ($business->ownershipMembers->isNotEmpty())
                <div class="mt-6 pt-4 border-t border-slate-200">
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
            </div>
            @endif

            @if ($business->vibe_unique_id || $business->vibe_commencement_date || $business->pathway_status || $business->vibe_comments)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <h3 class="text-base font-semibold text-slate-800 mb-1">{{ __('VIBE metadata') }}</h3>
                <p class="text-sm text-slate-500 mb-4">{{ __('Progress tracking details for the VIBE pathway.') }}</p>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @if ($business->vibe_unique_id)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('VIBE unique ID') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->vibe_unique_id }}</dd>
                    </div>
                    @endif
                    @if ($business->vibe_commencement_date)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('VIBE commencement date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->vibe_commencement_date->format('d/m/Y') }}</dd>
                    </div>
                    @endif
                    @if ($business->pathway_status)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Pathway status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($business->pathway_status) }}</dd>
                    </div>
                    @endif
                    @if ($business->vibe_comments)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('VIBE comments') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ $business->vibe_comments }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
            @endif

            @if ($business->country_id || $business->city || $business->country)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <h3 class="text-base font-semibold text-slate-800 mb-1">{{ __('Location info') }}</h3>
                <p class="text-sm text-slate-500 mb-4">{{ __('Business location.') }}</p>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @if ($business->countryDivision)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Country') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->countryDivision->name }}</dd>
                    </div>
                    @endif
                    @if ($business->provinceDivision)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Province') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->provinceDivision->name }}</dd>
                    </div>
                    @endif
                    @if ($business->districtDivision)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('District') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->districtDivision->name }}</dd>
                    </div>
                    @endif
                    @if ($business->sectorDivision)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Sector') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->sectorDivision->name }}</dd>
                    </div>
                    @endif
                    @if ($business->cellDivision)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Cell') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->cellDivision->name }}</dd>
                    </div>
                    @endif
                    @if ($business->villageDivision)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Village') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->villageDivision->name }}</dd>
                    </div>
                    @endif
                    @if ($business->city)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('City') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->city }}</dd>
                    </div>
                    @endif
                    @if ($business->state_region)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('State / Region') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->state_region }}</dd>
                    </div>
                    @endif
                    @if ($business->postal_code)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Postal code') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->postal_code }}</dd>
                    </div>
                    @endif
                    @if ($business->country && !$business->countryDivision)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Country') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $business->country }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Facilities') }}</h3>
                @if ($business->facilities->isEmpty())
                    <p class="text-gray-600">{{ __('No facilities registered yet.') }}</p>
                    <a href="{{ route('businesses.facilities.create', $business) }}" class="inline-flex items-center mt-2 text-bucha-primary hover:text-indigo-900">
                        {{ __('Add facility') }}
                    </a>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach ($business->facilities as $facility)
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <a href="{{ route('businesses.facilities.show', [$business, $facility]) }}" class="font-medium text-gray-900 hover:underline">
                                        {{ $facility->facility_name }}
                                    </a>
                                    <p class="text-sm text-gray-500">{{ $facility->facility_type }} · {{ $facility->location_display }}</p>
                                </div>
                                <a href="{{ route('businesses.facilities.edit', [$business, $facility]) }}" class="text-sm text-bucha-primary hover:text-indigo-900">{{ __('Edit') }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('businesses.facilities.create', $business) }}" class="inline-flex items-center mt-4 text-bucha-primary hover:text-indigo-900">
                        {{ __('Add facility') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
