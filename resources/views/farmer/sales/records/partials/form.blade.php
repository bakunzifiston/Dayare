@php
    $sale = $sale ?? null;
    $lines = old('lines', $sale?->saleAnimals?->map(fn ($line) => [
        'animal_id' => $line->animal_id,
        'livestock_id' => $line->livestock_id,
        'sale_price' => $line->sale_price,
        'live_weight' => $line->live_weight,
        'price_per_kg' => $line->price_per_kg,
        'animal_condition' => $line->animal_condition,
        'remarks' => $line->remarks,
    ])->values()->all() ?? [['animal_id' => '', 'livestock_id' => '', 'sale_price' => '', 'live_weight' => '', 'price_per_kg' => '', 'animal_condition' => 'healthy', 'remarks' => '']]);
@endphp

<div class="space-y-8" x-data="saleForm(@js($lines))">
    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-base font-semibold text-slate-800">{{ __('Sale details') }}</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="farm_id" :value="__('Farm')" />
                <select id="farm_id" name="farm_id" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    <option value="">{{ __('Select farm') }}</option>
                    @foreach ($farms as $farm)
                        <option value="{{ $farm->id }}" @selected((int) old('farm_id', $sale?->farm_id) === $farm->id)>{{ $farm->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('farm_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="buyer_id" :value="__('Buyer')" />
                <select id="buyer_id" name="buyer_id" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    <option value="">{{ __('Select buyer') }}</option>
                    @foreach ($buyers as $buyer)
                        <option value="{{ $buyer->id }}" @selected((int) old('buyer_id', $sale?->buyer_id) === $buyer->id)>{{ $buyer->buyer_name }} ({{ $buyer->buyer_code }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('buyer_id')" class="mt-2" />
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="sale_type" :value="__('Sale type')" />
                <select id="sale_type" name="sale_type" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach (\App\Models\Sale::TYPES as $type)
                        <option value="{{ $type }}" @selected(old('sale_type', $sale?->sale_type ?? \App\Models\Sale::TYPE_INDIVIDUAL) === $type)>{{ __(ucwords(str_replace('_', ' ', $type))) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="sale_date" :value="__('Sale date')" />
                <x-text-input id="sale_date" name="sale_date" type="date" class="mt-1 block w-full" :value="old('sale_date', $sale?->sale_date?->format('Y-m-d') ?? now()->toDateString())" required />
            </div>
            <div>
                <x-input-label for="sale_status" :value="__('Sale status')" />
                <select id="sale_status" name="sale_status" class="mt-1 block w-full rounded-lg border-gray-300" required>
                    @foreach (\App\Models\Sale::STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('sale_status', $sale?->sale_status ?? \App\Models\Sale::STATUS_DRAFT) === $status)>{{ __(ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="payment_method" :value="__('Payment method')" />
                <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-lg border-gray-300">
                    <option value="">{{ __('Select method') }}</option>
                    @foreach (\App\Models\Sale::PAYMENT_METHODS as $method)
                        <option value="{{ $method }}" @selected(old('payment_method', $sale?->payment_method) === $method)>{{ __(ucwords(str_replace('_', ' ', $method))) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="currency" :value="__('Currency')" />
                <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" :value="old('currency', $sale?->currency ?? 'RWF')" />
            </div>
            <div>
                <x-input-label for="movement_permit_id" :value="__('Movement permit')" />
                <select id="movement_permit_id" name="movement_permit_id" class="mt-1 block w-full rounded-lg border-gray-300">
                    <option value="">{{ __('Optional') }}</option>
                    @foreach ($permits as $permit)
                        <option value="{{ $permit->id }}" @selected((int) old('movement_permit_id', $sale?->movement_permit_id) === $permit->id)>{{ $permit->permit_number }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="delivery_method" :value="__('Delivery method')" />
                <x-text-input id="delivery_method" name="delivery_method" type="text" class="mt-1 block w-full" :value="old('delivery_method', $sale?->delivery_method)" />
            </div>
            <div>
                <x-input-label for="destination" :value="__('Destination')" />
                <x-text-input id="destination" name="destination" type="text" class="mt-1 block w-full" :value="old('destination', $sale?->destination)" />
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="discount_amount" :value="__('Discount amount')" />
                <x-text-input id="discount_amount" name="discount_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('discount_amount', $sale?->discount_amount ?? 0)" />
            </div>
            <div>
                <x-input-label for="tax_amount" :value="__('Tax amount')" />
                <x-text-input id="tax_amount" name="tax_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('tax_amount', $sale?->tax_amount ?? 0)" />
            </div>
        </div>
        <div>
            <x-input-label for="notes" :value="__('Notes')" />
            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('notes', $sale?->notes) }}</textarea>
        </div>
        <div>
            <x-input-label for="attachment" :value="__('Attachment')" />
            <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm" />
        </div>
    </section>

    <section class="rounded-bucha border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-base font-semibold text-slate-800">{{ __('Sale animals') }}</h3>
            <button type="button" @click="addLine()" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('Add line') }}</button>
        </div>
        <x-input-error :messages="$errors->get('lines')" class="mt-2" />
        <template x-for="(line, index) in lines" :key="index">
            <div class="rounded-lg border border-slate-200 p-4 space-y-3">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700">{{ __('Animal') }}</label>
                        <select class="mt-1 block w-full rounded-lg border-gray-300" :name="`lines[${index}][animal_id]`" x-model="line.animal_id">
                            <option value="">{{ __('Optional') }}</option>
                            @foreach ($animals as $animal)
                                <option value="{{ $animal->id }}">{{ $animal->selectionLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">{{ __('Livestock group') }}</label>
                        <select class="mt-1 block w-full rounded-lg border-gray-300" :name="`lines[${index}][livestock_id]`" x-model="line.livestock_id">
                            <option value="">{{ __('Optional') }}</option>
                            @foreach ($livestock as $group)
                                <option value="{{ $group->id }}">{{ $group->livestock_code ?? $group->id }} · {{ $group->farm?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">{{ __('Live weight (kg)') }}</label>
                        <input type="number" step="0.01" min="0" class="mt-1 block w-full rounded-lg border-gray-300" :name="`lines[${index}][live_weight]`" x-model="line.live_weight" @input="recalculate(index)">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">{{ __('Price per kg') }}</label>
                        <input type="number" step="0.01" min="0" class="mt-1 block w-full rounded-lg border-gray-300" :name="`lines[${index}][price_per_kg]`" x-model="line.price_per_kg" @input="recalculate(index)">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">{{ __('Sale price') }}</label>
                        <input type="number" step="0.01" min="0" class="mt-1 block w-full rounded-lg border-gray-300" :name="`lines[${index}][sale_price]`" x-model="line.sale_price">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">{{ __('Condition') }}</label>
                        <select class="mt-1 block w-full rounded-lg border-gray-300" :name="`lines[${index}][animal_condition]`" x-model="line.animal_condition">
                            @foreach (\App\Models\SaleAnimal::CONDITIONS as $condition)
                                <option value="{{ $condition }}">{{ __(ucwords(str_replace('_', ' ', $condition))) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">{{ __('Remarks') }}</label>
                    <input type="text" class="mt-1 block w-full rounded-lg border-gray-300" :name="`lines[${index}][remarks]`" x-model="line.remarks">
                </div>
                <button type="button" @click="removeLine(index)" class="text-sm text-red-600 hover:underline" x-show="lines.length > 1">{{ __('Remove line') }}</button>
            </div>
        </template>
    </section>
</div>

<script>
    function saleForm(initialLines) {
        return {
            lines: initialLines.length ? initialLines : [{ animal_id: '', livestock_id: '', sale_price: '', live_weight: '', price_per_kg: '', animal_condition: 'healthy', remarks: '' }],
            addLine() {
                this.lines.push({ animal_id: '', livestock_id: '', sale_price: '', live_weight: '', price_per_kg: '', animal_condition: 'healthy', remarks: '' });
            },
            removeLine(index) {
                this.lines.splice(index, 1);
            },
            recalculate(index) {
                const line = this.lines[index];
                const weight = parseFloat(line.live_weight || 0);
                const pricePerKg = parseFloat(line.price_per_kg || 0);
                if (weight > 0 && pricePerKg > 0) {
                    line.sale_price = (weight * pricePerKg).toFixed(2);
                }
            },
        };
    }
</script>
