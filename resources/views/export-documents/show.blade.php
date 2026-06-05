<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ \App\Enums\MeatExportDocumentType::labelFor($document->document_type) }}
            </h2>
            <div class="flex gap-2">
                @if (auth()->user()->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_MANAGE_EXPORT_DOCUMENTS, auth()->user()->activeProcessorBusinessId()))
                    <a href="{{ route('export-documents.edit', [$confirmation, $document]) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">{{ __('Edit') }}</a>
                @endif
                <a href="{{ route('delivery-confirmations.show', $confirmation) }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Back') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Document number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $document->document_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($document->status) }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Issuing authority') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $document->issuing_authority ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Issued date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $document->issued_date?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Expiry date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $document->expiry_date?->format('d M Y') ?? '—' }}
                            @if ($document->isExpired()) <span class="text-red-600">({{ __('Expired') }})</span> @endif
                        </dd>
                    </div>
                    @if ($document->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Notes') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $document->notes }}</dd>
                    </div>
                    @endif
                    @if ($document->file_path)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Attachment') }}</dt>
                        <dd class="mt-1">
                            <a href="{{ route('export-documents.download', [$confirmation, $document]) }}" class="text-bucha-primary hover:underline">{{ __('Download file') }}</a>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
