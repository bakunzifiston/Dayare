<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Facilities') }} — {{ $business->business_name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('businesses.facilities.create', $business) }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                    {{ __('Add Facility') }}
                </a>
                <a href="{{ route('businesses.show', $business) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-slate-50/80 transition-colors">
                    {{ __('Back to Business') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-nowrap items-center gap-3 mb-6 overflow-x-auto pb-1 rounded-xl border border-slate-200/60 bg-white px-4 py-3 shadow-sm">
                <x-kpi-card inline title="{{ __('Total facilities') }}" :value="$kpis['total']" color="blue" />
                <x-kpi-card inline title="{{ __('Active') }}" :value="$kpis['active']" color="green" />
            </div>
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($facilities->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60 p-8 text-center text-slate-600">
                    <p class="mb-4">{{ __('No facilities for this business yet.') }}</p>
                    <a href="{{ route('businesses.facilities.create', $business) }}" class="inline-flex items-center px-4 py-2 bg-[#3B82F6] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#2563eb]">
                        {{ __('Add first facility') }}
                    </a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($facilities as $facility)
                            <li class="p-4 flex justify-between items-center hover:bg-slate-50/80 transition-colors">
                                <div>
                                    <a href="{{ route('businesses.facilities.show', [$business, $facility]) }}" class="font-medium text-slate-900 hover:text-indigo-600">
                                        {{ $facility->facility_name }}
                                    </a>
                                    <p class="text-sm text-slate-500">
                                        {{ $facility->facility_type }} · {{ $facility->location_display }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        {{ __('License') }}: {{ $facility->license_number ?? '—' }} · {{ ucfirst($facility->status) }}
                                        @if ($facility->isLicenseExpired())
                                            <span class="text-red-600">{{ __('(Expired)') }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('businesses.facilities.show', [$business, $facility]) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('View') }}</a>
                                    <a href="{{ route('businesses.facilities.edit', [$business, $facility]) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                    <form method="post" action="{{ route('businesses.facilities.destroy', [$business, $facility]) }}" class="inline" onsubmit="return confirm('{{ __('Delete this facility?') }}');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $facilities->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
