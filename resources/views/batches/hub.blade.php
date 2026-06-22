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

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('batches.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('All batches') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Search the full list, open a batch, edit or remove.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open list') }} →</span>
                </a>
                <a href="{{ route('slaughter-executions.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Slaughter execution') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Batches must be linked to a completed execution.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Execution home') }} →</span>
                </a>
                <a href="{{ route('post-mortem-inspections.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0V3m0 2v4m0-4h4m-4 0H9"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Post-mortem') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Record inspection and approved quantity per batch.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Post-mortem home') }} →</span>
                </a>
                <a href="{{ route('certificates.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Certificates') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Issue certificates when post-mortem approves quantity.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Certification home') }} →</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
