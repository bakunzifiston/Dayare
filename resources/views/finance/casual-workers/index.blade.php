<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Casual workers') }}</span>
    </x-slot>

    @php
        $workers = $workers ?? collect();
    @endphp

    <div class="space-y-5">
        <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3" aria-label="{{ __('Actions') }}">
            <div class="flex items-center gap-2 overflow-x-auto">
                <a href="{{ route('finance.payables.index', ['tab' => 'casual']) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Casual payables') }}</a>
                <a href="{{ route('finance.casual-workers.create') }}" class="ml-auto inline-flex h-9 shrink-0 items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white hover:bg-bucha-burgundy">
                    {{ __('Add casual worker') }}
                </a>
            </div>
        </section>

        @if (session('error'))
            <div class="rounded-bucha border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif
        @if (session('status'))
            <div class="rounded-bucha border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-bucha border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('Casual workers') }}</p>
                <p class="text-xs text-slate-500">{{ trans_choice(':count record|:count records', $workers->total(), ['count' => number_format($workers->total())]) }}</p>
            </div>

            @if ($workers->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-medium text-slate-800">{{ __('No casual workers yet') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Add a worker so they can appear on casual AP bills.') }}</p>
                    <a href="{{ route('finance.casual-workers.create') }}" class="mt-4 inline-flex h-10 items-center rounded-bucha bg-bucha-primary px-4 text-sm font-semibold text-white">{{ __('Add casual worker') }}</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('Name') }}</th>
                                <th class="px-4 py-3">{{ __('Phone') }}</th>
                                <th class="px-4 py-3">{{ __('National ID') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($workers as $worker)
                                <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $worker->displayName() }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $worker->phone ?? '—' }}</td>
                                    <td class="px-4 py-3 tabular-nums text-slate-600">{{ $worker->national_id ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            'bg-emerald-50 text-emerald-800' => $worker->is_active,
                                            'bg-slate-100 text-slate-600' => ! $worker->is_active,
                                        ])>{{ $worker->is_active ? __('Active') : __('Inactive') }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        @include('finance.casual-workers._row-actions', ['worker' => $worker])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">{{ $workers->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
