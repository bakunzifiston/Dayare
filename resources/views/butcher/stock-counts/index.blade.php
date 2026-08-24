<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Stock counts') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Physical inventory checks against system stock.') }}</p>
            </div>
            <a href="{{ route('butcher.stock-counts.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Start count') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <section class="overflow-hidden rounded-bucha border border-slate-200/80 bg-white shadow-bucha">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('Count #') }}</th>
                            <th class="px-4 py-3">{{ __('Outlet') }}</th>
                            <th class="px-4 py-3">{{ __('Date') }}</th>
                            <th class="px-4 py-3">{{ __('Lines') }}</th>
                            <th class="px-4 py-3">{{ __('Counted by') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($counts as $count)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium">
                                    <a href="{{ route('butcher.stock-counts.show', $count) }}" class="text-bucha-primary hover:underline">{{ $count->count_number }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $count->outlet?->name ?? __('All outlets') }}</td>
                                <td class="px-4 py-3">{{ $count->count_date?->toDateString() }}</td>
                                <td class="px-4 py-3">{{ $count->lines_count }}</td>
                                <td class="px-4 py-3">{{ $count->countedByUser?->name }}</td>
                                <td class="px-4 py-3"><x-butcher.status-badge :status="$count->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No stock counts yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            <div class="mt-4">{{ $counts->links() }}</div>
        </div>
    </div>
</x-app-layout>
