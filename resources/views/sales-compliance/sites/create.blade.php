<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Compliance') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Add site') }}</p>
                    <p class="text-xs text-slate-500">{{ __('Site type determines the inspection checklist.') }}</p>
                </div>
                <a href="{{ route('sales-compliance.sites.index') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
            </div>
            <div class="px-4 py-4">
                <form method="post" action="{{ route('sales-compliance.sites.store') }}" class="space-y-5">
                    @csrf
                    @include('sales-compliance.partials.site-fields', ['site' => null])
                    <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                        <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Save site') }}</button>
                        <a href="{{ route('sales-compliance.sites.index') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-app-layout>
