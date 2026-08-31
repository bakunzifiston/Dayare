<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('New cost allocation') }}</span>
    </x-slot>

    @php
        $ctrl = 'mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm';
        $categories = ['labor', 'logistics', 'overhead', 'utilities', 'other'];
        $selectedBatchIds = collect(old('batch_ids', []))->map(fn ($id) => (string) $id);
    @endphp

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Single allocation') }}</p>
                    <p class="text-xs text-slate-500">{{ __('Assign one amount to one batch.') }}</p>
                </div>
                <a href="{{ route('finance.cost-allocations.index') }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
            </div>
            <form method="POST" action="{{ route('finance.cost-allocations.store') }}" class="space-y-4 px-4 py-4">
                @csrf
                @include('finance.cost-allocations._form')
                <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                    <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Create allocation') }}</button>
                    <a href="{{ route('finance.cost-allocations.index') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Split across batches') }}</p>
                <p class="text-xs text-slate-500">{{ __('Distribute one total across several batches, equally or by quantity.') }}</p>
            </div>
            <form method="POST" action="{{ route('finance.cost-allocations.store-template') }}" class="space-y-4 px-4 py-4">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <x-input-label for="tpl_allocation_date" :value="__('Date')" />
                        <x-text-input id="tpl_allocation_date" name="allocation_date" type="date" class="{{ $ctrl }}" :value="old('allocation_date', now()->format('Y-m-d'))" required />
                        <x-input-error class="mt-1" :messages="$errors->get('allocation_date')" />
                    </div>
                    <div>
                        <x-input-label for="tpl_category" :value="__('Category')" />
                        <select id="tpl_category" name="category" class="{{ $ctrl }}" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected(old('category', 'overhead') === $category)>{{ ucfirst($category) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="tpl_total_amount" :value="__('Total amount (RWF)')" />
                        <x-text-input id="tpl_total_amount" name="total_amount" type="number" step="0.01" min="0.01" class="{{ $ctrl }}" :value="old('total_amount')" required />
                        <x-input-error class="mt-1" :messages="$errors->get('total_amount')" />
                    </div>
                    <div>
                        <x-input-label for="tpl_distribution_mode" :value="__('Split by')" />
                        <select id="tpl_distribution_mode" name="distribution_mode" class="{{ $ctrl }}">
                            <option value="equal" @selected(old('distribution_mode', 'equal') === 'equal')>{{ __('Equal split') }}</option>
                            <option value="quantity" @selected(old('distribution_mode') === 'quantity')>{{ __('Batch quantity') }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    <div>
                        <x-input-label for="tpl_distribution_scope" :value="__('Batches')" />
                        <select id="tpl_distribution_scope" name="distribution_scope" class="{{ $ctrl }}">
                            <option value="all" @selected(old('distribution_scope', 'all') === 'all')>{{ __('All listed batches') }}</option>
                            <option value="selected" @selected(old('distribution_scope') === 'selected')>{{ __('Only selected batches') }}</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="tpl_notes" :value="__('Notes')" />
                        <x-text-input id="tpl_notes" name="notes" type="text" class="{{ $ctrl }}" :value="old('notes')" />
                    </div>
                </div>

                <div id="tpl-batch-picker" class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-3 @if(old('distribution_scope', 'all') !== 'selected') hidden @endif">
                    <p class="text-xs font-medium text-slate-600">{{ __('Select batches') }}</p>
                    <div class="mt-2 grid max-h-48 grid-cols-1 gap-1 overflow-y-auto sm:grid-cols-2">
                        @forelse ($batches as $batch)
                            <label class="flex items-center gap-2 rounded-md px-1 py-1 text-sm text-slate-700 hover:bg-white">
                                <input type="checkbox" name="batch_ids[]" value="{{ $batch->id }}" class="rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary" @checked($selectedBatchIds->contains((string) $batch->id))>
                                <span>{{ $batch->batch_code ?? ('#'.$batch->id) }}@if($batch->quantity !== null) <span class="text-xs text-slate-400">{{ number_format((float) $batch->quantity, 2) }}</span>@endif</span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('No batches available.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                    <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white hover:bg-slate-800">{{ __('Create split allocations') }}</button>
                </div>
            </form>
        </section>
    </div>

    <script>
        (function () {
            var scope = document.getElementById('tpl_distribution_scope');
            var picker = document.getElementById('tpl-batch-picker');
            if (!scope || !picker) return;
            function sync() {
                picker.classList.toggle('hidden', scope.value !== 'selected');
            }
            scope.addEventListener('change', sync);
            sync();
        })();
    </script>
</x-app-layout>
