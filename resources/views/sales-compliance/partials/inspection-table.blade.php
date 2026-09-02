@php
    $rows = $rows ?? collect();
    $empty = $empty ?? __('No inspections.');
@endphp
@if ($rows->isEmpty())
    <div class="px-6 py-10 text-center">
        <p class="text-sm font-medium text-slate-800">{{ $empty }}</p>
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-2.5">{{ __('When') }}</th>
                    <th class="px-4 py-2.5">{{ __('Site') }}</th>
                    <th class="px-4 py-2.5">{{ __('Type') }}</th>
                    <th class="px-4 py-2.5">{{ __('Inspector') }}</th>
                    <th class="px-4 py-2.5">{{ __('Status') }}</th>
                    <th class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $inspection)
                    <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                        <td class="whitespace-nowrap px-4 py-2.5 text-slate-800">
                            {{ optional($inspection->scheduled_date)->format('d M Y') }}
                            <span class="text-slate-400">{{ $inspection->scheduledTimeDisplay() }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-slate-800">{{ $inspection->site->name ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-slate-700">{{ $inspection->site?->siteTypeLabel() ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-slate-700">{{ $inspection->assigneeName() }}</td>
                        <td class="whitespace-nowrap px-4 py-2.5">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ \App\Support\SalesComplianceCatalog::statusBadgeClass($inspection->status) }}">{{ $inspection->statusLabel() }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-2.5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('sales-compliance.inspections.show', $inspection) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('View') }}</a>
                                <a href="{{ route('sales-compliance.inspections.edit', $inspection) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ $inspection->isPending() ? __('Record') : __('Edit') }}</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if (method_exists($rows, 'links'))
        <div class="border-t border-slate-100 px-4 py-3">{{ $rows->links() }}</div>
    @endif
@endif
