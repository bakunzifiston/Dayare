<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Suppliers') }}
            </h2>
            <a href="{{ route('suppliers.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Add supplier') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('Active') }}" :value="$kpis['active']" color="green" />
                <x-kpi-card inline title="{{ __('Approved') }}" :value="$kpis['approved']" color="slate" />
            </div>
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($suppliers->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No suppliers yet.') }}</p>
                    <p class="text-sm mb-4">{{ __('Suppliers linked to your businesses for livestock and supply contracts.') }}</p>
                    <a href="{{ route('suppliers.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">{{ __('Add first supplier') }}</a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <th class="px-4 py-3">{{ __('Supplier') }}</th>
                                    <th class="px-4 py-3">{{ __('Type') }}</th>
                                    <th class="px-4 py-3">{{ __('Business') }}</th>
                                    <th class="px-4 py-3">{{ __('Phone') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($suppliers as $supplier)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('suppliers.show', $supplier) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ trim(($supplier->first_name ?? '') . ' ' . ($supplier->last_name ?? '')) ?: ($supplier->name ?? '—') }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ $supplier->type ? (\App\Models\Contract::SUPPLIER_TYPES[$supplier->type] ?? ucfirst(str_replace('_', ' ', $supplier->type))) : '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $supplier->business?->business_name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $supplier->phone ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            @php $ss = $supplier->supplier_status ?? 'approved'; @endphp
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $ss === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($ss === 'suspended' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">
                                                {{ \App\Models\Supplier::STATUSES[$ss] ?? $ss }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('suppliers.show', $supplier) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('View') }}</a>
                                            <span class="text-slate-300 mx-1">|</span>
                                            <a href="{{ route('suppliers.edit', $supplier) }}" class="text-slate-600 hover:text-slate-800 text-xs font-medium">{{ __('Edit') }}</a>
                                            <span class="text-slate-300 mx-1">|</span>
                                            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this supplier?') }}');">
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
                    <div class="px-4 py-3 border-t border-slate-100">{{ $suppliers->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
