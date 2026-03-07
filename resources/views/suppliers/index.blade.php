<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900">
                {{ __('Suppliers') }}
            </h1>
            <a href="{{ route('suppliers.create') }}" class="inline-flex items-center px-3 py-2 rounded-lg bg-[#3B82F6] text-white text-xs font-semibold hover:bg-[#2563eb]">
                {{ __('Add supplier') }}
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-slate-600">
                {{ __('List of suppliers linked to your businesses.') }}
            </p>
        </div>

        @if ($suppliers->isEmpty())
            <p class="text-sm text-slate-500">
                {{ __('No suppliers found yet.') }}
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold text-slate-500 uppercase">
                            <th class="py-2 pr-4">{{ __('Supplier') }}</th>
                            <th class="py-2 pr-4">{{ __('Type') }}</th>
                            <th class="py-2 pr-4">{{ __('Business') }}</th>
                            <th class="py-2 pr-4">{{ __('Phone') }}</th>
                            <th class="py-2 pr-4">{{ __('Email') }}</th>
                            <th class="py-2 pr-4">{{ __('Status') }}</th>
                            <th class="py-2 pr-4">{{ __('Supplier status') }}</th>
                            <th class="py-2 pr-4 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($suppliers as $supplier)
                            <tr>
                                <td class="py-2 pr-4">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="text-indigo-600 hover:underline font-medium">{{ trim(($supplier->first_name ?? '') . ' ' . ($supplier->last_name ?? '')) ?: ($supplier->name ?? '—') }}</a>
                                </td>
                                <td class="py-2 pr-4">
                                    {{ ucfirst($supplier->type) }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ $supplier->business?->business_name ?? '—' }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ $supplier->phone ?? '—' }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ $supplier->email ?? '—' }}
                                </td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $supplier->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $supplier->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4">
                                    @php $ss = $supplier->supplier_status ?? 'approved'; @endphp
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $ss === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($ss === 'suspended' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">
                                        {{ \App\Models\Supplier::STATUSES[$ss] ?? $ss }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4 text-right whitespace-nowrap">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">{{ __('View') }}</a>
                                    <span class="text-slate-300 mx-1">|</span>
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="text-slate-600 hover:text-slate-900 text-xs font-medium">{{ __('Edit') }}</a>
                                    <span class="text-slate-300 mx-1">|</span>
                                    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this supplier?') }}');">
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
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>
</x-app-layout>

