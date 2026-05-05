<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Edit casual worker') }}</span>
    </x-slot>

    <div class="py-6 lg:py-8">
        <div class="max-w-[720px] mx-auto px-0 sm:px-0">
            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
                <form method="POST" action="{{ route('finance.casual-workers.update', $worker) }}">
                    @csrf
                    @method('PUT')
                    @include('finance.casual-workers._form', ['worker' => $worker])
                    <div class="mt-6 flex gap-2">
                        <button type="submit" class="rounded-lg bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Save changes') }}</button>
                        <a href="{{ route('finance.casual-workers.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm">{{ __('Back') }}</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
