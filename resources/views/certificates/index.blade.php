<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('certificates.hub') }}" class="text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">{{ __('← Certificates') }}</a>
                <h2 class="mt-1 font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('All certificates') }}
                </h2>
            </div>
            <a href="{{ route('certificates.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
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

            <section class="mb-6 rounded-xl border border-slate-200/60 bg-white p-4 shadow-sm">
                <form method="GET" action="{{ route('certificates.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                    <div class="md:col-span-2">
                        <x-input-label for="search" :value="__('Search')" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$filters['search']" placeholder="{{ __('Certificate number or ID') }}" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                            <option value="">{{ __('All') }}</option>
                            @foreach (\App\Models\Certificate::STATUSES as $status)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="facility_id" :value="__('Facility')" />
                        <select id="facility_id" name="facility_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($facilities as $facility)
                                <option value="{{ $facility->id }}" @selected((string) $filters['facility_id'] === (string) $facility->id)>{{ $facility->facility_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="issued_from" :value="__('Issued from')" />
                        <x-text-input id="issued_from" name="issued_from" type="date" class="mt-1 block w-full" :value="$filters['issued_from']" />
                    </div>
                    <div>
                        <x-input-label for="issued_to" :value="__('Issued to')" />
                        <x-text-input id="issued_to" name="issued_to" type="date" class="mt-1 block w-full" :value="$filters['issued_to']" />
                    </div>
                    <div class="md:col-span-6 flex flex-wrap items-center gap-2 pt-1">
                        <x-primary-button>{{ __('Apply filters') }}</x-primary-button>
                        <a href="{{ route('certificates.index') }}" class="inline-flex items-center px-3 py-2 rounded-md border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('Reset') }}
                        </a>
                        <a href="{{ route('certificates.export', array_filter($filters, fn ($value) => $value !== '')) }}" class="ml-auto inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold bg-bucha-primary text-white hover:bg-bucha-burgundy">
                            {{ __('Export PDF') }}
                        </a>
                    </div>
                </form>
            </section>
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($certificates->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No certificates issued yet.') }}</p>
                    <p class="text-sm mb-4">{{ __('Certificates can only be issued for batches with a post-mortem inspection where approved quantity is greater than zero.') }}</p>
                    <a href="{{ route('certificates.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                        {{ __('Issue first certificate') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($certificates as $cert)
                            <li class="p-4 flex justify-between items-center hover:bg-slate-50/80 transition-colors">
                                <div>
                                    <a href="{{ route('certificates.show', $cert) }}" class="font-medium text-slate-900 hover:text-bucha-primary">
                                        {{ $cert->certificate_number ?: __('Certificate') }} #{{ $cert->id }}
                                    </a>
                                    <p class="text-sm text-slate-500">
                                        @if ($cert->batch)
                                            {{ $cert->batch->batch_code }} — {{ $cert->facility?->facility_name ?? '' }}
                                        @else
                                            {{ $cert->facility?->facility_name ?? '' }}
                                        @endif
                                        · {{ $cert->issued_at?->format('d M Y') ?? '' }} · {{ ucfirst($cert->status) }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        {{ __('Inspector') }}: {{ $cert->inspector->full_name }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('certificates.show', $cert) }}" class="text-sm text-bucha-primary hover:text-indigo-900">{{ __('View') }}</a>
                                    <a href="{{ route('certificates.edit', $cert) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $certificates->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
