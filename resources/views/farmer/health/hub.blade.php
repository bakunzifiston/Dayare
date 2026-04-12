<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-bucha-muted">{{ __('Health visit log') }}</span>
    </x-slot>

    <div class="max-w-5xl space-y-4">
        <p class="text-sm text-slate-600">{{ __('Recent visit log entries (history only). Edit healthy/sick counts on each farm’s Farm health page.') }}</p>

        @if ($records->isEmpty())
            <p class="text-sm text-slate-500">{{ __('No health records yet.') }}
                <a href="{{ route('farmer.farms.index') }}" class="text-bucha-primary hover:underline">{{ __('Go to farms') }}</a>
            </p>
        @else
            <div class="bg-white rounded-bucha border border-slate-200/60 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-2">{{ __('Date') }}</th>
                            <th class="px-4 py-2">{{ __('Farm') }}</th>
                            <th class="px-4 py-2">{{ __('Condition') }}</th>
                            <th class="px-4 py-2">{{ __('Notes') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $r)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $r->record_date?->toDateString() }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('farmer.farms.show', $r->farm) }}" class="text-bucha-primary hover:underline">{{ $r->farm?->name }}</a>
                                </td>
                                <td class="px-4 py-2 capitalize">{{ $r->condition }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ \Illuminate\Support\Str::limit($r->notes ?? '', 60) }}</td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                    <a href="{{ route('farmer.farms.health-records.index', $r->farm) }}" class="text-bucha-primary hover:underline">{{ __('Open farm') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $records->links() }}</div>
        @endif
    </div>
</x-app-layout>
