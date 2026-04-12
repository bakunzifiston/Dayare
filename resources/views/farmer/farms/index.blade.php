<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <span class="text-sm font-medium text-bucha-muted">{{ __('Farms') }}</span>
            <a href="{{ route('farmer.farms.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary text-white text-xs font-semibold uppercase tracking-widest rounded-bucha hover:bg-bucha-burgundy">
                {{ __('Add farm') }}
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        @if (session('status'))
            <div class="rounded-bucha border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        @if ($farms->isEmpty())
            <p class="text-sm text-slate-600">{{ __('No farms yet. Create your first farm to register livestock.') }}</p>
        @else
            <div class="bg-white shadow-sm rounded-bucha border border-slate-200/60 overflow-hidden">
                <ul class="divide-y divide-slate-100">
                    @foreach ($farms as $farm)
                        <li class="px-4 py-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <a href="{{ route('farmer.farms.show', $farm) }}" class="font-semibold text-slate-900 hover:text-bucha-primary">{{ $farm->name }}</a>
                                <p class="text-xs text-slate-500">{{ $farm->business?->business_name }}</p>
                            </div>
                            <div class="flex gap-2 text-sm">
                                <a href="{{ route('farmer.farms.livestock.index', $farm) }}" class="text-bucha-primary hover:underline">{{ __('Livestock') }}</a>
                                <a href="{{ route('farmer.farms.health-records.index', $farm) }}" class="text-slate-600 hover:underline">{{ __('Health') }}</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="px-1">{{ $farms->links() }}</div>
        @endif
    </div>
</x-app-layout>
