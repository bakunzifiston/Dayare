<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Contract') }} — {{ $contract->contract_number }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('contracts.edit', $contract) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Edit') }}</a>
                <form method="POST" action="{{ route('contracts.destroy', $contract) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this contract?') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">{{ __('Delete') }}</button>
                </form>
                <a href="{{ route('contracts.index') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Back to list') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            @if ($contract->isExpired() && $contract->status !== \App\Models\Contract::STATUS_EXPIRED)
                <div class="p-4 rounded-md bg-amber-50 text-amber-800 border border-amber-200">
                    {{ __('This contract has passed its end date. Consider updating the status to Expired.') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Contract number') }}</dt><dd class="mt-1 text-sm text-slate-900 font-medium">{{ $contract->contract_number }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Title') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->title }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Business') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->business?->business_name ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Counterparty') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->counterparty_name }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Type') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ \App\Models\Contract::TYPES[$contract->type] ?? $contract->type }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Status') }}</dt><dd class="mt-1"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                        @if($contract->status === 'active') bg-emerald-50 text-emerald-700
                        @elseif($contract->status === 'draft') bg-slate-100 text-slate-700
                        @elseif($contract->status === 'expired') bg-amber-50 text-amber-700
                        @else bg-slate-100 text-slate-600 @endif">{{ \App\Models\Contract::STATUSES[$contract->status] ?? $contract->status }}</span></dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Start date') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->start_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('End date') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->end_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Amount') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->amount !== null ? number_format($contract->amount, 2) : '—' }}</dd></div>
                    @if ($contract->notes)
                        <div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Notes') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $contract->notes }}</dd></div>
                    @endif
                </dl>
            </div>

            @if ($contract->supplier)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                    <h3 class="text-sm font-semibold text-slate-800 mb-2">{{ __('Linked supplier') }}</h3>
                    <p class="text-sm text-slate-600"><a href="{{ route('suppliers.show', $contract->supplier) }}" class="text-indigo-600 hover:underline">{{ $contract->counterparty_name }}</a></p>
                </div>
            @endif
            @if ($contract->facility)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                    <h3 class="text-sm font-semibold text-slate-800 mb-2">{{ __('Linked facility') }}</h3>
                    <p class="text-sm text-slate-600"><a href="{{ route('businesses.facilities.show', [$contract->facility->business, $contract->facility]) }}" class="text-indigo-600 hover:underline">{{ $contract->facility->facility_name }}</a></p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
