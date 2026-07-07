@php
    use App\Models\ColdRoomStandard;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('cold-rooms.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold Room') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('Temperature standards') }}
                </h2>
            </div>
            <a href="{{ route('cold-room-standards.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                {{ __('Add standard') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="profile-list-shell">
                <p class="text-sm text-slate-600">
                    {{ __('Define allowed temperature bands and how long an excursion can last before batches are flagged. Link each standard to a room under Manage cold rooms in the Cold Room module.') }}
                </p>

                @if (session('status'))
                    <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($standards->isEmpty())
                    <div class="profile-empty">
                        <p class="mb-4">{{ __('No standards yet. Add one to attach to your cold rooms.') }}</p>
                        <a href="{{ route('cold-room-standards.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Add first standard') }}
                        </a>
                    </div>
                @else
                    <div class="profile-cards-grid">
                        @foreach ($standards as $standard)
                            @php
                                $typeTone = $standard->type === ColdRoomStandard::TYPE_CHILLER ? 'active' : 'muted';
                                $initial = strtoupper(substr($standard->name, 0, 1));
                            @endphp
                            <x-entity.profile-card>
                                <x-slot:avatar>{{ $initial }}</x-slot:avatar>
                                <x-slot:title>{{ $standard->name }}</x-slot:title>
                                <x-slot:subtitle>{{ ucfirst($standard->type) }}</x-slot:subtitle>
                                <x-slot:badge>
                                    <x-entity.status-pill :tone="$typeTone" :label="ucfirst($standard->type)" />
                                </x-slot:badge>

                                <x-entity.profile-row :label="__('Min °C')">{{ number_format((float) $standard->min_temperature, 1) }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Max °C')">{{ number_format((float) $standard->max_temperature, 1) }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Tolerance (min)')">{{ number_format((int) $standard->tolerance_minutes) }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Linked rooms')">{{ number_format($standard->cold_rooms_count) }}</x-entity.profile-row>

                                <x-slot:highlights>
                                    <x-entity.profile-highlight
                                        :value="number_format((float) $standard->min_temperature, 1).'–'.number_format((float) $standard->max_temperature, 1).'°C'"
                                        :label="__('Range')"
                                    />
                                    <x-entity.profile-highlight
                                        :value="number_format($standard->cold_rooms_count)"
                                        :label="__('Rooms')"
                                    />
                                </x-slot:highlights>

                                <x-slot:actions>
                                    <x-entity.text-action :href="route('cold-room-standards.edit', $standard)">{{ __('Edit') }}</x-entity.text-action>
                                    <x-entity.text-action-delete
                                        :action="route('cold-room-standards.destroy', $standard)"
                                        :confirm="__('Delete this standard?')"
                                    >{{ __('Delete') }}</x-entity.text-action-delete>
                                </x-slot:actions>
                            </x-entity.profile-card>
                        @endforeach
                    </div>

                    @if ($standards->hasPages())
                        <div class="mt-4">{{ $standards->links() }}</div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
