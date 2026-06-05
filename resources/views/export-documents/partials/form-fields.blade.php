@php
    $document = $document ?? null;
@endphp
<div>
    <label for="document_type" class="block text-sm font-medium text-gray-700">{{ __('Document type') }}</label>
    <select id="document_type" name="document_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        <option value="">{{ __('Select type') }}</option>
        @foreach ($documentTypes as $type)
            <option value="{{ $type->value }}" @selected(old('document_type', $document?->document_type) === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('document_type')" class="mt-2" />
</div>
<div>
    <label for="document_number" class="block text-sm font-medium text-gray-700">{{ __('Document number') }}</label>
    <input type="text" id="document_number" name="document_number" value="{{ old('document_number', $document?->document_number) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
    <x-input-error :messages="$errors->get('document_number')" class="mt-2" />
</div>
<div>
    <label for="issuing_authority" class="block text-sm font-medium text-gray-700">{{ __('Issuing authority') }}</label>
    <input type="text" id="issuing_authority" name="issuing_authority" value="{{ old('issuing_authority', $document?->issuing_authority) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
    <x-input-error :messages="$errors->get('issuing_authority')" class="mt-2" />
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label for="issued_date" class="block text-sm font-medium text-gray-700">{{ __('Issued date') }}</label>
        <input type="date" id="issued_date" name="issued_date" value="{{ old('issued_date', $document?->issued_date?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
        <x-input-error :messages="$errors->get('issued_date')" class="mt-2" />
    </div>
    <div>
        <label for="expiry_date" class="block text-sm font-medium text-gray-700">{{ __('Expiry date') }}</label>
        <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date', $document?->expiry_date?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
        <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
    </div>
</div>
<div>
    <label for="status" class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
    <select id="status" name="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        @foreach ($statuses as $status)
            <option value="{{ $status }}" @selected(old('status', $document?->status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('status')" class="mt-2" />
</div>
<div>
    <label for="notes" class="block text-sm font-medium text-gray-700">{{ __('Notes') }}</label>
    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $document?->notes) }}</textarea>
    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
</div>
<div>
    <label for="file" class="block text-sm font-medium text-gray-700">{{ __('Attachment (PDF or image)') }}</label>
    <input type="file" id="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-gray-600" />
    @if ($document?->file_path)
        <p class="mt-1 text-sm text-gray-500">{{ __('Current file attached.') }}</p>
    @endif
    <x-input-error :messages="$errors->get('file')" class="mt-2" />
</div>
