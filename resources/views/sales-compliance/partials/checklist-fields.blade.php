@php
    $siteType = $inspection->site->site_type;
    $needsMeatSource = in_array($siteType, [
        \App\Support\SalesComplianceCatalog::SITE_RESTAURANT,
        \App\Support\SalesComplianceCatalog::SITE_BUTCHERY,
        \App\Support\SalesComplianceCatalog::SITE_PRIVATE_EVENT,
    ], true);
    $needsProducts = in_array($siteType, [
        \App\Support\SalesComplianceCatalog::SITE_BUTCHERY,
        \App\Support\SalesComplianceCatalog::SITE_PRIVATE_EVENT,
    ], true);
    $existingResponses = $inspection->responses->keyBy('item_key');
    $existingLines = old('product_lines', $inspection->productLines->map(fn ($line) => [
        'product_name' => $line->product_name,
        'quantity_description' => $line->quantity_description,
        'certificate_status' => $line->certificate_status,
    ])->all() ?: [['product_name' => '', 'quantity_description' => '', 'certificate_status' => \App\Support\SalesComplianceCatalog::RESULT_MISSING]]);
    $ruleMap = collect($rulesPreview ?? [])->mapWithKeys(fn ($rule, $source) => [
        is_object($rule) ? $rule->meat_source : $source => is_object($rule) ? (bool) $rule->certificate_required : (bool) $rule,
    ]);
@endphp

