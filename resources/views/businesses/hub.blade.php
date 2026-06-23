@php
    use App\Models\Business;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Businesses') }}
            </h2>
            <a href="{{ route('businesses.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Register business') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="profile-list-shell">
                @if (session('status'))
                    <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
                @endif

                <div class="profile-kpi-grid">
                    <x-entity.kpi-stat :label="__('Total businesses')" :value="number_format($totalBusinesses)">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Active')" :value="number_format($activeCount)" accent>
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Suspended')" :value="number_format($suspendedCount)">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 5a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Total facilities')" :value="number_format($totalFacilities)">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                </div>

                @if ($businesses->isEmpty())
                    <div class="profile-empty">
                        <p class="mb-4">{{ __('You have not registered any business yet.') }}</p>
                        <a href="{{ route('businesses.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Register your first business') }}
                        </a>
                    </div>
                @else
                    <div class="profile-cards-grid">
                        @foreach ($businesses as $business)
                            @php
                                $ownerName = $business->ownerIndividualDisplayName();
                                $location = collect([
                                    $business->districtDivision?->name,
                                    $business->city,
                                ])->filter()->implode(', ');
                                if ($location === '' && $business->address_line_1) {
                                    $location = $business->address_line_1;
                                }
                                $statusTone = match ($business->status) {
                                    Business::STATUS_ACTIVE => 'active',
                                    Business::STATUS_SUSPENDED => 'warning',
                                    default => 'muted',
                                };
                                $initial = strtoupper(substr($business->business_name, 0, 1));
                            @endphp
                            <x-entity.profile-card>
                                <x-slot:avatar>{{ $initial }}</x-slot:avatar>
                                <x-slot:title>
                                    <a href="{{ route('businesses.show', $business) }}">{{ $business->registration_number ?: $business->business_name }}</a>
                                </x-slot:title>
                                <x-slot:subtitle>{{ $location !== '' ? $location : $business->business_name }}</x-slot:subtitle>
                                <x-slot:badge>
                                    <x-entity.status-pill :tone="$statusTone" :label="strtoupper($business->status)" />
                                </x-slot:badge>

                                <x-entity.profile-row :label="__('Registration')">{{ $business->registration_number ?: '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Tax ID')">{{ $business->tax_id ?: '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Phone')">{{ $business->contact_phone ?: '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Email')">{{ $business->email ?: '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Owner')">{{ $ownerName !== '' ? $ownerName : '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Ownership')">
                                    {{ $business->ownership_type ? ucfirst(str_replace('_', ' ', $business->ownership_type)) : '—' }}
                                </x-entity.profile-row>
                                <x-entity.profile-row :label="__('Size')">{{ $business->business_size ? ucfirst($business->business_size) : '—' }}</x-entity.profile-row>
                                @if ($business->type)
                                    <x-entity.profile-row :label="__('Type')">{{ ucfirst($business->type) }}</x-entity.profile-row>
                                @endif

                                <x-slot:highlights>
                                    <x-entity.profile-highlight :value="number_format($business->facilities_count)" :label="__('Facilities')" />
                                    <x-entity.profile-highlight :value="$business->created_at?->format('d M Y') ?? '—'" :label="__('Registered')" />
                                </x-slot:highlights>

                                <x-slot:actions>
                                    <x-entity.text-action :href="route('businesses.show', $business)">{{ __('View') }}</x-entity.text-action>
                                    <x-entity.text-action :href="route('businesses.facilities.index', $business)">{{ __('Facilities') }}</x-entity.text-action>
                                    <x-entity.text-action :href="route('businesses.edit', $business)">{{ __('Edit') }}</x-entity.text-action>
                                    <x-entity.text-action-delete
                                        :action="route('businesses.destroy', $business)"
                                        :confirm="__('Are you sure you want to delete this business? This cannot be undone.')"
                                    >{{ __('Delete') }}</x-entity.text-action-delete>
                                </x-slot:actions>
                            </x-entity.profile-card>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $businesses->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
