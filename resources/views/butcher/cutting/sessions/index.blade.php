@php
    $fmtKg = static fn ($v): string => number_format((float) $v, 2);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Cutting sessions') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('All open and closed cutting sessions.') }}</p>
            </div>
            <a href="{{ route('butcher.cutting.sessions.create') }}" class="inline-flex items-center rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">{{ __('Open session') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="py-2 pr-4">{{ __('Session') }}</th>
                            <th class="py-2 pr-4">{{ __('Batch') }}</th>
                            <th class="py-2 pr-4">{{ __('Source (kg)') }}</th>
                            <th class="py-2 pr-4">{{ __('Yield (kg)') }}</th>
                            <th class="py-2 pr-4">{{ __('Wastage %') }}</th>
                            <th class="py-2 pr-4">{{ __('Date') }}</th>
                            <th class="py-2">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sessions as $session)
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="py-3 pr-4">
                                    <a href="{{ route('butcher.cutting.sessions.show', $session) }}" class="font-semibold text-bucha-primary hover:underline">{{ $session->session_number }}</a>
                                </td>
                                <td class="py-3 pr-4">{{ $session->batch?->batch_number }}</td>
                                <td class="py-3 pr-4">{{ $fmtKg($session->source_weight_kg) }}</td>
                                <td class="py-3 pr-4">{{ $fmtKg($session->total_cuts_weight_kg) }}</td>
                                <td class="py-3 pr-4">
                                    @if ($session->status === 'closed')
                                        {{ number_format((float) $session->wastage_pct, 1) }}%
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-3 pr-4">{{ $session->session_date?->toDateString() }}</td>
                                <td class="py-3"><x-butcher.status-badge :status="$session->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-slate-500">{{ __('No cutting sessions yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $sessions->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
