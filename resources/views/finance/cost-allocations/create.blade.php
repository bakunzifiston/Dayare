<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('New cost allocation') }}</span>
    </x-slot>

    <div class="py-6 lg:py-8">
        <div class="max-w-[1000px] mx-auto px-0 sm:px-0 space-y-4">
            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
                <form method="POST" action="{{ route('finance.cost-allocations.store') }}">
                    @csrf
                    @include('finance.cost-allocations._form')

                    <div class="mt-6 flex items-center gap-2">
                        <button class="rounded-lg bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Create allocation') }}</button>
                        <a href="{{ route('finance.cost-allocations.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </section>

            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('Auto allocation template') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('Distribute one total amount across multiple batches.') }}</p>

                <form method="POST" action="{{ route('finance.cost-allocations.store-template') }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <x-input-label for="tpl_category" :value="__('Category')" />
                            <select id="tpl_category" name="category" class="mt-1 block w-full rounded-lg border-slate-300">
                                @foreach (['overhead', 'labor', 'logistics', 'utilities', 'other'] as $category)
                                    <option value="{{ $category }}">{{ ucfirst($category) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="tpl_allocation_date" :value="__('Allocation date')" />
                            <x-text-input id="tpl_allocation_date" name="allocation_date" type="date" class="mt-1 block w-full" :value="now()->format('Y-m-d')" required />
                        </div>
                        <div>
                            <x-input-label for="tpl_total_amount" :value="__('Total amount (RWF)')" />
                            <x-text-input id="tpl_total_amount" name="total_amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="tpl_distribution_mode" :value="__('Distribution mode')" />
                            <select id="tpl_distribution_mode" name="distribution_mode" class="mt-1 block w-full rounded-lg border-slate-300">
                                <option value="equal">{{ __('Equal split') }}</option>
                                <option value="quantity">{{ __('By batch quantity') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="tpl_distribution_scope" :value="__('Batch scope')" />
                            <select id="tpl_distribution_scope" name="distribution_scope" class="mt-1 block w-full rounded-lg border-slate-300">
                                <option value="all">{{ __('All listed batches') }}</option>
                                <option value="selected">{{ __('Only selected batches') }}</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="tpl_notes" :value="__('Notes')" />
                            <x-text-input id="tpl_notes" name="notes" type="text" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-sm font-medium text-slate-700">{{ __('Select batches (used when scope = Selected)') }}</p>
                        <div class="mt-2 max-h-44 overflow-y-auto space-y-1">
                            @foreach($batches as $batch)
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="batch_ids[]" value="{{ $batch->id }}" class="rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary">
                                    <span>{{ $batch->batch_code ?? ('#'.$batch->id) }} @if($batch->quantity !== null) — {{ number_format((float) $batch->quantity, 2) }} @endif</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">{{ __('Create template allocations') }}</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
