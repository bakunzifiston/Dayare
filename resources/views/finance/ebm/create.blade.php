<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('New EBM record') }}</span>
    </x-slot>
    <div class="py-6 lg:py-8">
        <div class="max-w-[1100px] mx-auto">
            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
                <form method="POST" action="{{ route('finance.ebm.store') }}">
                    @csrf
                    @include('finance.ebm._form')
                    <div class="mt-6 flex items-center gap-2">
                        <button class="rounded-lg bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Save EBM record') }}</button>
                        <a href="{{ route('finance.ebm.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
