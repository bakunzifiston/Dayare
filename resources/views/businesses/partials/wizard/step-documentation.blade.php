@php
    $business = $business ?? null;
    $docLabels = \App\Models\Business::supportingDocumentsLabelMap();
    $uploadedFiles = old('supporting_document_files', $business?->supporting_document_files ?? []);
    $acceptTypes = '.pdf,.doc,.docx,.jpg,.jpeg,.png';
    $fileInputClass = 'mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200';
@endphp
<div class="bucha-wizard-form space-y-8">
    <x-wizard-section :title="__('Documents checklist')" :subtitle="__('Tick which supporting documents you can provide, then upload a file for each type below (optional).')">
        <x-wizard-field :label="__('Supporting documents available')">
            @include('businesses.partials.wizard._survey-checkboxes', [
                'name' => 'supporting_documents',
                'options' => $docLabels,
                'selected' => old('supporting_documents', $business?->supporting_documents ?? []),
                'otherName' => 'supporting_documents_other',
                'otherValue' => old('supporting_documents_other', $business?->supporting_documents_other),
                'otherShowKey' => 'other',
            ])
            <x-input-error class="mt-2" :messages="$errors->get('supporting_documents')" />
        </x-wizard-field>
    </x-wizard-section>

    <x-wizard-section :title="__('Upload documents')" :subtitle="__('PDF, Word, or images up to 10 MB per file. Leave blank to skip.')">
        <div class="space-y-5">
            @foreach ($docLabels as $type => $label)
                @php
                    $storedPath = is_array($uploadedFiles) ? ($uploadedFiles[$type] ?? null) : null;
                    $inputId = 'document_uploads_'.$type;
                @endphp
                <div class="rounded-bucha border border-slate-200 bg-slate-50/80 p-4">
                    <label for="{{ $inputId }}" class="block text-sm font-semibold text-slate-800">{{ $label }}</label>
                    @if ($storedPath && $business)
                        <p class="mt-2 text-sm text-slate-600">
                            {{ __('Current file:') }}
                            <a
                                href="{{ route('businesses.document.download', [$business, $type, basename($storedPath)]) }}"
                                class="text-bucha-primary hover:text-bucha-burgundy font-medium underline"
                            >{{ basename($storedPath) }}</a>
                            <span class="text-slate-400">({{ __('upload a new file to replace') }})</span>
                        </p>
                    @endif
                    <input
                        id="{{ $inputId }}"
                        name="document_uploads[{{ $type }}]"
                        type="file"
                        class="{{ $fileInputClass }}"
                        accept="{{ $acceptTypes }}"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('document_uploads.'.$type)" />
                </div>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('document_uploads')" />
    </x-wizard-section>
</div>
