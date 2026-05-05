@php
    use App\Models\FinancePayable;
@endphp
<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('AP payables') }}</span>
    </x-slot>

    <div class="py-6 lg:py-8">
        <div class="max-w-[1400px] mx-auto px-0 sm:px-0 space-y-4">
            @include('finance.payables._tabs', ['activeTab' => $activeTab, 'filters' => $filters])

            <section class="rounded-bucha border border-slate-200 bg-white px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search payable number or note') }}" class="h-9 rounded-lg border border-slate-200 px-3 text-sm">
                        <select name="status" class="h-9 rounded-lg border border-slate-200 px-2 text-sm">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach (['open', 'overdue', 'paid', 'cancelled'] as $s)
                                <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="h-9 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white">{{ __('Filter') }}</button>
                    </form>
                    <a href="{{ route('finance.payables.create', ['tab' => $activeTab]) }}" class="h-9 inline-flex items-center rounded-lg bg-bucha-primary px-3 text-xs font-semibold text-white">{{ __('New payable') }}</a>
                </div>
            </section>

            <section class="rounded-bucha border border-slate-200 bg-white overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="text-left px-4 py-2">{{ __('Payable') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Type') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Counterparty') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Status') }}</th>
                                <th class="text-right px-4 py-2">{{ __('Total') }}</th>
                                <th class="text-right px-4 py-2">{{ __('Paid') }}</th>
                                <th class="text-left px-4 py-2">{{ __('Due') }}</th>
                                <th class="text-right px-4 py-2">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payables as $payable)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2 font-medium text-slate-800">{{ $payable->payable_number }}</td>
                                    <td class="px-4 py-2">
                                        @if ($payable->ap_bucket === FinancePayable::BUCKET_EMPLOYEE)
                                            <span class="rounded bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800">{{ __('Employee') }}</span>
                                        @elseif ($payable->ap_bucket === FinancePayable::BUCKET_CASUAL_WORKER)
                                            <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900">{{ __('Casual') }}</span>
                                        @elseif ($payable->ap_bucket === FinancePayable::BUCKET_CLIENT)
                                            <span class="rounded bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-900">{{ __('Client') }}</span>
                                        @else
                                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-800">{{ __('Supplier') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">{{ $payable->counterpartyLabel() }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($payable->status) }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format((float) $payable->total_amount, 2) }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format((float) $payable->amount_paid, 2) }}</td>
                                    <td class="px-4 py-2">{{ optional($payable->due_date)->format('Y-m-d') ?? '—' }}</td>
                                    <td class="px-4 py-2">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('finance.payables.edit', $payable).'?tab='.$payable->payablesTabKey() }}" class="text-bucha-primary">{{ __('Edit') }}</a>
                                            @if ((float) $payable->amount_paid < (float) $payable->total_amount)
                                                <form method="POST" action="{{ route('finance.payables.mark-paid', $payable) }}">
                                                    @csrf
                                                    <button class="text-green-700">{{ __('Mark paid') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">{{ __('No payables found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-slate-100">{{ $payables->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
