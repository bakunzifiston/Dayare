@php
    use App\Models\PostMortemInspection;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('certificates.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Certificates') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('All certificates') }}
                </h2>
            </div>
            <a href="{{ route('certificates.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('+ Issue certificate') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total issued') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_issued']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Active') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['active'] > 0 ? 'text-green-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['active']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Expired') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['expired'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['expired']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Revoked') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['revoked'] > 0 ? 'text-red-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['revoked']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Ready to issue') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['ready_to_issue'] > 0 ? 'text-blue-700' : 'text-slate-900' }}"
                       @if ($hubStats['ready_to_issue'] > 0) title="{{ __('Batches with PM approved, cold room released, and no certificate yet') }}" @endif>
                        {{ number_format($hubStats['ready_to_issue']) }}
                    </p>
                </div>
            </div>

            <form method="get" action="{{ route('certificates.index') }}" class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <div>
                        <label for="filter_status" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                        <select id="filter_status" name="status" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                            <option value="expired" @selected(request('status') === 'expired')>{{ __('Expired') }}</option>
                            <option value="revoked" @selected(request('status') === 'revoked')>{{ __('Revoked') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter_facility_id" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Facility') }}</label>
                        <select id="filter_facility_id" name="facility_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($facilities as $facility)
                                <option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->facility_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter_has_transport" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Has transport') }}</label>
                        <select id="filter_has_transport" name="has_transport" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="1" @selected(request('has_transport') === '1')>{{ __('Yes') }}</option>
                            <option value="0" @selected(request('has_transport') === '0')>{{ __('No') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter_issued_from" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Issued from') }}</label>
                        <input id="filter_issued_from" type="date" name="issued_from" value="{{ request('issued_from') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                    </div>
                    <div>
                        <label for="filter_issued_to" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Issued to') }}</label>
                        <input id="filter_issued_to" type="date" name="issued_to" value="{{ request('issued_to') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                        {{ __('Apply') }}
                    </button>
                    @if (request()->hasAny(['status', 'facility_id', 'has_transport', 'issued_from', 'issued_to']))
                        <a href="{{ route('certificates.index') }}" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Clear') }}</a>
                    @endif
                    <a href="{{ route('certificates.export', array_filter(request()->only(['status', 'facility_id', 'issued_from', 'issued_to']), fn ($v) => $v !== '' && $v !== null)) }}"
                       class="ml-auto inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold bg-bucha-primary text-white hover:bg-bucha-burgundy">
                        {{ __('Export PDF') }}
                    </a>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                @if ($certificates->isEmpty())
                    <p class="text-sm text-gray-500 py-8 text-center">
                        {{ __('No certificates found.') }}
                        @if (request()->hasAny(['status', 'facility_id', 'has_transport', 'issued_from', 'issued_to']))
                            {{ __('Try clearing the filters.') }}
                        @else
                            <a href="{{ route('certificates.create') }}" class="text-blue-600 hover:underline">{{ __('Issue the first one →') }}</a>
                        @endif
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Certificate no.') }}</th>
                                    <th class="px-4 py-3">{{ __('Batch') }}</th>
                                    <th class="px-4 py-3">{{ __('Issued') }}</th>
                                    <th class="px-4 py-3">{{ __('Expiry') }}</th>
                                    <th class="px-4 py-3">{{ __('Facility') }}</th>
                                    <th class="px-4 py-3">{{ __('Inspector') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('PM result') }}</th>
                                    <th class="px-4 py-3">{{ __('Transport') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($certificates as $cert)
                                    @php
                                        $pmResult = $cert->batch?->postMortemInspection?->result;
                                        $pmBadge = match ($pmResult) {
                                            PostMortemInspection::RESULT_APPROVED => 'bg-green-100 text-green-800',
                                            PostMortemInspection::RESULT_PARTIAL => 'bg-yellow-100 text-yellow-800',
                                            PostMortemInspection::RESULT_REJECTED => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-600',
                                        };
                                    @endphp
                                    <tr class="cert-row cursor-pointer hover:bg-slate-50/80" data-cert-id="{{ $cert->id }}">
                                        <td class="px-4 py-3 font-mono text-xs">
                                            <a href="{{ route('certificates.show', $cert) }}" class="text-bucha-primary hover:underline">
                                                {{ $cert->certificate_number ?? 'CERT-'.$cert->batch?->batch_code }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs">
                                            @if ($cert->batch)
                                                <a href="{{ route('batches.show', $cert->batch) }}" class="text-bucha-primary hover:underline">{{ $cert->batch->batch_code }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $cert->issued_at?->format('d M Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 {{ $cert->isExpired() ? 'text-red-600' : 'text-gray-600' }}">
                                            {{ $cert->expiry_date?->format('d M Y') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3">{{ $cert->facility?->facility_name ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $cert->inspector?->full_name ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $cert->status_badge_class }}">
                                                {{ $cert->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($pmResult)
                                                <span class="text-xs px-2 py-0.5 rounded-full {{ $pmBadge }}">{{ ucfirst($pmResult) }}</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($cert->transportTrips->count() > 0)
                                                {{ $cert->transportTrips->count() }} {{ __('trips') }}
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="cert-actions inline-flex gap-2">
                                                <a href="{{ route('certificates.show', $cert) }}" class="text-xs text-blue-600 hover:underline">{{ __('View') }}</a>
                                                <a href="{{ route('certificates.edit', $cert) }}" class="text-xs text-gray-500 hover:underline">{{ __('Edit') }}</a>
                                                <a href="{{ route('certificates.qr', $cert) }}" class="text-xs text-gray-500 hover:underline" target="_blank" rel="noopener">{{ __('QR') }}</a>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="cert-detail-row bg-gray-50" id="cert-detail-{{ $cert->id }}" style="display:none;">
                                        <td colspan="100" class="px-4 py-3">
                                            <div class="flex flex-wrap gap-6 text-sm text-gray-700 mb-2">
                                                <span>{{ __('Batch') }}: <strong class="font-mono">{{ $cert->batch?->batch_code ?? '—' }}</strong></span>
                                                <span>{{ __('Species') }}: <strong>{{ $cert->batch?->species ?? '—' }}</strong></span>
                                                @if ($cert->batch?->hasPerAnimalData())
                                                    <span>{{ __('Animals') }}: <strong>{{ $cert->batch->animal_count }}</strong></span>
                                                @else
                                                    <span>{{ __('Quantity') }}: <strong>{{ number_format($cert->batch?->quantity ?? 0, 2) }} {{ $cert->batch?->quantity_unit }}</strong></span>
                                                @endif
                                                @if ($cert->batch?->postMortemInspection)
                                                    <span class="text-green-700">{{ __('PM approved') }}: <strong>{{ $cert->batch->postMortemInspection->approved_quantity }}</strong></span>
                                                    @if ($cert->batch->postMortemInspection->condemned_quantity > 0)
                                                        <span class="text-red-600">{{ __('Condemned') }}: <strong>{{ $cert->batch->postMortemInspection->condemned_quantity }}</strong></span>
                                                    @endif
                                                @endif
                                            </div>
                                            @if ($cert->transportTrips->isNotEmpty())
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ __('Transport') }}: {{ $cert->transportTrips->count() }} {{ __('trip(s)') }}
                                                    — {{ __('latest') }}: {{ $cert->transportTrips->sortByDesc('created_at')->first()?->created_at?->format('d M Y') ?? '—' }}
                                                </p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $certificates->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.cert-row').forEach(function (row) {
                    row.addEventListener('click', function (e) {
                        if (e.target.closest('.cert-actions')) return;
                        var id = this.dataset.certId;
                        var detail = document.getElementById('cert-detail-' + id);
                        if (detail) detail.style.display = detail.style.display === 'none' ? '' : 'none';
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
