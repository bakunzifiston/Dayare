@php
    use App\Models\PostMortemInspection;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Post-mortem inspections') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-xl font-medium text-gray-900">{{ __('Post-mortem inspections') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ __('Record and review post-mortem inspections for each batch. Each animal in the batch receives an individual outcome before certification.') }}
                    </p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ route('post-mortem-inspections.index') }}"
                       class="text-sm px-3 py-1.5 rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                        {{ __('View all') }}
                    </a>
                    <a href="{{ route('post-mortem-inspections.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                        {{ __('+ New inspection') }}
                    </a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total inspections') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_inspections']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Animals examined') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['animals_examined']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Condemned this week') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['condemned_this_week'] > 0 ? 'text-red-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['condemned_this_week']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Batches without PM') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['batches_without_pm'] > 0 ? 'text-amber-700' : 'text-slate-900' }}"
                       @if ($hubStats['batches_without_pm'] > 0) title="{{ __('Batches pending post-mortem inspection') }}" @endif>
                        {{ number_format($hubStats['batches_without_pm']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Ready for certificate') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['ready_for_cert'] > 0 ? 'text-blue-700' : 'text-slate-900' }}"
                       @if ($hubStats['ready_for_cert'] > 0) title="{{ __('PM approved, no certificate issued yet') }}" @endif>
                        {{ number_format($hubStats['ready_for_cert']) }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach ([PostMortemInspection::RESULT_APPROVED, PostMortemInspection::RESULT_PARTIAL, PostMortemInspection::RESULT_REJECTED] as $result)
                    @php
                        $resultInspections = $byResult->get($result, collect());
                        $badgeClass = match ($result) {
                            PostMortemInspection::RESULT_APPROVED => 'bg-green-100 text-green-700',
                            PostMortemInspection::RESULT_PARTIAL => 'bg-yellow-100 text-yellow-700',
                            PostMortemInspection::RESULT_REJECTED => 'bg-red-100 text-red-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-gray-700">{{ ucfirst($result) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $badgeClass }}">
                                {{ $resultInspections->count() }}
                            </span>
                        </div>
                        @forelse ($resultInspections->take(5) as $pm)
                            <div class="py-2 border-t border-gray-100 first:border-t-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-mono text-xs text-gray-800 truncate">
                                            {{ $pm->batch->batch_code ?? '—' }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $pm->batch->slaughterExecution->slaughterPlan->facility->facility_name ?? '—' }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ $pm->inspection_date?->format('d M Y') ?? '—' }}
                                        </p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-sm font-medium text-gray-700">
                                            {{ $pm->total_examined }}
                                            <span class="text-xs font-normal text-gray-400">{{ __('examined') }}</span>
                                        </p>
                                        @if ($pm->condemned_quantity > 0)
                                            <p class="text-xs text-red-600">{{ $pm->condemned_quantity }} {{ __('condemned') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-1.5 flex-wrap">
                                    <a href="{{ route('post-mortem-inspections.show', $pm) }}"
                                       class="text-xs text-blue-600 hover:underline">{{ __('View') }}</a>
                                    <a href="{{ route('post-mortem-inspections.edit', $pm) }}"
                                       class="text-xs text-gray-500 hover:underline">{{ __('Edit') }}</a>
                                    @if ($pm->approved_quantity > 0 && $pm->batch->canIssueCertificate())
                                        <a href="{{ route('certificates.create', ['batch_id' => $pm->batch_id]) }}"
                                           class="text-xs text-green-600 hover:underline font-medium">{{ __('Issue cert →') }}</a>
                                    @elseif ($pm->approved_quantity > 0 && ! $pm->batch->certificate)
                                        <a href="{{ route('cold-rooms.hub') }}"
                                           class="text-xs text-amber-600 hover:underline font-medium">{{ __('Release from cold room →') }}</a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 py-2">{{ __('No :result inspections.', ['result' => $result]) }}</p>
                        @endforelse
                        @if ($resultInspections->count() > 5)
                            <a href="{{ route('post-mortem-inspections.index', ['result' => $result]) }}"
                               class="block mt-2 text-xs text-blue-600 hover:underline text-center">
                                {{ __('View all :count →', ['count' => $resultInspections->count()]) }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-700">{{ __('Recent inspections') }}</p>
                    <a href="{{ route('post-mortem-inspections.index') }}"
                       class="text-xs text-blue-600 hover:underline">{{ __('View all →') }}</a>
                </div>
                @forelse ($recentInspections as $pm)
                    @php
                        $dot = match ($pm->result ?? 'pending') {
                            PostMortemInspection::RESULT_APPROVED => 'bg-green-500',
                            PostMortemInspection::RESULT_PARTIAL => 'bg-yellow-400',
                            PostMortemInspection::RESULT_REJECTED => 'bg-red-400',
                            default => 'bg-gray-300',
                        };
                    @endphp
                    <div class="flex items-center gap-4 px-4 py-3 border-b border-gray-100 last:border-b-0">
                        <div class="w-2 h-2 rounded-full {{ $dot }} flex-shrink-0" aria-hidden="true"></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-mono text-sm text-gray-800">{{ $pm->batch->batch_code ?? '—' }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $pm->batch->slaughterExecution->slaughterPlan->facility->facility_name ?? '—' }}
                                · {{ $pm->inspection_date?->format('d M Y') ?? '—' }}
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm text-gray-700">
                                {{ $pm->total_examined }} {{ __('examined') }}
                            </p>
                            @if ($pm->hasPerAnimalOutcomes())
                                <p class="text-xs text-gray-500">
                                    {{ $pm->inspectionItems->count() }} {{ __('animals recorded') }}
                                </p>
                            @else
                                <p class="text-xs text-gray-400">{{ __('Aggregate only') }}</p>
                            @endif
                        </div>
                        <a href="{{ route('post-mortem-inspections.show', $pm) }}"
                           class="text-xs text-blue-600 hover:underline flex-shrink-0">{{ __('View') }}</a>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 px-4 py-6 text-center">
                        {{ __('No post-mortem inspections recorded yet.') }}
                        <a href="{{ route('post-mortem-inspections.create') }}" class="text-blue-600 hover:underline">
                            {{ __('Record the first one →') }}
                        </a>
                    </p>
                @endforelse
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('post-mortem-inspections.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('All inspections') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Search the full list, open an inspection, edit or remove.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open list') }} →</span>
                </a>
                <a href="{{ route('batches.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Batches') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Each inspection is tied to a batch from slaughter execution.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Batches home') }} →</span>
                </a>
                <a href="{{ route('certificates.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Certificates') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Issue certificates when approved quantity is greater than zero.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Certification home') }} →</span>
                </a>
                <a href="{{ route('slaughter-executions.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Slaughter execution') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Post-mortem follows batch creation from a completed execution.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Execution home') }} →</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
