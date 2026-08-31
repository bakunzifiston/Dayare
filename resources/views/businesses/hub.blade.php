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

                <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Businesses summary') }}">
                    <x-kpi-card stat compact color="slate" :title="__('Total businesses')" :value="number_format($totalBusinesses)" glyph="building" />
                    <x-kpi-card stat compact color="bucha-success" :title="__('Active')" :value="number_format($activeCount)" glyph="check" />
                    <x-kpi-card stat compact color="amber" :title="__('Suspended')" :value="number_format($suspendedCount)" glyph="alert" />
                    <x-kpi-card stat compact color="bucha" :title="__('Total facilities')" :value="number_format($totalFacilities)" glyph="building" />
                </section>

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
