@php
    use App\Models\Facility;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('businesses.show', $business) }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Business profile') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('Facilities') }} — {{ $business->business_name }}
                </h2>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('businesses.facilities.create', $business) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Add facility') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="profile-list-shell">
                @if (session('status'))
                    <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
                @endif

                <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Facilities summary') }}">
                    <x-kpi-card stat compact color="slate" :title="__('Total facilities')" :value="number_format($kpis['total'])" glyph="building" />
                    <x-kpi-card stat compact color="bucha-success" :title="__('Active')" :value="number_format($kpis['active'])" glyph="check" />
                    <x-kpi-card stat compact color="amber" :title="__('Suspended')" :value="number_format($kpis['suspended'])" glyph="alert" />
                </section>

                @if ($facilities->isEmpty())
                    <div class="profile-empty">
                        <p class="mb-4">{{ __('No facilities for this business yet.') }}</p>
                        <a href="{{ route('businesses.facilities.create', $business) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Add first facility') }}
                        </a>
                    </div>
                @else
                    <div class="profile-cards-grid">
                        @foreach ($facilities as $facility)
                            @php
                                $statusTone = match ($facility->status) {
                                    Facility::STATUS_ACTIVE => 'active',
                                    Facility::STATUS_SUSPENDED => 'warning',
                                    default => 'muted',
                                };
                                $initial = strtoupper(substr($facility->facility_name, 0, 1));
                                $badgeLabel = $facility->isLicenseExpired()
                                    ? __('License expired')
                                    : strtoupper($facility->status);
                                $badgeTone = $facility->isLicenseExpired() ? 'danger' : $statusTone;
                            @endphp
                            <x-entity.profile-card>
                                <x-slot:avatar>{{ $initial }}</x-slot:avatar>
                                <x-slot:title>
                                    <a href="{{ route('businesses.facilities.show', [$business, $facility]) }}">{{ $facility->facility_name }}</a>
                                </x-slot:title>
                                <x-slot:subtitle>{{ $facility->location_display }}</x-slot:subtitle>
                                <x-slot:badge>
                                    <x-entity.status-pill :tone="$badgeTone" :label="$badgeLabel" />
                                </x-slot:badge>

                                <x-entity.profile-row :label="__('Facility type')">{{ $facility->facility_type ?: '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('License number')">{{ $facility->license_number ?: '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Daily capacity')">{{ $facility->daily_capacity ?: '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('License issued')">{{ $facility->license_issue_date?->format('d M Y') ?? '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('License expires')">{{ $facility->license_expiry_date?->format('d M Y') ?? '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('GPS')">{{ $facility->gps ?: '—' }}</x-entity.profile-row>

                                <x-slot:highlights>
                                    <x-entity.profile-highlight :value="number_format($facility->inspectors_count)" :label="__('Inspectors')" />
                                    <x-entity.profile-highlight :value="number_format($facility->employees_count)" :label="__('Employees')" />
                                </x-slot:highlights>

                                <x-slot:actions>
                                    <x-entity.text-action :href="route('businesses.facilities.show', [$business, $facility])">{{ __('View') }}</x-entity.text-action>
                                    <x-entity.text-action :href="route('businesses.facilities.edit', [$business, $facility])">{{ __('Edit') }}</x-entity.text-action>
                                    <x-entity.text-action-delete
                                        :action="route('businesses.facilities.destroy', [$business, $facility])"
                                        :confirm="__('Are you sure you want to delete this facility? This cannot be undone.')"
                                    >{{ __('Delete') }}</x-entity.text-action-delete>
                                </x-slot:actions>
                            </x-entity.profile-card>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $facilities->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
