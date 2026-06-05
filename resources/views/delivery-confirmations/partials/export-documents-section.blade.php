@if ($confirmation->isInternationalExport())
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">{{ __('International export documents') }}</h3>
            @if (auth()->user()->canProcessorPermission(\App\Models\BusinessUser::PERMISSION_MANAGE_EXPORT_DOCUMENTS, auth()->user()->activeProcessorBusinessId()))
                <a href="{{ route('export-documents.create', $confirmation) }}" class="inline-flex items-center px-3 py-1.5 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                    {{ __('Add document') }}
                </a>
            @endif
        </div>

        @if ($confirmation->exportDocuments->isEmpty())
            <p class="text-sm text-gray-500">{{ __('No export documents recorded yet.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">{{ __('Type') }}</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">{{ __('Number') }}</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">{{ __('Issuing authority') }}</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">{{ __('Issued') }}</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">{{ __('Expires') }}</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">{{ __('Status') }}</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($confirmation->exportDocuments as $doc)
                            <tr>
                                <td class="px-3 py-2">{{ \App\Enums\MeatExportDocumentType::labelFor($doc->document_type) }}</td>
                                <td class="px-3 py-2">{{ $doc->document_number ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $doc->issuing_authority ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $doc->issued_date?->format('d M Y') ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    {{ $doc->expiry_date?->format('d M Y') ?? '—' }}
                                    @if ($doc->isExpired())
                                        <span class="ml-1 inline-flex px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">{{ __('Expired') }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $doc->status === 'issued' ? 'bg-green-100 text-green-800' : ($doc->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ ucfirst($doc->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('export-documents.show', [$confirmation, $doc]) }}" class="text-bucha-primary hover:underline">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (! $confirmation->allExportDocumentsIssued())
            <div class="mt-4 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                {{ __('Not all required export documents have been issued. This shipment cannot be marked as fully compliant until all four documents are in issued status.') }}
            </div>
        @endif
    </div>
@endif
