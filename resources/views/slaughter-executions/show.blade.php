<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('slaughter-executions.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Slaughter execution') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Slaughter execution') }} — {{ $execution->slaughter_time->format('d M Y H:i') }}
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('slaughter-executions.edit', $execution) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('slaughter-executions.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('All executions') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Slaughter session ID') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('slaughter-plans.show', $execution->slaughterPlan) }}" class="text-bucha-primary hover:underline">
                                #{{ $execution->slaughter_plan_id }} — {{ $execution->slaughterPlan->slaughter_date->format('d M Y') }} ({{ $execution->slaughterPlan->facility->facility_name }})
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Slaughter time') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $execution->slaughter_time->format('l, d M Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Actual animals slaughtered') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $execution->actual_animals_slaughtered }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $execution->status)) }}</dd>
                    </div>
                </dl>
            </div>

            @if ($execution->batches->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Batches') }}</h3>
                    <ul class="divide-y divide-gray-200">
                        @foreach ($execution->batches as $b)
                            <li class="py-2">
                                <a href="{{ route('batches.show', $b) }}" class="font-medium text-bucha-primary hover:underline">{{ $b->batch_code }}</a>
                                <span class="text-sm text-gray-500"> {{ $b->species }} · {{ $b->quantity }} · {{ ucfirst($b->status) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('batches.create') }}" class="inline-flex items-center mt-2 text-sm text-bucha-primary hover:text-indigo-900">{{ __('Add batch') }}</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
