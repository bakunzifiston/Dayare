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
                        <i class="ti ti-spray text-lg leading-none"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Sanitation') }}</h2>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Log sanitation') }}</h3>
                </div>
                <form method="post" action="{{ route('butcher.compliance.sanitation.store') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-6 md:grid-cols-3 lg:grid-cols-4">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Outlet') }}</label>
                        <select name="outlet_id" required class="{{ $fieldClass }}">
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Equipment') }}</label>
                        <input name="equipment_name" required placeholder="Band saw" class="{{ $fieldClass }}">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Cleaning type') }}</label>
                        <select name="cleaning_type" required class="{{ $fieldClass }}">
                            @foreach ($cleaningTypes as $type)
                                <option value="{{ $type }}">{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Chemical used') }}</label>
                        <input name="chemical_used" class="{{ $fieldClass }}">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Performed at') }}</label>
                        <input type="datetime-local" name="performed_at" value="{{ now()->format('Y-m-d\TH:i') }}" class="{{ $fieldClass }}">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Next due') }}</label>
                        <input type="datetime-local" name="next_due_at" class="{{ $fieldClass }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Notes') }}</label>
                        <input name="notes" class="{{ $fieldClass }}">
                    </div>
                    <div class="flex items-end md:col-span-3 lg:col-span-1">
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-bucha bg-bucha-primary px-4 py-2 text-sm font-semibold text-white hover:bg-bucha-burgundy">
                            <i class="ti ti-device-floppy text-base leading-none" aria-hidden="true"></i>
                            {{ __('Save record') }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Sanitation history') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 sm:px-5">{{ __('Equipment') }}</th>
                                <th class="hidden md:table-cell px-4 py-3 sm:px-5">{{ __('Outlet') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Type') }}</th>
                                <th class="px-4 py-3 sm:px-5">{{ __('Next due') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($records as $record)
                                <tr @class(['hover:bg-slate-50/80', 'bg-red-50/60' => $record->isOverdue()])>
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-bucha-burgundy ring-1 ring-inset ring-red-100" aria-hidden="true">
                                                <i class="ti ti-spray text-[1.15rem] leading-none"></i>
                                            </span>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $record->equipment_name }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $record->performed_at?->format('M j, H:i') }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden md:table-cell px-4 py-3 sm:px-5 text-slate-700">{{ $record->outlet?->name }}</td>
                                    <td class="px-4 py-3 sm:px-5 text-slate-700">{{ str_replace('_', ' ', $record->cleaning_type) }}</td>
                                    <td @class(['px-4 py-3 sm:px-5 tabular-nums', 'font-semibold text-red-700' => $record->isOverdue(), 'text-slate-700' => ! $record->isOverdue()])>
                                        {{ $record->next_due_at?->format('M j, Y H:i') ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">{{ __('No sanitation records yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($records->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">{{ $records->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
