<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Edit AP payable') }}</span>
    </x-slot>

    <div class="py-6 lg:py-8">
        <div class="max-w-[1100px] mx-auto px-0 sm:px-0 space-y-4">
            @include('finance.payables._tabs', ['activeTab' => $payable->payablesTabKey(), 'filters' => ['status' => '', 'q' => '']])

            <section class="rounded-bucha border border-slate-200 bg-white px-4 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs text-slate-500">{{ __('Payable') }}</p>
                        <p class="text-lg font-semibold text-slate-900">{{ $payable->payable_number }}</p>
                    </div>
                    @if ((float) $payable->amount_paid < (float) $payable->total_amount)
                        <form method="POST" action="{{ route('finance.payables.mark-paid', $payable) }}">
                            @csrf
                            <button class="rounded-lg border border-green-300 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700">{{ __('Mark paid') }}</button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="rounded-bucha border border-slate-200 bg-white px-5 py-5">
                <form method="POST" action="{{ route('finance.payables.update', $payable) }}">
                    @csrf
                    @method('PUT')
                    @include('finance.payables._form', ['activeTab' => $payable->payablesTabKey()])

                    <div class="mt-6 flex items-center gap-2">
                        <button class="rounded-lg bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Save changes') }}</button>
                        <a href="{{ route('finance.payables.index', ['tab' => $payable->payablesTabKey()]) }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm">{{ __('Back') }}</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
