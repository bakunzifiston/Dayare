<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('batches.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Batches') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Batch') }} — {{ $batch->batch_code }}
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('batches.edit', $batch) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('batches.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('All batches') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Batch code') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $batch->batch_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 flex flex-wrap items-center gap-2">
                            {{ ucfirst($batch->status) }}
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $batch->cold_chain_badge_class }}">
                                {{ ucfirst(str_replace('_', ' ', $batch->cold_chain_status)) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Slaughter execution') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('slaughter-executions.show', $batch->slaughterExecution) }}" class="text-bucha-primary hover:underline">
                                {{ $batch->slaughterExecution->slaughter_time->format('d M Y H:i') }} — {{ $batch->slaughterExecution->slaughterPlan->facility->facility_name }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inspector') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('inspectors.show', $batch->inspector) }}" class="text-bucha-primary hover:underline">
                                {{ $batch->inspector->full_name }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Species') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $batch->species }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Quantity') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($batch->quantity, 2) }} {{ $batch->quantity_unit_label ?: __('carcasses') }}</dd>
                    </div>
                </dl>
            </div>

            @if ($batch->hasPerAnimalData())
                @include('batches.partials._batch-animals-table', ['batchItems' => $batch->items])
            @endif

            <div class="flex flex-wrap gap-2">
                @if (! $batch->hasPostMortem())
                    <a href="{{ route('post-mortem-inspections.create', ['batch_id' => $batch->id]) }}"
                       class="inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                        <i class="ti ti-activity text-base" aria-hidden="true"></i>
                        {{ __('Record post-mortem inspection') }}
                    </a>
                @endif
                @if ($batch->canIssueCertificate() && ! $batch->certificate)
                    <a href="{{ route('certificates.create', ['batch_id' => $batch->id]) }}"
                       class="inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                        <i class="ti ti-certificate text-base" aria-hidden="true"></i>
                        {{ __('Issue certificate') }}
                    </a>
                @endif
            </div>

            @if ($batch->postMortemInspection)
                @php $pm = $batch->postMortemInspection; @endphp
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Post-mortem inspection') }}</h3>
                        <a href="{{ route('post-mortem-inspections.edit', $pm) }}" class="text-sm text-bucha-primary hover:text-indigo-900">{{ __('Edit') }}</a>
                    </div>
                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div><dt class="text-sm text-gray-500">{{ __('Inspector') }}</dt><dd class="text-sm"><a href="{{ route('inspectors.show', $pm->inspector) }}" class="text-bucha-primary hover:underline">{{ $pm->inspector->full_name }}</a></dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Inspection date') }}</dt><dd class="text-sm">{{ $pm->inspection_date?->format('d M Y') ?? '—' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Total examined') }}</dt><dd class="text-sm">{{ $pm->total_examined }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Approved quantity') }}</dt><dd class="text-sm">{{ $pm->approved_quantity }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Condemned quantity') }}</dt><dd class="text-sm">{{ $pm->condemned_quantity }}</dd></div>
                        @if ($pm->notes)<div class="sm:col-span-2"><dt class="text-sm text-gray-500">{{ __('Notes') }}</dt><dd class="text-sm whitespace-pre-wrap">{{ $pm->notes }}</dd></div>@endif
                    </dl>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Post-mortem inspection') }}</h3>
                    <p class="text-sm text-gray-500 mb-2">{{ __('No post-mortem inspection recorded for this batch yet.') }}</p>
                    <a href="{{ route('post-mortem-inspections.create', ['batch_id' => $batch->id]) }}" class="text-sm text-bucha-primary hover:underline">{{ __('Record post-mortem inspection') }}</a>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Certificate') }}</h3>
                @if ($batch->certificate)
                    <p class="text-sm text-gray-600 mb-2">{{ __('Certificate issued.') }}</p>
                    <a href="{{ route('certificates.show', $batch->certificate) }}" class="font-medium text-bucha-primary hover:underline">{{ $batch->certificate->certificate_number ?: __('Certificate') }} #{{ $batch->certificate->id }}</a>
                    <span class="text-sm text-gray-500"> · {{ $batch->certificate->issued_at?->format('d M Y') }} · {{ ucfirst($batch->certificate->status) }}</span>
                @elseif ($batch->canIssueCertificate())
                    <p class="text-sm text-gray-500 mb-2">{{ __('This batch is eligible for a certificate (post-mortem approved quantity &gt; 0).') }}</p>
                    <a href="{{ route('certificates.create', ['batch_id' => $batch->id]) }}" class="text-sm text-bucha-primary hover:underline">{{ __('Issue certificate') }}</a>
                @else
                    <p class="text-sm text-gray-500">{{ __('Certificate can be issued only after a post-mortem inspection with approved quantity greater than zero.') }}</p>
                @endif
            </div>

            @if ($batch->warehouseStorage)
                <div class="mt-4 rounded bg-gray-50 border border-gray-200 p-3">
                    <p class="text-xs font-medium text-gray-600 mb-1">{{ __('Warehouse storage') }}</p>
                    <p class="text-sm text-gray-700">
                        {{ $batch->warehouseStorage->coldRoom->name ?? '—' }}
                        · {{ __('Entered') }}: {{ $batch->warehouseStorage->created_at->format('d M Y') }}
                    </p>
                </div>
            @endif

            @if ($batch->transportTrips->isNotEmpty())
                <div class="mt-4 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm font-medium text-gray-700 mb-2">
                        {{ __('Transport trips (:count)', ['count' => $batch->transportTrips->count()]) }}
                    </p>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500">
                                <th class="pb-1 px-2">{{ __('Destination') }}</th>
                                <th class="pb-1 px-2">{{ __('Departure') }}</th>
                                <th class="pb-1 px-2">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($batch->transportTrips as $trip)
                                <tr class="border-t border-gray-100">
                                    <td class="py-1 px-2">{{ $trip->destination ?? '—' }}</td>
                                    <td class="py-1 px-2">{{ $trip->created_at->format('d M Y') }}</td>
                                    <td class="py-1 px-2">
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                                            {{ ucfirst($trip->status ?? '—') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
