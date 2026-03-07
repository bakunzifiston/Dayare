<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h1 class="text-lg font-semibold text-slate-900">
                {{ __('Edit contract') }}
            </h1>
            <a href="{{ route('contracts.show', $contract) }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Back to contract') }}</a>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form method="POST" action="{{ route('contracts.update', $contract) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Contract details') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="business_id" :value="__('Business')" />
                        <select id="business_id" name="business_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]" required>
                            @foreach ($businesses as $b)
                                <option value="{{ $b->id }}" @selected(old('business_id', $contract->business_id) == $b->id)>{{ $b->business_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('business_id')" />
                    </div>
                    <div>
                        <x-input-label for="contract_number" :value="__('Contract number')" />
                        <x-text-input id="contract_number" name="contract_number" type="text" class="mt-1 block w-full" :value="old('contract_number', $contract->contract_number)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('contract_number')" />
                    </div>
                </div>
                <div>
                    <x-input-label for="title" :value="__('Title')" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $contract->title)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <select id="type" name="type" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]" required>
                            @foreach (\App\Models\Contract::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $contract->type) == $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('type')" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]" required>
                            @foreach (\App\Models\Contract::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $contract->status) == $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="start_date" :value="__('Start date')" />
                        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', $contract->start_date?->format('Y-m-d'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
                    </div>
                    <div>
                        <x-input-label for="end_date" :value="__('End date (optional)')" />
                        <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date', $contract->end_date?->format('Y-m-d'))" />
                        <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
                    </div>
                </div>
                <div>
                    <x-input-label for="amount" :value="__('Amount (optional)')" />
                    <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('amount', $contract->amount)" />
                    <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-800">{{ __('Counterparty (optional)') }}</h2>
                <div>
                    <x-input-label for="supplier_id" :value="__('Supplier')" />
                    <select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id', $contract->supplier_id) == $s->id)>{{ trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')) ?: ($s->name ?? 'Supplier #'.$s->id) }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('supplier_id')" />
                </div>
                <div>
                    <x-input-label for="facility_id" :value="__('Facility')" />
                    <select id="facility_id" name="facility_id" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($facilities as $f)
                            <option value="{{ $f->id }}" @selected(old('facility_id', $contract->facility_id) == $f->id)>{{ $f->facility_name }} ({{ $f->business?->business_name ?? '' }})</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('facility_id')" />
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div>
                    <x-input-label for="notes" :value="__('Notes (optional)')" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-[#3B82F6] focus:ring-[#3B82F6]">{{ old('notes', $contract->notes) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                    {{ __('Update contract') }}
                </button>
                <a href="{{ route('contracts.show', $contract) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-xs text-slate-700 hover:bg-slate-50">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
