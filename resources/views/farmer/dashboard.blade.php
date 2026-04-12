<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Farmer dashboard') }}</span>
    </x-slot>

    <div class="space-y-8 max-w-[1600px]">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                {{ __('Welcome, :name', ['name' => $user->name]) }}
            </h1>
            <p class="mt-1 text-sm text-bucha-muted">{{ __('Livestock, health, and processor supply requests.') }}</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Total livestock') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($totalLivestock) }}</p>
            </div>
            <div class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Available stock') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($availableLivestock) }}</p>
            </div>
            <div class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Supplied (intakes)') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($suppliedAnimals) }}</p>
            </div>
            <div class="rounded-bucha border border-slate-200/80 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Pending requests') }}</p>
                <p class="mt-1 text-2xl font-bold text-amber-700">{{ number_format($pendingRequests) }}</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <section class="space-y-3">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Livestock by farm') }}</h2>
                <div class="rounded-bucha border border-slate-200/60 bg-white divide-y divide-slate-100">
                    @forelse ($farmsWithLivestock as $farm)
                        <div class="px-4 py-3 flex justify-between gap-4 text-sm">
                            <div>
                                <a href="{{ route('farmer.farms.show', $farm) }}" class="font-medium text-bucha-primary hover:underline">{{ $farm->name }}</a>
                                <p class="text-xs text-slate-500">{{ __('Head counts are totals / available.') }}</p>
                            </div>
                            <div class="text-right tabular-nums">
                                @php
                                    $tot = $farm->livestock->sum('total_quantity');
                                    $av = $farm->livestock->sum('available_quantity');
                                @endphp
                                {{ number_format($tot) }} / {{ number_format($av) }}
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-sm text-slate-500">{{ __('No farms yet.') }} <a href="{{ route('farmer.farms.create') }}" class="text-bucha-primary hover:underline">{{ __('Add a farm') }}</a></p>
                    @endforelse
                </div>
            </section>

            <section class="space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Incoming supply requests') }}</h2>
                    <a href="{{ route('farmer.supply-requests.index') }}" class="text-xs text-bucha-primary hover:underline">{{ __('All') }}</a>
                </div>
                <div class="rounded-bucha border border-slate-200/60 bg-white divide-y divide-slate-100">
                    @forelse ($incomingRequests as $req)
                        <div class="px-4 py-3 text-sm flex justify-between gap-4">
                            <div>
                                <p class="font-medium text-slate-900">{{ $req->processor?->business_name }}</p>
                                <p class="text-xs text-slate-500">{{ ucfirst($req->animal_type) }} × {{ $req->quantity_requested }}</p>
                            </div>
                            <a href="{{ route('farmer.supply-requests.show', $req) }}" class="shrink-0 text-bucha-primary hover:underline">{{ __('Review') }}</a>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-sm text-slate-500">{{ __('No pending requests.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <section class="space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Supply history (recent)') }}</h2>
                    <a href="{{ route('farmer.supply-history') }}" class="text-xs text-bucha-primary hover:underline">{{ __('Full history') }}</a>
                </div>
                <div class="rounded-bucha border border-slate-200/60 bg-white divide-y divide-slate-100 text-sm">
                    @forelse ($historyPreview as $row)
                        <div class="px-4 py-2 flex justify-between gap-4">
                            <span class="text-slate-600">{{ $row['date'] }}</span>
                            <span class="text-slate-900 truncate">{{ $row['facility'] }}</span>
                            <span class="tabular-nums">{{ $row['quantity'] }}</span>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-slate-500">{{ __('No supply activity yet.') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="space-y-3">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Recent health records') }}</h2>
                <div class="rounded-bucha border border-slate-200/60 bg-white divide-y divide-slate-100 text-sm">
                    @forelse ($recentHealth as $h)
                        <div class="px-4 py-2 flex justify-between gap-4">
                            <span>{{ $h->record_date?->toDateString() }}</span>
                            <span class="capitalize">{{ $h->condition }}</span>
                            <span class="text-slate-500 truncate">{{ $h->farm?->name }}</span>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-slate-500">{{ __('No health records yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('farmer.farms.index') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-sm font-semibold rounded-bucha">{{ __('Manage farms') }}</a>
            <a href="{{ route('farmer.supply-requests.index') }}" class="inline-flex items-center px-4 py-2 border border-slate-300 rounded-bucha text-sm">{{ __('Supply requests') }}</a>
        </div>
    </div>
</x-app-layout>
