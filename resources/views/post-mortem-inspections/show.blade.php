<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Post-mortem inspection') }} — {{ $inspection->inspection_date?->format('d M Y') ?? __('No date') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('post-mortem-inspections.edit', $inspection) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('batches.show', $inspection->batch) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('View batch') }}
                </a>
                <a href="{{ route('post-mortem-inspections.index') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
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
                        <dt class="text-sm font-medium text-gray-500">{{ __('Batch') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('batches.show', $inspection->batch) }}" class="text-indigo-600 hover:underline">
                                {{ $inspection->batch->batch_code }}
                            </a>
                            — {{ $inspection->batch->species }} ({{ $inspection->batch->quantity }})
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inspection date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->inspection_date?->format('l, d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inspector') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('inspectors.show', $inspection->inspector) }}" class="text-indigo-600 hover:underline">
                                {{ $inspection->inspector->full_name }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Total examined') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->total_examined }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Approved quantity') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->approved_quantity }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Condemned quantity') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inspection->condemned_quantity }}</dd>
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
