<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Supply requests') }}</span>
    </x-slot>

    <div class="max-w-5xl space-y-4">
        <div class="bg-white rounded-bucha border border-slate-200/60 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-2">{{ __('Processor') }}</th>
                        <th class="px-4 py-2">{{ __('Facility') }}</th>
                        <th class="px-4 py-2">{{ __('Type') }}</th>
                        <th class="px-4 py-2">{{ __('Qty') }}</th>
                        <th class="px-4 py-2">{{ __('Status') }}</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($requests as $r)
                        <tr>
                            <td class="px-4 py-2">{{ $r->processor?->business_name }}</td>
                            <td class="px-4 py-2">{{ $r->destinationFacility?->facility_name }}</td>
                            <td class="px-4 py-2 capitalize">{{ $r->animal_type }}</td>
                            <td class="px-4 py-2">{{ $r->quantity_requested }}</td>
                            <td class="px-4 py-2 capitalize">{{ $r->status }}</td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('farmer.supply-requests.show', $r) }}" class="text-bucha-primary hover:underline">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No supply requests yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $requests->links() }}</div>
    </div>
</x-app-layout>
