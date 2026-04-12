<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Supply history') }}</span>
    </x-slot>

    <div class="max-w-5xl">
        <div class="bg-white rounded-bucha border border-slate-200/60 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-2">{{ __('Date') }}</th>
                        <th class="px-4 py-2">{{ __('Facility') }}</th>
                        <th class="px-4 py-2">{{ __('Animal type') }}</th>
                        <th class="px-4 py-2">{{ __('Quantity') }}</th>
                        <th class="px-4 py-2">{{ __('Status / source') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($history as $row)
                        <tr>
                            <td class="px-4 py-2">{{ $row['date'] }}</td>
                            <td class="px-4 py-2">{{ $row['facility'] }}</td>
                            <td class="px-4 py-2">{{ $row['animal_type'] }}</td>
                            <td class="px-4 py-2">{{ $row['quantity'] }}</td>
                            <td class="px-4 py-2">{{ $row['status'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('No history yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
