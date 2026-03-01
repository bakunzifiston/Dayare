<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Batch') }} — {{ $batch->batch_code }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('batches.edit', $batch) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('batches.index') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                    {{ __('Back to list') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Batch code') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $batch->batch_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($batch->status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Slaughter execution') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('slaughter-executions.show', $batch->slaughterExecution) }}" class="text-indigo-600 hover:underline">
                                {{ $batch->slaughterExecution->slaughter_time->format('d M Y H:i') }} — {{ $batch->slaughterExecution->slaughterPlan->facility->facility_name }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inspector') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('inspectors.show', $batch->inspector) }}" class="text-indigo-600 hover:underline">
                                {{ $batch->inspector->full_name }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Species') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $batch->species }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Quantity') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $batch->quantity }}</dd>
                    </div>
                </dl>
            </div>

            @if ($batch->postMortemInspection)
                @php $pm = $batch->postMortemInspection; @endphp
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Post-mortem inspection') }}</h3>
                        <a href="{{ route('post-mortem-inspections.edit', $pm) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                    </div>
                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div><dt class="text-sm text-gray-500">{{ __('Inspector') }}</dt><dd class="text-sm"><a href="{{ route('inspectors.show', $pm->inspector) }}" class="text-indigo-600 hover:underline">{{ $pm->inspector->full_name }}</a></dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Inspection date') }}</dt><dd class="text-sm">{{ $pm->inspection_date?->format('d M Y') ?? '—' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Total examined') }}</dt><dd class="text-sm">{{ $pm->total_examined }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Approved quantity') }}</dt><dd class="text-sm">{{ $pm->approved_quantity }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('Condemned quantity') }}</dt><dd class="text-sm">{{ $pm->condemned_quantity }}</dd></div>
                        @if ($pm->notes)<div class="sm:col-span-2"><dt class="text-sm text-gray-500">{{ __('Notes') }}</dt><dd class="text-sm whitespace-pre-wrap">{{ $pm->notes }}</dd></div>@endif
                    </dl>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Post-mortem inspection') }}</h3>
                    <p class="text-sm text-gray-500 mb-2">{{ __('No post-mortem inspection recorded for this batch yet.') }}</p>
                    <a href="{{ route('post-mortem-inspections.create') }}" class="text-sm text-indigo-600 hover:underline">{{ __('Record post-mortem inspection') }}</a>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Certificate') }}</h3>
                @if ($batch->certificate)
                    <p class="text-sm text-gray-600 mb-2">{{ __('Certificate issued.') }}</p>
                    <a href="{{ route('certificates.show', $batch->certificate) }}" class="font-medium text-indigo-600 hover:underline">{{ $batch->certificate->certificate_number ?: __('Certificate') }} #{{ $batch->certificate->id }}</a>
                    <span class="text-sm text-gray-500"> · {{ $batch->certificate->issued_at?->format('d M Y') }} · {{ ucfirst($batch->certificate->status) }}</span>
                @elseif ($batch->canIssueCertificate())
                    <p class="text-sm text-gray-500 mb-2">{{ __('This batch is eligible for a certificate (post-mortem approved quantity &gt; 0).') }}</p>
                    <a href="{{ route('certificates.create') }}" class="text-sm text-indigo-600 hover:underline">{{ __('Issue certificate') }}</a>
                @else
                    <p class="text-sm text-gray-500">{{ __('Certificate can be issued only after a post-mortem inspection with approved quantity greater than zero.') }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
