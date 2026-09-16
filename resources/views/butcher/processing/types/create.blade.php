@php
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.processing.types.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Cut types') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-cut text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Add cut type') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ __('Define a cut for processing and catalog.') }}</p>
                    </div>
                </div>
            </div>

            <form method="post" action="{{ route('butcher.processing.types.store') }}" class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                @csrf
                <div class="space-y-6 p-4 sm:p-6">
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Cut type') }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="name" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Name') }}</label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="{{ $fieldClass }}" placeholder="T-Bone">
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <label for="meat_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Meat type') }}</label>
                                <select id="meat_type" name="meat_type" required class="{{ $fieldClass }}">
                                    @foreach ($meatTypes as $type)
                                        <option value="{{ $type }}" @selected(old('meat_type') === $type)>{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('meat_type')" class="mt-2" />
                            </div>
                            <div>
                                <label for="expected_yield_pct" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Expected yield %') }}</label>
                                <input id="expected_yield_pct" name="expected_yield_pct" type="number" step="0.01" min="0.01" max="100" value="{{ old('expected_yield_pct', '85') }}" required class="{{ $fieldClass }}">
                                <x-input-error :messages="$errors->get('expected_yield_pct')" class="mt-2" />
                            </div>
                        </div>
                    </section>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-6">
                    <a href="{{ route('butcher.processing.types.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                        <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                        {{ __('Add cut type') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
