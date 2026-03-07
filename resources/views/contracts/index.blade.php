<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900">
                {{ __('Contracts') }}
            </h1>
            <a href="{{ route('contracts.create') }}" class="inline-flex items-center px-3 py-2 rounded-lg bg-[#3B82F6] text-white text-xs font-semibold hover:bg-[#2563eb]">
                {{ __('Add contract') }}
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <p class="text-sm text-slate-600">
                {{ __('Employee and supplier contracts. Select type when adding.') }}
            </p>
            <div class="flex gap-2">
                <a href="{{ route('contracts.index', request()->except('category')) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium {{ !request('category') ? 'bg-[#3B82F6] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ __('All') }}</a>
                <a href="{{ route('contracts.index', ['category' => 'employee'] + request()->only('page')) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium {{ request('category') === 'employee' ? 'bg-[#3B82F6] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ __('Employee') }}</a>
                <a href="{{ route('contracts.index', ['category' => 'supplier'] + request()->only('page')) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium {{ request('category') === 'supplier' ? 'bg-[#3B82F6] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ __('Supplier') }}</a>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800 text-sm">{{ session('status') }}</div>
        @endif

        @if ($contracts->isEmpty())
            <p class="text-sm text-slate-500">
                {{ __('No contracts yet.') }}
            </p>
            <a href="{{ route('contracts.create') }}" class="inline-flex items-center px-4 py-2 mt-4 rounded-lg bg-[#3B82F6] text-white text-sm font-medium hover:bg-[#2563eb]">{{ __('Create first contract') }}</a>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold text-slate-500 uppercase">
                            <th class="py-2 pr-4">{{ __('Contract number') }}</th>
                            <th class="py-2 pr-4">{{ __('Title') }}</th>
                            <th class="py-2 pr-4">{{ __('Category') }}</th>
                            <th class="py-2 pr-4">{{ __('Type') }}</th>
                            <th class="py-2 pr-4">{{ __('Business') }}</th>
                            <th class="py-2 pr-4">{{ __('Counterparty') }}</th>
                            <th class="py-2 pr-4">{{ __('Start') }}</th>
                            <th class="py-2 pr-4">{{ __('End') }}</th>
                            <th class="py-2 pr-4">{{ __('Status') }}</th>
                            <th class="py-2 pr-4 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($contracts as $contract)
                            <tr>
                                <td class="py-2 pr-4">
                                    <a href="{{ route('contracts.show', $contract) }}" class="text-indigo-600 hover:underline font-medium">{{ $contract->contract_number }}</a>
                                </td>
                                <td class="py-2 pr-4">{{ $contract->title }}</td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $contract->contract_category === 'employee' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ \App\Models\Contract::CATEGORIES[$contract->contract_category] ?? $contract->contract_category }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4">{{ $contract->type_label }}</td>
                                <td class="py-2 pr-4">{{ $contract->business?->business_name ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ $contract->counterparty_name }}</td>
                                <td class="py-2 pr-4">{{ $contract->start_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ $contract->end_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                        @if($contract->status === 'active') bg-emerald-50 text-emerald-700
                                        @elseif($contract->status === 'draft') bg-slate-100 text-slate-700
                                        @elseif($contract->status === 'expired') bg-amber-50 text-amber-700
                                        @else bg-slate-100 text-slate-600 @endif">
                                        {{ \App\Models\Contract::STATUSES[$contract->status] ?? $contract->status }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4 text-right whitespace-nowrap">
                                    <a href="{{ route('contracts.show', $contract) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">{{ __('View') }}</a>
                                    <span class="text-slate-300 mx-1">|</span>
                                    <a href="{{ route('contracts.edit', $contract) }}" class="text-slate-600 hover:text-slate-900 text-xs font-medium">{{ __('Edit') }}</a>
                                    <span class="text-slate-300 mx-1">|</span>
                                    <form method="POST" action="{{ route('contracts.destroy', $contract) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this contract?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-xs font-medium">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $contracts->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
