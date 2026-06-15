@php
    use App\Models\SlaughterExecution;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Slaughter execution') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-xl font-medium text-gray-900">{{ __('Slaughter execution') }}</h1>
                    <p class="text-sm text-gray-500 mt-1 max-w-2xl">
                        {{ __('Record and track slaughter sessions. Each execution links a slaughter plan to actual animals processed and individual meat yields.') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                    <a href="{{ route('slaughter-executions.index') }}"
                       class="text-sm px-3 py-1.5 rounded border border-gray-300 bg-white hover:bg-gray-50 text-gray-700">
                        {{ __('View all') }}
                    </a>
                    <a href="{{ route('slaughter-executions.create') }}"
                       class="text-sm px-3 py-1.5 rounded border border-gray-800 bg-gray-900 hover:bg-gray-800 text-white">
                        {{ __('+ New execution') }}
                    </a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total executions') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_executions']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Animals slaughtered') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_slaughtered']) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Total meat yield') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['total_meat_kg'], 2) }} kg</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Executions today') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['executions_today'] > 0 ? 'text-blue-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['executions_today']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Plans without execution') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['plans_without_execution'] > 0 ? 'text-amber-700' : 'text-slate-900' }}"
                       @if ($hubStats['plans_without_execution'] > 0) title="{{ __('Active plans with no execution recorded') }}" @endif>
                        {{ number_format($hubStats['plans_without_execution']) }}
                    </p>
                    @if ($hubStats['plans_without_execution'] > 0)
                        <p class="mt-0.5 text-xs text-amber-600">{{ __('Sessions awaiting slaughter execution') }}</p>
                    @endif
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Completed without batch') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['pending_batches'] > 0 ? 'text-red-700' : 'text-slate-900' }}"
                       @if ($hubStats['pending_batches'] > 0) title="{{ __('Completed executions with no batch created yet') }}" @endif>
                        {{ number_format($hubStats['pending_batches']) }}
                    </p>
                    @if ($hubStats['pending_batches'] > 0)
                        <p class="mt-0.5 text-xs text-red-600">{{ __('Ready for batch creation') }}</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ([SlaughterExecution::STATUS_SCHEDULED, SlaughterExecution::STATUS_IN_PROGRESS, SlaughterExecution::STATUS_COMPLETED, SlaughterExecution::STATUS_CANCELLED] as $status)
                    @php
                        $statusExecutions = $byStatus->get($status, collect());
                        $badgeClass = match ($status) {
                            SlaughterExecution::STATUS_SCHEDULED => 'bg-gray-100 text-gray-700',
                            SlaughterExecution::STATUS_IN_PROGRESS => 'bg-blue-100 text-blue-700',
                            SlaughterExecution::STATUS_COMPLETED => 'bg-green-100 text-green-700',
                            SlaughterExecution::STATUS_CANCELLED => 'bg-red-100 text-red-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                        $label = ucfirst(str_replace('_', ' ', $status));
                    @endphp
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-gray-700">{{ __($label) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $badgeClass }}">
                                {{ $statusExecutions->count() }}
                            </span>
                        </div>
                        @forelse ($statusExecutions->take(5) as $execution)
                            <div class="py-2 border-t border-gray-100 first:border-t-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm text-gray-800 truncate">
                                            {{ __('Plan #:id', ['id' => $execution->slaughter_plan_id]) }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $execution->slaughter_time->format('d M Y H:i') }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ $execution->slaughterPlan->facility->facility_name ?? '—' }}
                                        </p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-sm font-medium text-gray-700">
                                            {{ $execution->actual_animals_slaughtered }}
                                            <span class="text-xs font-normal text-gray-400">{{ __('animals') }}</span>
                                        </p>
                                        @if ($execution->hasPerAnimalSlaughter())
                                            <p class="text-xs text-gray-500">
                                                {{ number_format($execution->total_meat_quantity_kg, 1) }} kg
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2 mt-1.5">
                                    <a href="{{ route('slaughter-executions.show', $execution) }}"
                                       class="text-xs text-blue-600 hover:underline">{{ __('View') }}</a>
                                    <a href="{{ route('slaughter-executions.edit', $execution) }}"
                                       class="text-xs text-gray-500 hover:underline">{{ __('Edit') }}</a>
                                    @if ($status === SlaughterExecution::STATUS_COMPLETED && $execution->batches->isEmpty() && auth()->user()?->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_CREATE_BATCH))
                                        <a href="{{ route('batches.create', ['slaughter_execution_id' => $execution->id]) }}"
                                           class="text-xs text-green-600 hover:underline font-medium">{{ __('Create batch →') }}</a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 py-2">{{ __('No :status executions.', ['status' => strtolower($label)]) }}</p>
                        @endforelse
                        @if ($statusExecutions->count() > 5)
                            <a href="{{ route('slaughter-executions.index', ['status' => $status]) }}"
                               class="block mt-2 text-xs text-blue-600 hover:underline text-center">
                                {{ __('View all :count →', ['count' => $statusExecutions->count()]) }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-700">{{ __('Recent executions') }}</p>
                    <a href="{{ route('slaughter-executions.index') }}"
                       class="text-xs text-blue-600 hover:underline">{{ __('View all →') }}</a>
                </div>
                @forelse ($recentExecutions as $execution)
                    @php
                        $dotClass = match ($execution->status) {
                            SlaughterExecution::STATUS_SCHEDULED => 'bg-gray-400',
                            SlaughterExecution::STATUS_IN_PROGRESS => 'bg-blue-500',
                            SlaughterExecution::STATUS_COMPLETED => 'bg-green-500',
                            SlaughterExecution::STATUS_CANCELLED => 'bg-red-400',
                            default => 'bg-gray-300',
                        };
                    @endphp
                    <div class="flex items-center gap-4 px-4 py-3 border-b border-gray-100 last:border-b-0">
                        <div class="w-2 h-2 rounded-full {{ $dotClass }} flex-shrink-0" aria-hidden="true"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800">
                                {{ __('Plan #:id', ['id' => $execution->slaughter_plan_id]) }}
                                <span class="text-gray-400">·</span>
                                {{ $execution->slaughterPlan->facility->facility_name ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $execution->slaughter_time->format('d M Y H:i') }}
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm text-gray-700">
                                {{ $execution->actual_animals_slaughtered }} {{ __('animals') }}
                            </p>
                            @if ($execution->hasPerAnimalSlaughter())
                                <p class="text-xs text-gray-500">
                                    {{ number_format($execution->total_meat_quantity_kg, 1) }} kg {{ __('yield') }}
                                </p>
                            @else
                                <p class="text-xs text-gray-400">{{ __('No yield data') }}</p>
                            @endif
                        </div>
                        <a href="{{ route('slaughter-executions.show', $execution) }}"
                           class="text-xs text-blue-600 hover:underline flex-shrink-0">{{ __('View') }}</a>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 px-4 py-6 text-center">
                        {{ __('No slaughter executions recorded yet.') }}
                        <a href="{{ route('slaughter-executions.create') }}" class="text-blue-600 hover:underline">
                            {{ __('Record the first one →') }}
                        </a>
                    </p>
                @endforelse
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <a href="{{ route('slaughter-plans.hub') }}"
                   class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm text-gray-700 shadow-sm">
                    <i class="ti ti-calendar-event text-gray-400" aria-hidden="true"></i>
                    {{ __('Slaughter plans') }}
                </a>
                <a href="{{ route('ante-mortem-inspections.index') }}"
                   class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm text-gray-700 shadow-sm">
                    <i class="ti ti-stethoscope text-gray-400" aria-hidden="true"></i>
                    {{ __('Ante-mortem') }}
                </a>
                <a href="{{ route('batches.hub') }}"
                   class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm text-gray-700 shadow-sm">
                    <i class="ti ti-box text-gray-400" aria-hidden="true"></i>
                    {{ __('Batches') }}
                </a>
                <a href="{{ route('slaughter-executions.index') }}"
                   class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-sm text-gray-700 shadow-sm">
                    <i class="ti ti-list text-gray-400" aria-hidden="true"></i>
                    {{ __('Full list') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
