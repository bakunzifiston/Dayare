@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2).' kg';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Stock transfers') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Move inventory between outlets in the same business.') }}</p>
            </div>
            <a href="{{ route('butcher.transfers.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                {{ __('New transfer') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <section class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('When') }}</th>
                                <th class="px-4 py-3">{{ __('From') }}</th>
                                <th class="px-4 py-3">{{ __('To') }}</th>
                                <th class="px-4 py-3">{{ __('Source batch') }}</th>
                                <th class="px-4 py-3">{{ __('Qty') }}</th>
                                <th class="px-4 py-3">{{ __('By') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($transfers as $transfer)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">{{ $transfer->transferred_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3">{{ $transfer->fromOutlet?->name }}</td>
                                    <td class="px-4 py-3">{{ $transfer->toOutlet?->name }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('butcher.inventory.batches.show', $transfer->batch) }}" class="text-bucha-primary hover:underline">{{ $transfer->batch?->batch_number }}</a>
                                        @if ($transfer->destinationBatch)
                                            <span class="text-slate-400">→</span>
                                            <a href="{{ route('butcher.inventory.batches.show', $transfer->destinationBatch) }}" class="text-bucha-primary hover:underline">{{ $transfer->destinationBatch->batch_number }}</a>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $fmtKg($transfer->quantity_kg) }}</td>
                                    <td class="px-4 py-3">{{ $transfer->transferredByUser?->name }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No transfers yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4">{{ $transfers->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
