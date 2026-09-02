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
                    <p class="text-sm font-semibold text-slate-900">{{ $escalation->reason }}</p>
                    <p class="text-xs text-slate-500">{{ $escalation->site->name ?? '—' }}</p>
                </div>
                <a href="{{ route('sales-compliance.hub', ['view' => 'escalations']) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Status') }}</dt>
                    <dd class="mt-0.5">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ \App\Support\SalesComplianceCatalog::escalationBadgeClass($escalation->status) }}">{{ $escalation->statusLabel() }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Inspection') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        @if ($escalation->inspection)
                            <a href="{{ route('sales-compliance.inspections.show', $escalation->inspection) }}" class="text-bucha-primary hover:underline">{{ optional($escalation->inspection->scheduled_date)->format('d M Y') }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Created by') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $escalation->createdBy->name ?? '—' }} · {{ optional($escalation->created_at)->format('d M Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Updated by') }}</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $escalation->updatedBy->name ?? '—' }} · {{ optional($escalation->updated_at)->format('d M Y H:i') }}</dd>
                </div>
            </dl>
            @if ($escalation->notes)
                <div class="border-t border-slate-100 px-4 py-3">
                    <p class="text-xs text-slate-500">{{ __('Notes') }}</p>
                    <p class="mt-1 text-sm text-slate-800">{{ $escalation->notes }}</p>
                </div>
            @endif
            <form method="post" action="{{ route('sales-compliance.escalations.update', $escalation) }}" class="space-y-4 border-t border-slate-100 px-4 py-4">
                @csrf
                @method('PUT')
                <div>
                    <x-input-label for="status" :value="__('Escalation status')" />
                    <select id="status" name="status" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm">
                        @foreach (\App\Support\SalesComplianceCatalog::escalationStatusLabels() as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $escalation->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="notes" :value="__('Notes')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">{{ old('notes', $escalation->notes) }}</textarea>
                </div>
                <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Update status') }}</button>
            </form>
        </section>
    </div>
</x-app-layout>
