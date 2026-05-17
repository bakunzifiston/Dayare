<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <h2 class="font-semibold text-xl text-slate-800">{{ __('Permit requests') }}</h2>
            <a href="{{ route('farmer.movement.requests.create') }}" class="rounded-bucha bg-bucha-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white">{{ __('New request') }}</a>
        </div>
    </x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.movement.partials.nav')
        <form method="GET" class="flex flex-wrap gap-3">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search request number…') }}" class="rounded-lg border-gray-300 text-sm" />
            <select name="status" class="rounded-lg border-gray-300 text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Models\PermitRequest::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">{{ __('Filter') }}</button>
        </form>
        <div class="overflow-hidden rounded-bucha border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Request') }}</th>
                        <th class="px-4 py-3">{{ __('Farm') }}</th>
                        <th class="px-4 py-3">{{ __('Purpose') }}</th>
                        <th class="px-4 py-3">{{ __('Animals') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $record->request_number }}</td>
                            <td class="px-4 py-3">{{ $record->farm?->name }}</td>
                            <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $record->movement_purpose) }}</td>
                            <td class="px-4 py-3">{{ $record->animals_count ?? $record->animals->count() }}</td>
                            <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $record->status) }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('farmer.movement.requests.show', $record) }}" class="text-bucha-primary hover:underline">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No permit requests yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $records->links() }}
    </div>
</x-app-layout>