@if ($needsMeatSource)
    <div>
        <x-input-label for="meat_source" :value="__('Meat source')" />
        <select id="meat_source" name="meat_source" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm">
            <option value="">{{ __('Select meat source') }}</option>
            @foreach ($meatSources as $value => $label)
                <option value="{{ $value }}" @selected(old('meat_source', $inspection->meat_source) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">{{ __('Certificate of origin is required or skipped based on configurable meat-source rules.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('meat_source')" />
    </div>
@endif

<div class="space-y-4">
    @foreach ($items as $item)
        @php
            $current = old('responses.'.$item['key'].'.result', $existingResponses[$item['key']]->result ?? '');
            $notes = old('responses.'.$item['key'].'.notes', $existingResponses[$item['key']]->notes ?? '');
            $options = $item['kind'] === \App\Support\SalesComplianceCatalog::KIND_PASS_FAIL
                ? [\App\Support\SalesComplianceCatalog::RESULT_PASS, \App\Support\SalesComplianceCatalog::RESULT_FAIL]
                : [\App\Support\SalesComplianceCatalog::RESULT_PRESENT, \App\Support\SalesComplianceCatalog::RESULT_MISSING];
        @endphp
        <div class="rounded-lg border border-slate-100 p-3 {{ $item['certificate'] ? 'js-certificate-item' : '' }}" @if ($item['certificate']) data-certificate-item="1" @endif>
            <div class="flex flex-wrap items-start justify-between gap-2">
                <p class="text-sm font-medium text-slate-900">{{ $item['label'] }}</p>
                <select name="responses[{{ $item['key'] }}][result]" class="h-9 rounded-lg border-slate-200 text-sm">
                    <option value="">{{ __('Select') }}</option>
                    @foreach ($options as $option)
                        <option value="{{ $option }}" @selected($current === $option)>{{\App\Support\SalesComplianceCatalog::resultLabels()[$option]}}</option>
                    @endforeach
                </select>
            </div>
            <textarea name="responses[{{ $item['key'] }}][notes]" rows="2" class="mt-2 block w-full rounded-lg border-slate-200 text-sm" placeholder="{{ __('Inspector notes') }}">{{ $notes }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('responses.'.$item['key'].'.result')" />
        </div>
    @endforeach
</div>

@if ($needsProducts)
    <div>
        <div class="mb-2 flex items-center justify-between">
            <p class="text-sm font-semibold text-slate-900">{{ __('Meat products on site') }}</p>
            <button type="button" id="add-product-line" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Add product') }}</button>
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('product_lines')" />
        <div id="product-lines" class="space-y-2">
            @foreach ($existingLines as $index => $line)
                <div class="product-line grid gap-2 rounded-lg border border-slate-100 p-3 sm:grid-cols-3">
                    <input type="text" name="product_lines[{{ $index }}][product_name]" value="{{ $line['product_name'] ?? '' }}" class="h-9 rounded-lg border-slate-200 text-sm" placeholder="{{ __('Product name') }}">
                    <input type="text" name="product_lines[{{ $index }}][quantity_description]" value="{{ $line['quantity_description'] ?? '' }}" class="h-9 rounded-lg border-slate-200 text-sm" placeholder="{{ __('Quantity / description') }}">
                    <select name="product_lines[{{ $index }}][certificate_status]" class="h-9 rounded-lg border-slate-200 text-sm js-product-cert">
                        <option value="{{ \App\Support\SalesComplianceCatalog::RESULT_PRESENT }}" @selected(($line['certificate_status'] ?? '') === \App\Support\SalesComplianceCatalog::RESULT_PRESENT)>{{ __('Certificate present') }}</option>
                        <option value="{{ \App\Support\SalesComplianceCatalog::RESULT_MISSING }}" @selected(($line['certificate_status'] ?? \App\Support\SalesComplianceCatalog::RESULT_MISSING) === \App\Support\SalesComplianceCatalog::RESULT_MISSING)>{{ __('Certificate missing') }}</option>
                    </select>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div>
    <x-input-label for="inspector_notes" :value="__('Inspector notes')" />
    <textarea id="inspector_notes" name="inspector_notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">{{ old('inspector_notes', $inspection->inspector_notes) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('inspector_notes')" />
</div>

<div>
    <x-input-label for="attachments" :value="__('Photos and documents')" />
    <input id="attachments" name="attachments[]" type="file" multiple accept="image/*,.pdf,application/pdf" class="mt-1 block w-full text-sm text-slate-600">
    <p class="mt-1 text-xs text-slate-500">{{ __('Images or PDF, up to 10 MB each.') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('attachments')" />
    <x-input-error class="mt-2" :messages="$errors->get('attachments.*')" />
</div>

@if ($inspection->attachments->isNotEmpty())
    <ul class="space-y-1 text-sm">
        @foreach ($inspection->attachments as $attachment)
            <li>
                <a href="{{ route('sales-compliance.inspections.attachments.download', [$inspection, $attachment]) }}" class="text-bucha-primary hover:underline">{{ $attachment->original_name }}</a>
                <span class="text-xs text-slate-400">{{ $attachment->isImage() ? __('Photo') : __('Document') }}</span>
            </li>
        @endforeach
    </ul>
@endif

<script>
    (function () {
        const rules = @json($ruleMap);
        const source = document.getElementById('meat_source');
        const certItems = document.querySelectorAll('[data-certificate-item]');
        function syncCert() {
            if (!source) return;
            const required = source.value ? (rules[source.value] !== false) : true;
            certItems.forEach((el) => {
                el.classList.toggle('opacity-50', !required);
                const select = el.querySelector('select');
                if (select) select.disabled = !required;
            });
        }
        if (source) {
            source.addEventListener('change', syncCert);
            syncCert();
        }

        const list = document.getElementById('product-lines');
        const addBtn = document.getElementById('add-product-line');
        if (list && addBtn) {
            addBtn.addEventListener('click', function () {
                const index = list.querySelectorAll('.product-line').length;
                const wrap = document.createElement('div');
                wrap.className = 'product-line grid gap-2 rounded-lg border border-slate-100 p-3 sm:grid-cols-3';
                wrap.innerHTML = `
                    <input type="text" name="product_lines[${index}][product_name]" class="h-9 rounded-lg border-slate-200 text-sm" placeholder="{{ __('Product name') }}">
                    <input type="text" name="product_lines[${index}][quantity_description]" class="h-9 rounded-lg border-slate-200 text-sm" placeholder="{{ __('Quantity / description') }}">
                    <select name="product_lines[${index}][certificate_status]" class="h-9 rounded-lg border-slate-200 text-sm js-product-cert">
                        <option value="present">{{ __('Certificate present') }}</option>
                        <option value="missing" selected>{{ __('Certificate missing') }}</option>
                    </select>`;
                list.appendChild(wrap);
            });
        }
    })();
</script>
