<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <span class="text-sm font-medium text-bucha-muted">{{ __('Supply requests to farmers') }}</span>
            <a href="{{ route('processor.supply-requests.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-xs font-semibold uppercase tracking-widest rounded-bucha">{{ __('New request') }}</a>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-4">
        @if (session('status'))
            <div class="rounded-bucha border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="bg-white rounded-bucha border border-slate-200/60 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-2">{{ __('Farmer') }}</th>
                        <th class="px-4 py-2">{{ __('Facility') }}</th>
                        <th class="px-4 py-2">{{ __('Type') }}</th>
                        <th class="px-4 py-2">{{ __('Qty') }}</th>
                        <th class="px-4 py-2">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($requests as $r)
                        <tr>
                            <td class="px-4 py-2">{{ $r->farmer?->business_name }}</td>
                            <td class="px-4 py-2">{{ $r->destinationFacility?->facility_name }}</td>
                            <td class="px-4 py-2">{{ \App\Support\FarmerAnimalType::label($r->animal_type) }}</td>
                            <td class="px-4 py-2">{{ $r->quantity_requested }}</td>
                            <td class="px-4 py-2 capitalize">{{ $r->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('No requests yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $requests->links() }}</div>
    </div>
</x-app-layout>
