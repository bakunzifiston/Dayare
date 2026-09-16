@php
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.stock-counts.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Stock counts') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-clipboard-check text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Start count') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ __('Create a draft count for active batches.') }}</p>
                    </div>
                </div>
            </div>

            <form method="post" action="{{ route('butcher.stock-counts.store') }}" class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                @csrf

                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Count') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="outlet_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Outlet') }}</label>
                                <select id="outlet_id" name="outlet_id" class="{{ $fieldClass }}">
                                    <option value="">{{ __('All outlets') }}</option>
                                    @foreach ($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('outlet_id')" class="mt-2" />
                            </div>
                            <div>
                                <label for="count_date" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Count date') }}</label>
                                <input id="count_date" name="count_date" type="date" value="{{ old('count_date', now()->toDateString()) }}" class="{{ $fieldClass }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="notes" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Notes') }}</label>
                                <textarea id="notes" name="notes" rows="3" class="{{ $fieldClass }}">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.stock-counts.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                        {{ __('Start count') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
