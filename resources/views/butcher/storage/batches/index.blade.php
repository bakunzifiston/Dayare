<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('butcher.storage.index') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Cold storage') }}</a>
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ __('Inventory batches') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('Batch') }}</th>
                            <th class="px-4 py-3">{{ __('Meat') }}</th>
                            <th class="px-4 py-3">{{ __('Remaining (kg)') }}</th>
                            <th class="px-4 py-3">{{ __('Age') }}</th>
                            <th class="px-4 py-3">{{ __('Best before') }}</th>
                            <th class="px-4 py-3">{{ __('Location') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($batches as $batch)
                            <tr @class(['hover:bg-slate-50', 'bg-amber-50/50' => $batch->isExpiringSoon(), 'bg-red-50/40' => $batch->status === 'expired'])>
                                <td class="px-4 py-3 font-medium">
                                    <a href="{{ route('butcher.storage.batches.show', $batch) }}" class="text-bucha-primary hover:underline">{{ $batch->batch_number }}</a>
                                </td>
                                <td class="px-4 py-3 capitalize">{{ $batch->meat_type }}</td>
                                <td class="px-4 py-3">{{ number_format((float) $batch->remaining_weight_kg, 2) }}</td>
                                <td class="px-4 py-3">{{ $batch->ageInDays() }}d</td>
                                <td class="px-4 py-3">{{ $batch->best_before_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">{{ $batch->storage_location ?: '—' }}</td>
                                <td class="px-4 py-3"><x-butcher.status-badge :status="$batch->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ __('No batches yet. Receive a delivery from procurement.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $batches->links() }}</div>
        </div>
    </div>
</x-app-layout>
