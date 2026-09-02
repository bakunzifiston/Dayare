<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Compliance') }}</span>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $inspection->site->name }}</p>
                    <p class="text-xs text-slate-500">
                        {{ optional($inspection->scheduled_date)->format('d M Y') }} {{ $inspection->scheduledTimeDisplay() }}
                        · {{ $inspection->site->siteTypeLabel() }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ \App\Support\SalesComplianceCatalog::statusBadgeClass($inspection->status) }}">{{ $inspection->statusLabel() }}</span>
                    <a href="{{ route('sales-compliance.inspections.edit', $inspection) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ $inspection->isPending() ? __('Record checklist') : __('Update checklist') }}</a>
                    <a href="{{ route('sales-compliance.escalations.create', ['inspection_id' => $inspection->id]) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Escalate') }}</a>
                    <form method="POST" action="{{ route('sales-compliance.inspections.destroy', $inspection) }}" onsubmit="return confirm(@js(__('Delete this visit?')));">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-medium text-red-700 hover:bg-red-100">{{ __('Delete') }}</button>
                    </form>
                    <a href="{{ route('sales-compliance.hub') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Inspector') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $inspection->assigneeName() }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Location') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $inspection->site->locationDisplay() }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Contact') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $inspection->site->contact_name ?: '—' }}</dd>
                </div>
                @if ($inspection->site->site_type === \App\Support\SalesComplianceCatalog::SITE_PRIVATE_EVENT)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Event') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ collect([$inspection->site->event_type, $inspection->site->event_name])->filter()->implode(' · ') ?: '—' }}</dd>
                    </div>
                @endif
                @if ($inspection->meat_source)
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Meat source') }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ \App\Support\SalesComplianceCatalog::meatSourceLabel($inspection->meat_source) }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Created by') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $inspection->createdBy->name ?? '—' }} · {{ optional($inspection->created_at)->format('d M Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Updated by') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $inspection->updatedBy->name ?? '—' }} · {{ optional($inspection->updated_at)->format('d M Y H:i') }}</dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Checklist results') }}</p>
            </div>
            @if ($inspection->responses->isEmpty() && $inspection->productLines->isEmpty())
                <div class="px-6 py-10 text-center">
                    <p class="text-sm font-medium text-slate-800">{{ __('Checklist not recorded yet.') }}</p>
                    <a href="{{ route('sales-compliance.inspections.edit', $inspection) }}" class="mt-4 inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Record checklist') }}</a>
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($items as $item)
                        @php $response = $inspection->responses->firstWhere('item_key', $item['key']); @endphp
                        <li class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ $item['label'] }}</p>
                                    @if ($response?->notes)
                                        <p class="mt-1 text-sm text-slate-600">{{ $response->notes }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ in_array($response?->result, ['fail', 'missing'], true) ? 'bg-red-50 text-red-800' : 'bg-emerald-50 text-emerald-800' }}">
                                    {{ \App\Support\SalesComplianceCatalog::resultLabels()[$response?->result] ?? '—' }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($inspection->productLines->isNotEmpty())
                    <div class="border-t border-slate-100 px-4 py-3">
                        <p class="mb-2 text-xs font-semibold text-slate-700">{{ __('Meat products') }}</p>
                        <ul class="space-y-1 text-sm text-slate-800">
                            @foreach ($inspection->productLines as $line)
                                <li>{{ $line->product_name }}{{ $line->quantity_description ? ' · '.$line->quantity_description : '' }} · {{ \App\Support\SalesComplianceCatalog::resultLabels()[$line->certificate_status] ?? $line->certificate_status }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($inspection->inspector_notes)
                    <div class="border-t border-slate-100 px-4 py-3">
                        <p class="text-xs text-slate-500">{{ __('Inspector notes') }}</p>
                        <p class="mt-1 text-sm text-slate-800">{{ $inspection->inspector_notes }}</p>
                    </div>
                @endif
            @endif
        </section>

        @if ($inspection->attachments->isNotEmpty())
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Attachments') }}</p>
                </div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($inspection->attachments as $attachment)
                        <li class="flex items-center justify-between px-4 py-3 text-sm">
                            <span>{{ $attachment->original_name }} <span class="text-xs text-slate-400">{{ $attachment->isImage() ? __('Photo') : __('Document') }}</span></span>
                            <a href="{{ route('sales-compliance.inspections.attachments.download', [$inspection, $attachment]) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Download') }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($inspection->escalations->isNotEmpty())
            <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Escalations') }}</p>
                </div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($inspection->escalations as $escalation)
                        <li class="flex items-center justify-between px-4 py-3 text-sm">
                            <span>{{ $escalation->reason }}</span>
                            <a href="{{ route('sales-compliance.escalations.show', $escalation) }}" class="text-xs font-medium text-bucha-primary hover:underline">{{ $escalation->statusLabel() }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-app-layout>
