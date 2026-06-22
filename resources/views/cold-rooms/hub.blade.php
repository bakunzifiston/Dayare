<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Cold Room') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-xl font-medium text-gray-900">{{ __('Cold rooms') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ __('Monitor cold room temperatures and track cold chain compliance. Temperature readings from warehouse storage automatically feed violation tracking.') }}
                    </p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ route('cold-rooms.manage.index') }}"
                       class="text-sm px-3 py-1.5 rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                        {{ __('Manage rooms') }}
                    </a>
                    <a href="{{ route('warehouse-storages.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                        {{ __('+ New storage') }}
                    </a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total rooms') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_rooms']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Open violations') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['open_violations'] > 0 ? 'text-red-700' : 'text-slate-900' }}"
                       @if ($hubStats['open_violations'] > 0) title="{{ __('Rooms currently out of temperature range') }}" @endif>
                        {{ number_format($hubStats['open_violations']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Batches at risk') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['batches_at_risk'] > 0 ? 'text-amber-700' : 'text-slate-900' }}"
                       @if ($hubStats['batches_at_risk'] > 0) title="{{ __('Batches with at_risk or compromised cold chain status') }}" @endif>
                        {{ number_format($hubStats['batches_at_risk']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('In storage') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['storages_in_room']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Standards') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['standards']) }}</p>
                </div>
            </div>

            @if ($openViolations->isNotEmpty())
                <div class="rounded bg-red-50 border border-red-200 p-4 mb-6">
                    <p class="text-sm font-medium text-red-800 mb-3">
                        {{ __('Open temperature violations (:count)', ['count' => $openViolations->count()]) }}
                    </p>
                    @foreach ($openViolations as $violation)
                        <div class="flex items-center justify-between py-2 border-t border-red-100 first:border-t-0">
                            <div>
                                <p class="text-sm text-red-800 font-medium">
                                    {{ $violation->coldRoom->name }}
                                    <span class="text-xs font-normal text-red-600 ml-1">
                                        {{ $violation->coldRoom->facility->facility_name ?? '—' }}
                                    </span>
                                </p>
                                <p class="text-xs text-red-600">
                                    {{ __('Started: :time · :minutes min', [
                                        'time' => $violation->start_time->format('d M Y H:i'),
                                        'minutes' => $violation->duration_minutes,
                                    ]) }}
                                </p>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-800">{{ __('Open') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                @forelse ($roomsWithStatus as $room)
                    @php
                        $hasViolation = $room->violations->isNotEmpty();
                        $occupancy = $room->warehouseStorages->count();
                        $borderClass = $hasViolation ? 'border-red-300' : 'border-gray-200';
                    @endphp
                    <div class="bg-white border {{ $borderClass }} rounded-lg p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $room->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $room->facility->facility_name ?? '—' }}
                                </p>
                            </div>
                            <span class="text-xs px-1.5 py-0.5 rounded-full
                                {{ $room->type === 'chiller' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ ucfirst($room->type) }}
                            </span>
                        </div>
                        @if ($room->standard)
                            <p class="text-xs text-gray-500 mb-2">
                                {{ number_format($room->standard->min_temperature, 1) }}°C
                                – {{ number_format($room->standard->max_temperature, 1) }}°C
                            </p>
                        @else
                            <p class="text-xs text-gray-400 mb-2">{{ __('No standard set') }}</p>
                        @endif
                        <div class="flex items-center justify-between text-xs mt-1">
                            <span class="text-gray-500">{{ trans_choice(':count batch in storage|:count batches in storage', $occupancy, ['count' => $occupancy]) }}</span>
                            @if ($hasViolation)
                                <span class="text-red-600 font-medium">{{ __('⚠ Violation open') }}</span>
                            @else
                                <span class="text-green-600">{{ __('✓ In range') }}</span>
                            @endif
                        </div>
                        <div class="flex gap-2 mt-3 text-xs">
                            <a href="{{ route('cold-rooms.manage.edit', $room) }}"
                               class="text-gray-500 hover:underline">{{ __('Edit') }}</a>
                            <a href="{{ route('warehouse-storages.index', ['cold_room_id' => $room->id]) }}"
                               class="text-blue-600 hover:underline">{{ __('View storages') }}</a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3">
                        <p class="text-sm text-gray-400 text-center py-8">
                            {{ __('No cold rooms registered.') }}
                            <a href="{{ route('cold-rooms.manage.create') }}" class="text-blue-600 hover:underline">
                                {{ __('Add the first room →') }}
                            </a>
                        </p>
                    </div>
                @endforelse
            </div>

            <div class="bg-white border border-gray-200 rounded-lg mb-4">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-700">{{ __('Storage records') }}</p>
                    <a href="{{ route('warehouse-storages.index') }}" class="text-xs text-blue-600 hover:underline">
                        {{ __('View all') }}
                    </a>
                </div>
                <div class="p-0">
                    @include('warehouse-storages.partials.storage-table', [
                        'storages' => $storageRecords,
                        'embedded' => true,
                        'emptyMessage' => __('No storage records yet. Record post-mortem approved meat into a cold room.'),
                    ])
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg mb-4">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-700">{{ __('Recent temperature readings') }}</p>
                </div>
                @forelse ($recentLogs as $log)
                    @php
                        $inRange = $log->coldRoom->standard?->temperatureInRange($log->temperature) ?? true;
                        $dot = $inRange ? 'bg-green-500' : 'bg-red-400';
                        $tempClass = $inRange ? 'text-green-700' : 'text-red-700';
                    @endphp
                    <div class="flex items-center gap-4 px-4 py-3 border-b border-gray-100 last:border-b-0">
                        <div class="w-2 h-2 rounded-full {{ $dot }} flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800">{{ $log->coldRoom->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $log->coldRoom->facility->facility_name ?? '—' }}
                                · {{ $log->recorded_at->format('d M Y H:i') }}
                            </p>
                        </div>
                        <p class="text-sm font-medium {{ $tempClass }}">
                            {{ number_format($log->temperature, 1) }}°C
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 px-4 py-6 text-center">
                        {{ __('No temperature readings yet. Readings are created automatically when temperature logs are added to warehouse storage records linked to a cold room.') }}
                    </p>
                @endforelse
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('cold-rooms.manage.index') }}" class="group flex flex-col rounded-2xl border-2 border-bucha-primary/35 bg-white p-6 shadow-md ring-1 ring-bucha-primary/10 transition hover:border-bucha-primary hover:shadow-lg">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-bucha-primary/15 text-bucha-primary">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Manage cold rooms') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Register rooms at each storage facility and attach a temperature standard.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open room list') }} →</span>
                </a>
                <a href="{{ route('warehouse-storages.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Storage records') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('List, create, and edit cold room storage. Log temperatures per storage record.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open storage') }} →</span>
                </a>
                <a href="{{ route('certificates.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Certificates') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Release certified batches from cold storage for transport.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Certification home') }} →</span>
                </a>
                <a href="{{ route('cold-room-standards.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Temperature standards') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Allowed °C ranges and tolerance before batches are marked at risk.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Manage standards') }} →</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
