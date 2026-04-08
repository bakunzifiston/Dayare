<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Post-mortem inspection') }} — {{ $inspection->inspection_date?->format('d M Y') ?? __('No date') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('post-mortem-inspections.edit', $inspection) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('batches.show', $inspection->batch) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('View batch') }}
                </a>
                <a href="{{ route('post-mortem-inspections.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Back to list') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Batch') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('batches.show', $inspection->batch) }}" class="text-bucha-primary hover:underline">
                                {{ $inspection->batch->batch_code }}
                            </a>
                            — {{ $inspection->batch->species }} ({{ $inspection->batch->quantity }})
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inspection date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->inspection_date?->format('l, d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inspector') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('inspectors.show', $inspection->inspector) }}" class="text-bucha-primary hover:underline">
                                {{ $inspection->inspector->full_name }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Species') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->species ?: $inspection->batch->species }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Computed result') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ str($inspection->result ?? 'approved')->replace('_', ' ')->title() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Total examined') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->total_examined }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Approved quantity') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->approved_quantity }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Condemned quantity') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->condemned_quantity }}</dd>
                    </div>
                    @if ($inspection->notes)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Notes') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $inspection->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Carcass inspection') }}</h3>
                    @php $carcass = $inspection->observations->where('category', 'carcass'); @endphp
                    @if ($carcass->isEmpty())
                        <p class="text-sm text-gray-500">{{ __('No carcass observations recorded.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Item') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Status') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($carcass as $observation)
                                        <tr>
                                            <td class="px-3 py-2">{{ str($observation->item)->replace('_', ' ')->title() }}</td>
                                            <td class="px-3 py-2">{{ str($observation->value)->replace('_', ' ')->title() }}</td>
                                            <td class="px-3 py-2">{{ $observation->notes ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Organ inspection') }}</h3>
                    @php $organs = $inspection->observations->where('category', 'organ'); @endphp
                    @if ($organs->isEmpty())
                        <p class="text-sm text-gray-500">{{ __('No organ observations recorded.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Item') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Status') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($organs as $observation)
                                        <tr>
                                            <td class="px-3 py-2">{{ str($observation->item)->replace('_', ' ')->title() }}</td>
                                            <td class="px-3 py-2">{{ str($observation->value)->replace('_', ' ')->title() }}</td>
                                            <td class="px-3 py-2">{{ $observation->notes ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
