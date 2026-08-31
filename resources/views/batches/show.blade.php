<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Batches') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="font-mono text-sm font-semibold text-slate-900">{{ $batch->batch_code }}</p>
                    <p class="text-xs text-slate-500">{{ $batch->species }} · {{ ucfirst($batch->status) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('batches.edit', $batch) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                    <a href="{{ route('batches.hub') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Batch code') }}</dt>
                    <dd class="mt-0.5 font-mono text-sm text-slate-900">{{ $batch->batch_code }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Status') }}</dt>
                    <dd class="mt-0.5 flex flex-wrap items-center gap-2 text-sm text-slate-900">
                        {{ ucfirst($batch->status) }}
                        <span class="rounded-full px-2 py-0.5 text-xs {{ $batch->cold_chain_badge_class }}">
                            {{ ucfirst(str_replace('_', ' ', $batch->cold_chain_status)) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Slaughter execution') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        <a href="{{ route('slaughter-executions.show', $batch->slaughterExecution) }}" class="text-bucha-primary hover:underline">
                            {{ $batch->slaughterExecution->slaughter_time->format('d M Y H:i') }} — {{ $batch->slaughterExecution->slaughterPlan->facility->facility_name }}
                        </a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Inspector') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        <a href="{{ route('inspectors.show', $batch->inspector) }}" class="text-bucha-primary hover:underline">{{ $batch->inspector->full_name }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Species') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $batch->species }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Quantity') }}</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ number_format($batch->quantity, 2) }} {{ $batch->quantity_unit_label ?: __('carcasses') }}</dd>
                </div>
            </dl>
        </section>

        @if ($batch->hasPerAnimalData())
            @include('batches.partials._batch-animals-table', [
                'batchItems' => $batch->items,
                'releaseByAnimalId' => $releaseByAnimalId ?? collect(),
            ])
        @endif

        <div class="flex flex-wrap gap-2">
            @if (! $batch->hasPostMortem())
                <a href="{{ route('post-mortem-inspections.create', ['batch_id' => $batch->id]) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Record post-mortem inspection') }}</a>
            @endif
            @if ($batch->canIssueCertificate())
                <a href="{{ route('certificates.create', ['slaughter_execution_id' => $batch->slaughter_execution_id]) }}" class="inline-flex h-8 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">
                    {{ $batch->certificates()->exists() ? __('Issue another certificate') : __('Issue certificate') }}
                </a>
            @endif
        </div>

        @if ($batch->postMortemInspection)
            @php $pm = $batch->postMortemInspection; @endphp
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Post-mortem inspection') }}</p>
                    <a href="{{ route('post-mortem-inspections.edit', $pm) }}" class="text-xs font-medium text-bucha-primary hover:underline">{{ __('Edit') }}</a>
                </div>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Inspector') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">
                            <a href="{{ route('inspectors.show', $pm->inspector) }}" class="text-bucha-primary hover:underline">{{ $pm->inspector->full_name }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Inspection date') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $pm->inspection_date?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Total examined meat') }}</dt>
                        <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ number_format((float) $pm->total_examined, 2) }} kg</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Approved meat') }}</dt>
                        <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ number_format((float) $pm->approved_quantity, 2) }} kg</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Condemned meat') }}</dt>
                        <dd class="mt-0.5 text-sm tabular-nums text-slate-900">{{ number_format((float) $pm->condemned_quantity, 2) }} kg</dd>
                    </div>
                    @if ($pm->notes)
                        <div class="sm:col-span-2 lg:col-span-3">
                            <dt class="text-xs text-slate-500">{{ __('Notes') }}</dt>
                            <dd class="mt-0.5 whitespace-pre-wrap text-sm text-slate-900">{{ $pm->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </section>
        @else
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white px-4 py-4">
                <p class="text-sm font-semibold text-slate-900">{{ __('Post-mortem inspection') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('No post-mortem inspection recorded for this batch yet.') }}</p>
                <a href="{{ route('post-mortem-inspections.create', ['batch_id' => $batch->id]) }}" class="mt-2 inline-flex text-xs font-medium text-bucha-primary hover:underline">{{ __('Record post-mortem inspection') }}</a>
            </section>
        @endif

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Certificate') }}</p>
            </div>
            <div class="px-4 py-4">
                @if ($batch->certificates->isNotEmpty())
                    <p class="mb-2 text-sm text-slate-600">{{ __('Certificates issued for this batch.') }}</p>
                    <ul class="space-y-2">
                        @foreach ($batch->certificates as $cert)
                            @php
                                $certAnimalIds = \App\Support\CertificateAnimalSelection::certificateAnimalIds($cert);
                                $certTags = $batch->items
                                    ->filter(fn ($item) => $certAnimalIds->isEmpty() || $certAnimalIds->contains((int) $item->animal_intake_item_id))
                                    ->map(fn ($item) => $item->intakeItem?->ear_tag)
                                    ->filter()
                                    ->values();
                            @endphp
                            <li class="text-sm">
                                <a href="{{ route('certificates.show', $cert) }}" class="font-medium text-bucha-primary hover:underline">{{ $cert->certificate_number ?: __('Certificate') }} #{{ $cert->id }}</a>
                                <span class="text-slate-500"> · {{ $cert->issued_at?->format('d M Y') }} · {{ ucfirst($cert->status) }}</span>
                                @if ($certTags->isNotEmpty())
                                    <span class="mt-0.5 block font-mono text-xs text-slate-500">{{ $certTags->implode(', ') }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @elseif ($batch->canIssueCertificate())
                    <p class="text-sm text-slate-500">{{ __('Released animals are ready for certification.') }}</p>
                    <a href="{{ route('certificates.create', ['slaughter_execution_id' => $batch->slaughter_execution_id]) }}" class="mt-2 inline-flex text-xs font-medium text-bucha-primary hover:underline">{{ __('Issue certificate') }}</a>
                @elseif ($batch->postMortemInspection && $batch->postMortemInspection->approved_quantity > 0 && ! $batch->hasReleasedColdRoomStorage())
                    <p class="text-sm text-slate-500">{{ __('Post-mortem is approved. Release the meat from cold room storage before issuing a certificate.') }}</p>
                    <a href="{{ route('cold-rooms.hub') }}" class="mt-2 inline-flex text-xs font-medium text-bucha-primary hover:underline">{{ __('Go to cold room') }}</a>
                @else
                    <p class="text-sm text-slate-500">{{ __('Certificate can be issued only after post-mortem approval and cold room release.') }}</p>
                @endif
            </div>
        </section>

        @if ($batch->warehouseStorage)
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-medium text-slate-500">{{ __('Warehouse storage') }}</p>
                <p class="mt-0.5 text-sm text-slate-700">
                    {{ $batch->warehouseStorage->coldRoom->name ?? '—' }}
                    · {{ __('Entered') }}: {{ $batch->warehouseStorage->created_at->format('d M Y') }}
                </p>
            </section>
        @endif

        @if ($batch->transportTrips->isNotEmpty())
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Transport trips (:count)', ['count' => $batch->transportTrips->count()]) }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-500">
                                <th class="px-4 py-2">{{ __('Destination') }}</th>
                                <th class="px-3 py-2">{{ __('Departure') }}</th>
                                <th class="px-3 py-2">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($batch->transportTrips as $trip)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2">{{ $trip->destination ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $trip->created_at->format('d M Y') }}</td>
                                    <td class="px-3 py-2">
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ ucfirst($trip->status ?? '—') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
