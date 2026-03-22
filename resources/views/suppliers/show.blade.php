<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Supplier') }} — {{ trim(($supplier->first_name ?? '') . ' ' . ($supplier->last_name ?? '')) ?: ($supplier->name ?? '—') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('suppliers.edit', $supplier) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50">{{ __('Edit') }}</a>
                <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this supplier?') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">{{ __('Delete') }}</button>
                </form>
                <a href="{{ route('suppliers.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Back to list') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Business') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $supplier->business?->business_name ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Type') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ ucfirst($supplier->type ?? '') }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('First name') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $supplier->first_name ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Last name') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $supplier->last_name ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Date of birth') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $supplier->date_of_birth?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Nationality') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $supplier->nationality ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Registration number') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $supplier->registration_number ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Tax ID') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $supplier->tax_id ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Phone') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $supplier->phone ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Email') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $supplier->email ?? '—' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Status') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $supplier->is_active ? __('Active') : __('Inactive') }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Supplier status') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ \App\Models\Supplier::STATUSES[$supplier->supplier_status ?? 'approved'] ?? $supplier->supplier_status }} <span class="text-slate-500 text-xs">({{ __('Only approved can be used for animal intake') }})</span></dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">{{ __('Address / location') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $supplier->village?->name ?? $supplier->sectorDivision?->name ?? $supplier->districtDivision?->name ?? $supplier->province?->name ?? $supplier->country?->name ?? '—' }}</dd></div>
                    @if ($supplier->address_line_1 || $supplier->address_line_2)
                        <div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Address lines') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ trim(($supplier->address_line_1 ?? '') . ' ' . ($supplier->address_line_2 ?? '')) ?: '—' }}</dd></div>
                    @endif
                    @if ($supplier->notes)
                        <div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">{{ __('Notes') }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $supplier->notes }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
