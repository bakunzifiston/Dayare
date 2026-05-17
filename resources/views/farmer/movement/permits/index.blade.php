<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-4 w-full"><h2 class="font-semibold text-xl text-slate-800">{{ __('Movement permits') }}</h2><a href="{{ route('farmer.movement.permits.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white">{{ __('Import / create permit') }}</a></div></x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.movement.partials.nav')
        <form method="GET" class="grid gap-3 rounded-bucha border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-5">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search permits...') }}" class="rounded-lg border-gray-300 text-sm md:col-span-2" />
            <select name="permit_type" class="rounded-lg border-gray-300 text-sm">
                <option value="">{{ __('All types') }}</option>
                @foreach (\App\Models\MovementPermit::TYPES as $type)
                    <option value="{{ $type }}" @selected(request('permit_type') === $type)>{{ __(ucwords(str_replace('_', ' ', $type))) }}</option>
                @endforeach
            </select>
            <select name="permit_status" class="rounded-lg border-gray-300 text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Models\MovementPermit::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('permit_status') === $status)>{{ __(ucwords(str_replace('_', ' ', $status))) }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">{{ __('Filter') }}</button>
        </form>
        @if ($records->isEmpty())
            <p class="text-sm text-slate-500">{{ __('No movement permits yet.') }}</p>
        @else
            <div class="overflow-hidden rounded-bucha border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('Permit #') }}</th>
                            <th class="px-4 py-3">{{ __('Farm') }}</th>
                            <th class="px-4 py-3">{{ __('Type') }}</th>
                            <th class="px-4 py-3">{{ __('Departure') }}</th>
                            <th class="px-4 py-3">{{ __('Animals') }}</th>
                            <th class="px-4 py-3">{{ __('Permit status') }}</th>
                            <th class="px-4 py-3">{{ __('Movement') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $record)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $record->permit_number }}</td>
                                <td class="px-4 py-3">{{ $record->sourceFarm?->name }}</td>
                                <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $record->permit_type) }}</td>
                                <td class="px-4 py-3">{{ $record->departure_date?->toDateString() }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ $record->animals->count() }}</td>
                                <td class="px-4 py-3"><x-movement-permit-status-badge :status="$record->permit_status" /></td>
                                <td class="px-4 py-3"><x-movement-status-badge :status="$record->movement_status" /></td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('farmer.movement.permits.show', $record) }}" class="text-bucha-primary hover:underline">{{ __('View') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $records->links() }}</div>
        @endif
    </div>
</x-app-layout>
