<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Compliance') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Open escalation') }}</p>
                    <p class="text-xs text-slate-500">{{ __('Track follow-up for failed or flagged sites.') }}</p>
                </div>
                <a href="{{ route('sales-compliance.hub', ['view' => 'escalations']) }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
            </div>
            <div class="px-4 py-4">
                <form method="post" action="{{ route('sales-compliance.escalations.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <x-input-label for="site_id" :value="__('Site')" />
                        <select id="site_id" name="site_id" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" required>
                            <option value="">{{ __('Select site') }}</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}" @selected((string) old('site_id', $inspection?->site_id) === (string) $site->id)>{{ $site->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('site_id')" />
                    </div>
                    <input type="hidden" name="inspection_id" value="{{ old('inspection_id', $inspection?->id) }}">
                    <div>
                        <x-input-label for="reason" :value="__('Reason')" />
                        <x-text-input id="reason" name="reason" type="text" class="mt-1 h-9 block w-full rounded-lg border-slate-200 text-sm" :value="old('reason', $inspection ? __('Failed inspection follow-up') : '')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                    </div>
                    <div>
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">{{ old('notes') }}</textarea>
                    </div>
                    <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                        <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Open escalation') }}</button>
                        <a href="{{ route('sales-compliance.hub', ['view' => 'escalations']) }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-app-layout>
