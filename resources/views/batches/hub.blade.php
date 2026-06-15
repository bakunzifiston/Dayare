@php
    use App\Models\Batch;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Batches') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-xl font-medium text-gray-900">{{ __('Batches') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ __('Create and manage meat batches from slaughter executions. Each batch carries individual animals through post-mortem and certification.') }}
                    </p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ route('batches.index') }}"
                       class="text-sm px-3 py-1.5 rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                        {{ __('View all') }}
                    </a>
                    <a href="{{ route('batches.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                        {{ __('+ New batch') }}
                    </a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total batches') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_batches']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Pending post-mortem') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['pending_pm'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['pending_pm']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Ready for certificate') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['ready_for_cert'] > 0 ? 'text-blue-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['ready_for_cert']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Cold chain issues') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['cold_chain_issues'] > 0 ? 'text-red-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['cold_chain_issues']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total quantity') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_quantity'], 2) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @php
                    $batchColumns = [
                        [
                            'label' => __('Recent batches'),
                            'batches' => $recentBatches,
                            'badgeClass' => 'bg-blue-100 text-blue-700',
                            'emptyMessage' => __('No batches recorded yet.'),
                            'viewAllRoute' => route('batches.index'),
                        ],
                        [
                            'label' => __('Approved'),
                            'batches' => $byStatus->get('approved', collect()),
                            'badgeClass' => 'bg-green-100 text-green-700',
                            'emptyMessage' => __('No approved batches.'),
                            'viewAllRoute' => route('batches.index', ['status' => 'approved']),
                        ],
                        [
                            'label' => __('Rejected'),
                            'batches' => $byStatus->get('rejected', collect()),
                            'badgeClass' => 'bg-red-100 text-red-700',
                            'emptyMessage' => __('No rejected batches.'),
                            'viewAllRoute' => route('batches.index', ['status' => 'rejected']),
                        ],
                    ];
                @endphp
                @foreach ($batchColumns as $column)
                    @php $statusBatches = $column['batches']; @endphp
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-gray-700">{{ $column['label'] }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $column['badgeClass'] }}">
                                {{ $statusBatches->count() }}
                            </span>
                        </div>
                        @forelse ($statusBatches->take(5) as $batch)
                            <div class="py-2 border-t border-gray-100 first:border-t-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-mono text-xs text-gray-800 truncate">{{ $batch->batch_code }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $batch->slaughterExecution->slaughterPlan->facility->facility_name ?? '—' }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $batch->species }}</p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        @if ($batch->hasPerAnimalData())
                                            <p class="text-sm font-medium text-gray-700">{{ $batch->animal_count }}
                                                <span class="text-xs font-normal text-gray-400">{{ __('animals') }}</span>
                                            </p>
                                        @else
                                            <p class="text-sm font-medium text-gray-700">
                                                {{ number_format($batch->quantity, 2) }}
                                                <span class="text-xs font-normal text-gray-400">{{ $batch->quantity_unit }}</span>
                                            </p>
                                        @endif
                                        <span class="text-xs px-1.5 py-0.5 rounded-full {{ $batch->cold_chain_badge_class }}">
                                            {{ ucfirst(str_replace('_', ' ', $batch->cold_chain_status)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 mt-1.5">
                                    @if ($batch->postMortemInspection)
                                        <span class="text-xs text-green-600">{{ __('PM done') }}</span>
                                    @else
                                        <span class="text-xs text-amber-600">{{ __('PM pending') }}</span>
                                    @endif
                                </div>
                                <div class="flex gap-2 mt-1.5 flex-wrap">
                                    <a href="{{ route('batches.show', $batch) }}"
                                       class="text-xs text-blue-600 hover:underline">{{ __('View') }}</a>
                                    <a href="{{ route('batches.edit', $batch) }}"
                                       class="text-xs text-gray-500 hover:underline">{{ __('Edit') }}</a>
                                    @if (! $batch->postMortemInspection)
                                        <a href="{{ route('post-mortem-inspections.create', ['batch_id' => $batch->id]) }}"
                                           class="text-xs text-amber-600 hover:underline font-medium">{{ __('Post-mortem →') }}</a>
                                    @elseif ($batch->canIssueCertificate() && ! $batch->certificate)
                                        <a href="{{ route('certificates.create', ['batch_id' => $batch->id]) }}"
                                           class="text-xs text-green-600 hover:underline font-medium">{{ __('Issue cert →') }}</a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 py-2">{{ $column['emptyMessage'] }}</p>
                        @endforelse
                        @if ($statusBatches->count() > 5)
                            <a href="{{ $column['viewAllRoute'] }}"
                               class="block mt-2 text-xs text-blue-600 hover:underline text-center">
                                {{ __('View all :count →', ['count' => $statusBatches->count()]) }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                <a href="{{ route('slaughter-executions.hub') }}"
                   class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm text-gray-700 shadow-sm">
                    <i class="ti ti-calendar-event text-gray-400" aria-hidden="true"></i>
                    {{ __('Slaughter executions') }}
                </a>
                <a href="{{ route('post-mortem-inspections.hub') }}"
                   class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm text-gray-700 shadow-sm">
                    <i class="ti ti-stethoscope text-gray-400" aria-hidden="true"></i>
                    {{ __('Post-mortem') }}
                </a>
                <a href="{{ route('cold-rooms.hub') }}"
                   class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm text-gray-700 shadow-sm">
                    <i class="ti ti-temperature text-gray-400" aria-hidden="true"></i>
                    {{ __('Cold rooms') }}
                </a>
                <a href="{{ route('certificates.hub') }}"
                   class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm text-gray-700 shadow-sm">
                    <i class="ti ti-certificate text-gray-400" aria-hidden="true"></i>
                    {{ __('Certificates') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
