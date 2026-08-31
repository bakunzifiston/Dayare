<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('New operating expense') }}</span>
    </x-slot>
    <div class="py-6 lg:py-8">
        <div class="max-w-[1100px] mx-auto">
            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
                <p class="mb-4 text-sm text-slate-600">{{ __('Record a daily operating cost. This is not an AP bill and not a batch cost allocation.') }}</p>
                <form method="POST" action="{{ route('finance.expenses.store') }}" enctype="multipart/form-data">
                    @csrf
                    @include('finance.expenses._form')
                    <div class="mt-6 flex items-center gap-2">
                        <button class="rounded-lg bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Save expense') }}</button>
                        <a href="{{ route('finance.expenses.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
