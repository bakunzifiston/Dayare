<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <h2 class="font-semibold text-xl text-slate-800">{{ __('Vaccinations') }}</h2>
            <a href="{{ route('farmer.health.vaccinations.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white">{{ __('Add vaccination') }}</a>
        </div>
    </x-slot>

    <div class="max-w-7xl space-y-6">
        @include('farmer.health.partials.nav')

        <form method="get" class="grid gap-3 rounded-bucha border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-5">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search code, vaccine, vet') }}" class="rounded-lg border-gray-300 text-sm md:col-span-2">
            <select name="status" class="rounded-lg border-gray-300 text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Models\Vaccination::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ __(ucfirst($status)) }}</option>
                @endforeach
            </select>
            <select name="animal_id" class="rounded-lg border-gray-300 text-sm">
                <option value="">{{ __('All animals') }}</option>
                @foreach ($animals as $animal)
                    <option value="{{ $animal->id }}" @selected((int) request('animal_id') === $animal->id)>{{ $animal->selectionLabel() }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="rounded-bucha bg-slate-900 px-4 py-2 text-sm font-medium text-white">{{ __('Filter') }}</button>
                <a href="{{ route('farmer.health.vaccinations.index', ['export' => 'csv'] + request()->query()) }}" class="rounded-bucha border border-slate-300 px-4 py-2 text-sm">{{ __('Export') }}</a>
            </div>
        </form>

        @if ($records->isEmpty())
            <div class="rounded-bucha border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                <p class="text-sm text-slate-600">{{ __('No vaccination records yet.') }}</p>
            </div>
        @else
            <div class="overflow-hidden rounded-bucha border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('Code') }}</th>
                            <th class="px-4 py-3">{{ __('Animal') }}</th>
                            <th class="px-4 py-3">{{ __('Vaccine') }}</th>
                            <th class="px-4 py-3">{{ __('Date') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $record)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $record->vaccination_code }}</td>
                                <td class="px-4 py-3">{{ $record->animal?->animal_code }}</td>
                                <td class="px-4 py-3">{{ $record->vaccine_name }}</td>
                                <td class="px-4 py-3">{{ $record->vaccination_date?->toDateString() }}</td>
                                <td class="px-4 py-3"><x-health-status-badge :status="$record->status" /></td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('farmer.health.vaccinations.show', $record) }}" class="text-bucha-primary hover:underline">{{ __('View') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $records->links() }}</div>
        @endif
    </div>
</x-app-layout>
