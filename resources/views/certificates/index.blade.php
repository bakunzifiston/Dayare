<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Certificates') }}
            </h2>
            <a href="{{ route('certificates.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                {{ __('Issue certificate') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total certificates') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('Active') }}" :value="$kpis['active']" color="green" />
            </div>
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($certificates->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No certificates issued yet.') }}</p>
                    <p class="text-sm mb-4">{{ __('Certificates can only be issued for batches with a post-mortem inspection where approved quantity is greater than zero.') }}</p>
                    <a href="{{ route('certificates.create') }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                        {{ __('Issue first certificate') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($certificates as $cert)
                            <li class="p-4 flex justify-between items-center hover:bg-slate-50/80 transition-colors">
                                <div>
                                    <a href="{{ route('certificates.show', $cert) }}" class="font-medium text-gray-900 hover:underline">
                                        {{ $cert->certificate_number ?: __('Certificate') }} #{{ $cert->id }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        @if ($cert->batch)
                                            {{ $cert->batch->batch_code }} — {{ $cert->facility?->facility_name ?? '' }}
                                        @else
                                            {{ $cert->facility?->facility_name ?? '' }}
                                        @endif
                                        · {{ $cert->issued_at?->format('d M Y') ?? '' }} · {{ ucfirst($cert->status) }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ __('Inspector') }}: {{ $cert->inspector->full_name }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('certificates.show', $cert) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('View') }}</a>
                                    <a href="{{ route('certificates.edit', $cert) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-4 border-t">
                        {{ $certificates->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
