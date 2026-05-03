<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Cost allocations') }}</span>
    </x-slot>

    <div class="py-6 lg:py-8">
        <div class="max-w-[1400px] mx-auto px-0 sm:px-0 space-y-4">
            <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <input type="text" name="category" value="{{ $filters['category'] ?? '' }}" placeholder="{{ __('Category') }}" class="h-9 rounded-lg border border-slate-200 px-3 text-sm">
                        <select name="batch_id" class="h-9 rounded-lg border border-slate-200 px-2 text-sm">
                            <option value="">{{ __('All batches') }}</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" @selected((string) ($filters['batch_id'] ?? '') === (string) $batch->id)>
                                    {{ $batch->batch_code ?? ('#'.$batch->id) }}
                                </option>
                            @endforeach
                        </select>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="h-9 rounded-lg border border-slate-200 px-2 text-sm">
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="h-9 rounded-lg border border-slate-200 px-2 text-sm">
                        <button type="submit" class="h-9 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white">{{ __('Filter') }}</button>
                    </form>
                    <a href="{{ route('finance.cost-allocations.create') }}" class="h-9 inline-flex items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white">{{ __('New allocation') }}</a>
                </div>
                <p class="mt-2 text-xs text-slate-600">{{ __('Total filtered amount: RWF :amount', ['amount' => number_format((float) $totalAmount, 2)]) }}</p>
            </section>

            <section class="rounded-bucha border border-slate-200 bg-white overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="text-left px-4 py-2">{{ __('Date') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Batch') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Category') }}</th>
                                <th class="text-right px-4 py-2">{{ __('Amount') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Source') }}</th>
                                <th class="text-right px-4 py-2">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allocations as $allocation)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2">{{ optional($allocation->allocation_date)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2">{{ $allocation->batch?->batch_code ?? ('#'.$allocation->batch_id) }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($allocation->category) }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format((float) $allocation->amount, 2) }}</td>
                                    <td class="px-4 py-2">{{ $allocation->source_type ? class_basename($allocation->source_type).' #'.$allocation->source_id : '—' }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="{{ route('finance.cost-allocations.edit', $allocation) }}" class="text-bucha-primary">{{ __('Edit') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No allocations found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-slate-100">{{ $allocations->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
