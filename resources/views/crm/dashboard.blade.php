<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-800 tracking-tight">
            {{ __('CRM') }}
        </h1>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto space-y-6">
            {{-- KPI cards --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Overview') }}</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-kpi-card
                            title="{{ __('Total clients') }}"
                            :value="$totalClients"
                            :href="route('clients.index')"
                            color="blue"
                        />
                        <x-kpi-card
                            title="{{ __('Open demands') }}"
                            :value="$openDemandsCount"
                            :href="route('demands.index')"
                            color="green"
                        />
                        <x-kpi-card
                            title="{{ __('Deliveries this month') }}"
                            :value="$deliveriesThisMonth"
                            :href="route('delivery-confirmations.index')"
                            color="slate"
                        />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Recent clients --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Recent clients') }}</h2>
                        <a href="{{ route('clients.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ __('View all') }}</a>
                    </div>
                    <div class="p-6">
                        @if ($recentClients->isEmpty())
                            <p class="text-sm text-slate-500">{{ __('No clients yet.') }}</p>
                            <a href="{{ route('clients.create') }}" class="inline-flex items-center mt-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Add first client') }}</a>
                        @else
                            <ul class="divide-y divide-slate-100">
                                @foreach ($recentClients as $client)
                                    <li class="py-2 first:pt-0 last:pb-0">
                                        <a href="{{ route('clients.show', $client) }}" class="text-sm font-medium text-slate-900 hover:text-indigo-600">{{ $client->name }}</a>
                                        @if ($client->country)
                                            <span class="text-slate-500 text-sm"> — {{ $client->country }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- Open demands --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Open demands') }}</h2>
                        <a href="{{ route('demands.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ __('View all') }}</a>
                    </div>
                    <div class="p-6">
                        @if ($openDemands->isEmpty())
                            <p class="text-sm text-slate-500">{{ __('No open demands.') }}</p>
                            <a href="{{ route('demands.create') }}" class="inline-flex items-center mt-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Create demand') }}</a>
                        @else
                            <ul class="divide-y divide-slate-100">
                                @foreach ($openDemands as $demand)
                                    <li class="py-2 first:pt-0 last:pb-0">
                                        <a href="{{ route('demands.show', $demand) }}" class="text-sm font-medium text-slate-900 hover:text-indigo-600">{{ $demand->demand_number }}</a>
                                        <span class="text-slate-500 text-sm"> — {{ $demand->title }}</span>
                                        <span class="text-slate-400 text-xs block mt-0.5">{{ $demand->destination_display }} · {{ $demand->requested_delivery_date?->format('d M Y') ?? '—' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- Recipients --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider">{{ __('Recipients') }}</h2>
                        <a href="{{ route('recipients.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ __('View all') }}</a>
                    </div>
                    <div class="p-6">
                        @if ($recipients->isEmpty())
                            <p class="text-sm text-slate-500">{{ __('No recipient facilities yet.') }}</p>
                            <p class="text-sm text-slate-500 mt-1">{{ __('Facilities that receive deliveries will appear here.') }}</p>
                        @else
                            <ul class="divide-y divide-slate-100">
                                @foreach ($recipients as $recipient)
                                    @php
                                        $facility = $recipient->facility;
                                        $lastDate = $recipient->last_delivery_date instanceof \Carbon\Carbon
                                            ? $recipient->last_delivery_date
                                            : \Carbon\Carbon::parse($recipient->last_delivery_date);
                                    @endphp
                                    <li class="py-2 first:pt-0 last:pb-0">
                                        @if ($facility && $facility->business)
                                            <a href="{{ route('businesses.facilities.show', [$facility->business, $facility]) }}" class="text-sm font-medium text-slate-900 hover:text-indigo-600">{{ $facility->facility_name }}</a>
                                        @else
                                            <span class="text-sm font-medium text-slate-900">{{ $facility?->facility_name ?? '—' }}</span>
                                        @endif
                                        <span class="text-slate-400 text-xs block mt-0.5">{{ $lastDate->format('d M Y') }} · {{ $recipient->delivery_count }} {{ __('deliveries') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('clients.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-[#3B82F6] text-white text-sm font-medium hover:bg-[#2563eb]">{{ __('Clients') }}</a>
                <a href="{{ route('demands.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">{{ __('Demand') }}</a>
                <a href="{{ route('recipients.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">{{ __('Recipients') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
