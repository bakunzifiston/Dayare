<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Edit operating expense') }}</span>
    </x-slot>
    <div class="py-6 lg:py-8">
        <div class="max-w-[1100px] mx-auto space-y-4">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @include('finance.partials.payment-panel', ['document' => $expense, 'documentType' => 'expense'])
            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
                <form method="POST" action="{{ route('finance.expenses.update', $expense) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('finance.expenses._form')
                    <div class="mt-6 flex items-center gap-2">
                        <button class="rounded-lg bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Save changes') }}</button>
                        <a href="{{ route('finance.expenses.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm">{{ __('Back') }}</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
