<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Daily hygiene logs') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('One checklist per outlet per day.') }}</p>
            </div>
            <a href="{{ route('butcher.compliance.index') }}" class="text-sm font-semibold text-bucha-primary hover:underline">{{ __('Compliance dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            @foreach ($outlets as $outlet)
                @if (! $todayLogs->has($outlet->id))
                    <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Today\'s checklist') }} — {{ $outlet->name }}</h3>
                        <form method="post" action="{{ route('butcher.compliance.hygiene.store') }}" class="mt-4 space-y-4">
                            @csrf
                            <input type="hidden" name="outlet_id" value="{{ $outlet->id }}">
                            <input type="hidden" name="log_date" value="{{ now()->toDateString() }}">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($checklistKeys as $key => $label)
                                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                        <input type="checkbox" name="checklist[{{ $key }}]" value="1" class="rounded border-gray-300 text-bucha-primary">
                                        <span>{{ __($label) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Issues found') }}</label>
                                <textarea name="issues_found" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm"></textarea>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase text-slate-500">{{ __('Corrective action') }}</label>
                                <textarea name="corrective_action" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm"></textarea>
                            </div>
                            <button type="submit" class="rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white">{{ __('Submit checklist') }}</button>
                        </form>
                    </section>
                @else
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                        {{ __(':outlet logged today.', ['outlet' => $outlet->name]) }}
                        <a href="{{ route('butcher.compliance.hygiene.show', $todayLogs[$outlet->id]) }}" class="ml-1 font-semibold underline">{{ __('View') }}</a>
                    </div>
                @endif
            @endforeach

            <section class="rounded-bucha border border-slate-200/80 bg-white p-5 shadow-bucha overflow-x-auto">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Log history') }}</h3>
                <table class="mt-4 min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <th class="py-2 pr-4">{{ __('Date') }}</th>
                            <th class="py-2 pr-4">{{ __('Outlet') }}</th>
                            <th class="py-2 pr-4">{{ __('Status') }}</th>
                            <th class="py-2">{{ __('Signed by') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="py-3 pr-4">
                                    <a href="{{ route('butcher.compliance.hygiene.show', $log) }}" class="font-semibold text-bucha-primary hover:underline">{{ $log->log_date?->toDateString() }}</a>
                                </td>
                                <td class="py-3 pr-4">{{ $log->outlet?->name }}</td>
                                <td class="py-3 pr-4"><x-butcher.status-badge :status="$log->status" /></td>
                                <td class="py-3">{{ $log->signedByUser?->name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-slate-500">{{ __('No hygiene logs yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $logs->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
