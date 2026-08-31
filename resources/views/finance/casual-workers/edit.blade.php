<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Edit casual worker') }}</span>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $worker->displayName() }}</p>
                    <p class="text-xs text-slate-500">{{ __('Used on casual AP bills.') }}</p>
                </div>
                <a href="{{ route('finance.casual-workers.index') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</a>
            </div>
            <form method="POST" action="{{ route('finance.casual-workers.update', $worker) }}" class="space-y-4 px-4 py-4">
                @csrf
                @method('PUT')
                @include('finance.casual-workers._form', ['worker' => $worker])
                <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                    <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">{{ __('Save changes') }}</button>
                    <a href="{{ route('finance.casual-workers.index') }}" class="inline-flex h-9 items-center px-2 text-xs font-medium text-slate-500 hover:text-slate-900">{{ __('Cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
