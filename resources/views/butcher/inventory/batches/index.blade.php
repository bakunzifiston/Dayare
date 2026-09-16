@php
    $filters = $filters ?? ['q' => '', 'status' => 'all', 'meat_type' => 'all'];
    $fmtKg = static fn ($v): string => number_format((float) $v, 2).' kg';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.inventory.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Inventory') }}
                </a>
            </div>

            <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                <form method="get" action="{{ route('butcher.inventory.batches.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="batch_q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Search') }}</label>
                            <input id="batch_q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Batch #, location…') }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                        </div>
                        <x-butcher.outlet-filter :outlets="$outlets" :selected="$filterOutletId" />
                        <div>
                            <label for="batch_status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
                            <select id="batch_status" name="status" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['status'] === 'all')>{{ __('All') }}</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="batch_meat" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Meat') }}</label>
                            <select id="batch_meat" name="meat_type" class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary">
                                <option value="all" @selected($filters['meat_type'] === 'all')>{{ __('All types') }}</option>
                                @foreach ($meatTypes as $type)
                                    <option value="{{ $type }}" @selected($filters['meat_type'] === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Apply') }}</button>
                        <a href="{{ route('butcher.inventory.batches.index') }}" class="inline-flex items-center rounded-bucha border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Reset') }}</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Inventory batches') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('Stock lots in storage.') }}</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ trans_choice(':count batch|:count batches', $batches->total(), ['count' => $batches->total()]) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Batch') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Meat') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Remaining') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5">{{ __('Age') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5">{{ __('Best before') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Status') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($batches as $batch)
                                <tr @class(['hover:bg-slate-50/80', 'bg-amber-50/40' => $batch->isExpiringSoon(), 'bg-red-50/30' => $batch->status === 'expired' || $batch->hasTemperatureBreach()])>
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-package text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-medium text-slate-900">{{ $batch->batch_number }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $batch->storage_location ?: ($batch->outlet?->name ?: '—') }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5 capitalize text-slate-700">{{ $batch->meat_type }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-right tabular-nums text-slate-700">{{ $fmtKg($batch->remaining_weight_kg) }}</td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $batch->ageInDays() }}d</td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $batch->best_before_date?->format('Y-m-d') ?: '—' }}</td>
                                    <td class="px-4 py-3 sm:px-5"><x-butcher.status-badge :status="$batch->status" /></td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <a href="{{ route('butcher.inventory.batches.show', $batch) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            <i class="ti ti-eye text-sm leading-none" aria-hidden="true"></i>
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                        {{ $filters['q'] !== '' || $filters['status'] !== 'all' || $filters['meat_type'] !== 'all' || $filterOutletId
                                            ? __('No batches match your filters.')
                                            : __('No batches yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($batches->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $batches->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
