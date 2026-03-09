<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Contracts') }}
            </h2>
            <a href="{{ route('contracts.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Add contract') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('Active') }}" :value="$kpis['active']" color="green" />
                <x-kpi-card inline title="{{ __('Draft') }}" :value="$kpis['draft']" color="slate" />
            </div>

            <div class="mb-4 flex flex-wrap gap-2">
                <a href="{{ route('contracts.index', request()->except('category')) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium {{ !request('category') ? 'bg-[#3B82F6] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ __('All') }}</a>
                <a href="{{ route('contracts.index', ['category' => 'employee'] + request()->only('page')) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium {{ request('category') === 'employee' ? 'bg-[#3B82F6] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ __('Employee') }}</a>
                <a href="{{ route('contracts.index', ['category' => 'supplier'] + request()->only('page')) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium {{ request('category') === 'supplier' ? 'bg-[#3B82F6] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ __('Supplier') }}</a>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($contracts->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No contracts yet.') }}</p>
                    <p class="text-sm mb-4">{{ __('Employee and supplier contracts. Select type when adding.') }}</p>
                    <a href="{{ route('contracts.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Create first contract') }}</a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <th class="px-4 py-3">{{ __('Contract #') }}</th>
                                    <th class="px-4 py-3">{{ __('Title') }}</th>
                                    <th class="px-4 py-3">{{ __('Category') }}</th>
                                    <th class="px-4 py-3">{{ __('Type') }}</th>
                                    <th class="px-4 py-3">{{ __('Business') }}</th>
                                    <th class="px-4 py-3">{{ __('Counterparty') }}</th>
                                    <th class="px-4 py-3">{{ __('Start') }}</th>
                                    <th class="px-4 py-3">{{ __('End') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($contracts as $contract)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('contracts.show', $contract) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $contract->contract_number }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ Str::limit($contract->title, 35) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $contract->contract_category === 'employee' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">
                                                {{ \App\Models\Contract::CATEGORIES[$contract->contract_category] ?? $contract->contract_category }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ $contract->type_label }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $contract->business?->business_name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $contract->counterparty_name }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $contract->start_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $contract->end_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                @if($contract->status === 'active') bg-emerald-50 text-emerald-700
                                                @elseif($contract->status === 'draft') bg-slate-100 text-slate-700
                                                @elseif($contract->status === 'expired') bg-amber-50 text-amber-700
                                                @else bg-slate-100 text-slate-600 @endif">
                                                {{ \App\Models\Contract::STATUSES[$contract->status] ?? $contract->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('contracts.show', $contract) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('View') }}</a>
                                            <span class="text-slate-300 mx-1">|</span>
                                            <a href="{{ route('contracts.edit', $contract) }}" class="text-slate-600 hover:text-slate-800 text-xs font-medium">{{ __('Edit') }}</a>
                                            <span class="text-slate-300 mx-1">|</span>
                                            <form method="POST" action="{{ route('contracts.destroy', $contract) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this contract?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('Delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $contracts->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
