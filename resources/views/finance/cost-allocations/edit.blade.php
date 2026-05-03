<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Edit cost allocation') }}</span>
    </x-slot>

    <div class="py-6 lg:py-8">
        <div class="max-w-[1000px] mx-auto px-0 sm:px-0">
            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
                <form method="POST" action="{{ route('finance.cost-allocations.update', $allocation) }}">
                    @csrf
                    @method('PUT')
                    @include('finance.cost-allocations._form')

                    <div class="mt-6 flex items-center gap-2">
                        <button class="rounded-lg bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Save changes') }}</button>
                        <a href="{{ route('finance.cost-allocations.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm">{{ __('Back') }}</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
