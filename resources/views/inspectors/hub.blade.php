@php
    use App\Models\Inspector;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Inspectors') }}
            </h2>
            <a href="{{ route('inspectors.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Register inspector') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="profile-list-shell">
                @if (session('status'))
                    <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
                @endif

                <div class="profile-kpi-grid profile-kpi-grid--3">
                    <x-entity.kpi-stat :label="__('Total inspectors')" :value="number_format($kpis['total'])">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Active')" :value="number_format($kpis['active'])" accent>
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                    <x-entity.kpi-stat :label="__('Expired status')" :value="number_format($kpis['expired'])">
                        <x-slot:icon>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 5a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"/></svg>
                        </x-slot:icon>
                    </x-entity.kpi-stat>
                </div>

                @if ($inspectors->isEmpty())
                    <div class="profile-empty">
                        <p class="mb-4">{{ __('No inspectors registered yet.') }}</p>
                        <a href="{{ route('inspectors.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Register first inspector') }}
                        </a>
                    </div>
                @else
                    <div class="profile-cards-grid">
                        @foreach ($inspectors as $inspector)
                            @php
                                $statusTone = match ($inspector->status) {
                                    Inspector::STATUS_ACTIVE => 'active',
                                    Inspector::STATUS_EXPIRED => 'warning',
                                    default => 'muted',
                                };
                                $badgeLabel = $inspector->isAuthorizationExpired()
                                    ? __('Auth expired')
                                    : strtoupper($inspector->status);
                                $badgeTone = $inspector->isAuthorizationExpired() ? 'danger' : $statusTone;
                                $initial = strtoupper(substr($inspector->first_name, 0, 1));
                                $cardTitle = $inspector->authorization_number ?: $inspector->national_id ?: $inspector->full_name;
                            @endphp
                            <x-entity.profile-card>
                                <x-slot:avatar>{{ $initial }}</x-slot:avatar>
                                <x-slot:title>
                                    <a href="{{ route('inspectors.show', $inspector) }}">{{ $cardTitle }}</a>
                                </x-slot:title>
                                <x-slot:subtitle>{{ $inspector->facility->facility_name ?? '—' }}</x-slot:subtitle>
                                <x-slot:badge>
                                    <x-entity.status-pill :tone="$badgeTone" :label="$badgeLabel" />
                                </x-slot:badge>

                                <x-entity.profile-row :label="__('Name')">{{ $inspector->full_name }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('National ID')">{{ $inspector->national_id ?: '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Email')">{{ $inspector->email ?: '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Phone')">{{ $inspector->phone_number ?: '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Authorization')">{{ $inspector->authorization_number ?: '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Auth expires')">{{ $inspector->authorization_expiry_date?->format('d M Y') ?? '—' }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Species')">{{ $inspector->species_allowed ?: '—' }}</x-entity.profile-row>
                                @if ($inspector->daily_capacity)
                                    <x-entity.profile-row :label="__('Daily capacity')">{{ $inspector->daily_capacity }}</x-entity.profile-row>
                                @endif

                                <x-slot:highlights>
                                    <x-entity.profile-highlight :value="$inspector->authorization_issue_date?->format('d M Y') ?? '—'" :label="__('Auth issued')" />
                                    <x-entity.profile-highlight :value="$inspector->stamp_serial_number ?: '—'" :label="__('Stamp serial')" />
                                </x-slot:highlights>

                                <x-slot:actions>
                                    <x-entity.text-action :href="route('inspectors.show', $inspector)">{{ __('View') }}</x-entity.text-action>
                                    <x-entity.text-action :href="route('inspectors.edit', $inspector)">{{ __('Edit') }}</x-entity.text-action>
                                    <x-entity.text-action-delete
                                        :action="route('inspectors.destroy', $inspector)"
                                        :confirm="__('Are you sure you want to delete this inspector? This cannot be undone.')"
                                    >{{ __('Delete') }}</x-entity.text-action-delete>
                                </x-slot:actions>
                            </x-entity.profile-card>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $inspectors->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
