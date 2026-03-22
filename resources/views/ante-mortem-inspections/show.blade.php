<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Ante-mortem inspection') }} — {{ $inspection->inspection_date->format('d M Y') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('ante-mortem-inspections.edit', $inspection) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('ante-mortem-inspections.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Back to list') }}
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
                            <a href="{{ route('slaughter-plans.show', $inspection->slaughterPlan) }}" class="text-bucha-primary hover:underline">
                                #{{ $inspection->slaughter_plan_id }} — {{ $inspection->slaughterPlan->slaughter_date->format('d M Y') }} ({{ $inspection->slaughterPlan->facility->facility_name }})
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inspection date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->inspection_date->format('l, d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inspector') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('inspectors.show', $inspection->inspector) }}" class="text-bucha-primary hover:underline">
                                {{ $inspection->inspector->full_name }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Species') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->species }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Number examined') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->number_examined }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Number approved') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->number_approved }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Number rejected') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->number_rejected }}</dd>
                    </div>
                    @if ($inspection->notes)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Notes') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $inspection->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
