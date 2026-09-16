@php
    $fieldClass = 'mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-bucha-primary focus:ring-bucha-primary';
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('butcher.compliance.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-bucha-primary hover:text-bucha-burgundy">
                    <i class="ti ti-arrow-left text-base leading-none" aria-hidden="true"></i>
                    {{ __('Compliance') }}
                </a>
                <div class="mt-3 flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                        <i class="ti ti-wash text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Hygiene checklists') }}</h2>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            @foreach ($outlets as $outlet)
                @if (! $todayLogs->has($outlet->id))
                    <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Today\'s checklist') }} — {{ $outlet->name }}</h3>
                        </div>
                        <form method="post" action="{{ route('butcher.compliance.hygiene.store') }}" class="space-y-4 p-4 sm:p-6">
                            @csrf
                            <input type="hidden" name="outlet_id" value="{{ $outlet->id }}">
                            <input type="hidden" name="log_date" value="{{ now()->toDateString() }}">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($checklistKeys as $key => $label)
                                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm text-slate-700">
                                        <input type="checkbox" name="checklist[{{ $key }}]" value="1" class="rounded border-slate-300 text-bucha-primary focus:ring-bucha-primary">
                                        <span>{{ __($label) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Issues found') }}</label>
                                <textarea name="issues_found" rows="2" class="{{ $fieldClass }}"></textarea>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Corrective action') }}</label>
                                <textarea name="corrective_action" rows="2" class="{{ $fieldClass }}"></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                                    <i class="ti ti-check text-base leading-none" aria-hidden="true"></i>
                                    {{ __('Submit checklist') }}
                                </button>
                            </div>
                        </form>
                    </section>
                @else
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                        {{ __(':outlet logged today.', ['outlet' => $outlet->name]) }}
                        <a href="{{ route('butcher.compliance.hygiene.show', $todayLogs[$outlet->id]) }}" class="ml-1 inline-flex items-center gap-1 font-semibold text-emerald-800 hover:underline">
                            {{ __('View') }}
                            <i class="ti ti-arrow-right text-sm leading-none" aria-hidden="true"></i>
                        </a>
                    </div>
                @endif
            @endforeach

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Log history') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Log') }}</th>
                                <th class="hidden sm:table-cell px-4 py-3 sm:px-5">{{ __('Outlet') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Status') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Signed by') }}</th>
                                <th class="px-4 py-3 sm:px-5 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($logs as $log)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-wash text-[1.15rem] leading-none"></i>
                                            </span>
                                            <p class="font-medium text-slate-900">{{ $log->log_date?->toDateString() }}</p>
                                        </div>
                                    </td>
                                    <td class="hidden sm:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $log->outlet?->name }}</td>
                                    <td class="px-4 py-3 sm:px-5"><x-butcher.status-badge :status="$log->status" /></td>
                                    <td class="px-4 py-3 sm:px-5 text-slate-700">{{ $log->signedByUser?->name }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-right">
                                        <a href="{{ route('butcher.compliance.hygiene.show', $log) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            <i class="ti ti-eye text-sm leading-none" aria-hidden="true"></i>
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">{{ __('No hygiene logs yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($logs->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $logs->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
