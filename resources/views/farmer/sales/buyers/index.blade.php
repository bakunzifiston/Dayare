<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-4 w-full"><h2 class="font-semibold text-xl text-slate-800">{{ __('Buyers & clients') }}</h2><a href="{{ route('farmer.sales.buyers.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white">{{ __('Add buyer') }}</a></div></x-slot>
    <div class="max-w-7xl space-y-6">
        @include('farmer.sales.partials.nav')
        <form method="GET" class="grid gap-3 rounded-bucha border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search buyers...') }}" class="rounded-lg border-gray-300 text-sm md:col-span-2" />
            <select name="buyer_type" class="rounded-lg border-gray-300 text-sm"><option value="">{{ __('All types') }}</option>@foreach (\App\Models\Buyer::TYPES as $type)<option value="{{ $type }}" @selected(request('buyer_type') === $type)>{{ __(ucwords(str_replace('_', ' ', $type))) }}</option>@endforeach</select>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">{{ __('Filter') }}</button>
        </form>
        @if ($records->isEmpty())<p class="text-sm text-slate-500">{{ __('No buyers yet.') }}</p>@else
        <div class="overflow-hidden rounded-bucha border border-slate-200 bg-white shadow-sm"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-slate-600"><tr><th class="px-4 py-3">{{ __('Code') }}</th><th class="px-4 py-3">{{ __('Buyer') }}</th><th class="px-4 py-3">{{ __('Type') }}</th><th class="px-4 py-3">{{ __('Trust') }}</th><th class="px-4 py-3">{{ __('Status') }}</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($records as $record)<tr><td class="px-4 py-3 font-mono text-xs">{{ $record->buyer_code }}</td><td class="px-4 py-3">{{ $record->buyer_name }}</td><td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $record->buyer_type) }}</td><td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $record->trust_level) }}</td><td class="px-4 py-3"><x-sale-status-badge :status="$record->status" /></td><td class="px-4 py-3 text-right"><a href="{{ route('farmer.sales.buyers.show', $record) }}" class="text-bucha-primary hover:underline">{{ __('View') }}</a></td></tr>@endforeach</tbody></table></div><div>{{ $records->links() }}</div>@endif
    </div>
</x-app-layout>
