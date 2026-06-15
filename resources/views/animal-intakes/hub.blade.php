@php
    use App\Models\AnimalIntake;
    use App\Models\AnimalIntakeItem;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Animal intake') }}
            </h2>
            <a href="{{ route('animal-intakes.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Record intake') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('warning'))
                <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">{{ session('warning') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 px-6 py-8 sm:px-10 sm:py-9 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-bucha-primary">{{ __('Module') }}</p>
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 leading-tight">
                    {{ __('Record animal origin before slaughter') }}
                </h1>
                <p class="mt-3 text-slate-600 leading-relaxed max-w-2xl">
                    {{ __('Each intake session tracks individual animals — ear tags, species, health status, and pricing. Approved intakes can be linked when you schedule slaughter.') }}
                </p>
            </div>

            {{-- Summary KPI bar --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Animals on site') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['heads_available']) }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Available for slaughter planning') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Intakes this month') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($hubStats['intakes_this_month']) }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Submitted sessions') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Cert issues') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['cert_issues'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['cert_issues']) }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Drafts pending') }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $hubStats['draft_count'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                        {{ number_format($hubStats['draft_count']) }}
                    </p>
                </div>
            </div>

            {{-- Filter bar --}}
            <form method="get" action="{{ route('animal-intakes.hub') }}" class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                    <div>
                        <label for="filter_species" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Species') }}</label>
                        <select id="filter_species" name="species" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach (AnimalIntake::SPECIES_OPTIONS as $speciesOption)
                                <option value="{{ $speciesOption }}" @selected($filters['species'] === $speciesOption)>{{ __($speciesOption) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter_health_status" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Health status') }}</label>
                        <select id="filter_health_status" name="health_status" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="{{ AnimalIntakeItem::HEALTH_HEALTHY }}" @selected($filters['health_status'] === AnimalIntakeItem::HEALTH_HEALTHY)>{{ __('Healthy') }}</option>
                            <option value="{{ AnimalIntakeItem::HEALTH_OBSERVATION }}" @selected($filters['health_status'] === AnimalIntakeItem::HEALTH_OBSERVATION)>{{ __('Under observation') }}</option>
                            <option value="{{ AnimalIntakeItem::HEALTH_REJECTED }}" @selected($filters['health_status'] === AnimalIntakeItem::HEALTH_REJECTED)>{{ __('Rejected') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter_draft_status" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Draft status') }}</label>
                        <select id="filter_draft_status" name="draft_status" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="draft" @selected($filters['draft_status'] === 'draft')>{{ __('Draft only') }}</option>
                            <option value="submitted" @selected($filters['draft_status'] === 'submitted')>{{ __('Submitted only') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter_certificate_status" class="block text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Certificate status') }}</label>
                        <select id="filter_certificate_status" name="certificate_status" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-bucha-primary focus:ring-bucha-primary shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="valid" @selected($filters['certificate_status'] === 'valid')>{{ __('Valid') }}</option>
                            <option value="expiring_soon" @selected($filters['certificate_status'] === 'expiring_soon')>{{ __('Expiring soon (30 days)') }}</option>
                            <option value="expired" @selected($filters['certificate_status'] === 'expired')>{{ __('Expired') }}</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Apply') }}
                        </button>
                        @if (array_filter($filters))
                            <a href="{{ route('animal-intakes.hub') }}" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Intake sessions table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/60">
                @if ($intakes->isEmpty())
                    <div class="p-8 text-center text-slate-600">
                        <p class="mb-4">{{ __('No intakes match your filters.') }}</p>
                        <a href="{{ route('animal-intakes.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">{{ __('Record first intake') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Reference') }}</th>
                                    <th class="px-4 py-3">{{ __('Date & time') }}</th>
                                    <th class="px-4 py-3">{{ __('Facility') }}</th>
                                    <th class="px-4 py-3">{{ __('Source') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Animals') }}</th>
                                    <th class="px-4 py-3">{{ __('Species mix') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Total value') }}</th>
                                    <th class="px-4 py-3">{{ __('Health') }}</th>
                                    <th class="px-4 py-3">{{ __('Cert expiry') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($intakes as $intake)
                                    @php
                                        $health = $intake->health_summary;
                                        $expiry = $intake->health_certificate_expiry_date;
                                        $sourceName = $intake->source_type === AnimalIntake::SOURCE_TYPE_CLIENT
                                            ? ($intake->client?->name ?? $intake->clientSourceDisplayName())
                                            : (trim(($intake->supplier?->first_name ?? '').' '.($intake->supplier?->last_name ?? ''))
                                                ?: trim(($intake->supplier_firstname ?? '').' '.($intake->supplier_lastname ?? ''))
                                                ?: '—');
                                    @endphp
                                    <tr class="intake-row cursor-pointer hover:bg-slate-50/80 transition-colors" data-intake-id="{{ $intake->id }}">
                                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $intake->reference ?? '—' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-slate-800">{{ $intake->intakeDatetimeLabel() }}</td>
                                        <td class="px-4 py-3 text-slate-800">{{ $intake->facility->facility_name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $sourceName }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums font-medium text-slate-900">{{ number_format($intake->number_of_animals) }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $intake->species_mix_label ?: '—' }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums text-slate-800">RWF {{ number_format($intake->total_price, 0) }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                @if ($health['healthy'] > 0)
                                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">{{ $health['healthy'] }}</span>
                                                @endif
                                                @if ($health['under_observation'] > 0)
                                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ $health['under_observation'] }}</span>
                                                @endif
                                                @if ($health['rejected'] > 0)
                                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">{{ $health['rejected'] }}</span>
                                                @endif
                                                @if ($health['healthy'] === 0 && $health['under_observation'] === 0 && $health['rejected'] === 0)
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($expiry)
                                                @if ($expiry->isPast())
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ __('Expired') }}</span>
                                                        <span class="text-slate-600">{{ $expiry->format('d M Y') }}</span>
                                                    </span>
                                                @elseif ($expiry->lte(today()->addDays(30)))
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ __('Expiring soon') }}</span>
                                                        <span class="text-slate-600">{{ $expiry->format('d M Y') }}</span>
                                                    </span>
                                                @else
                                                    <span class="text-slate-800">{{ $expiry->format('d M Y') }}</span>
                                                @endif
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex flex-wrap items-center gap-1">
                                                @if ($intake->isDraft())
                                                    <span class="inline-flex items-center rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ __('Draft') }}</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold
                                                        @if ($intake->status === AnimalIntake::STATUS_APPROVED) bg-emerald-100 text-emerald-800
                                                        @elseif ($intake->status === AnimalIntake::STATUS_REJECTED) bg-red-100 text-red-800
                                                        @else bg-slate-100 text-slate-700 @endif">
                                                        {{ ucfirst($intake->status) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right intake-row-actions">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('animal-intakes.edit', $intake) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('Edit') }}</a>
                                                <a href="{{ route('animal-intakes.show', $intake) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">{{ __('View') }}</a>
                                                <form method="POST" action="{{ route('animal-intakes.destroy', $intake) }}" class="inline" onsubmit="return confirm(@js(__('Are you sure you want to delete this animal intake? This cannot be undone.')));">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="intake-detail-row hidden bg-slate-50/60" id="detail-{{ $intake->id }}">
                                        <td colspan="11" class="px-4 py-4">
                                            @if ($intake->items->isEmpty())
                                                <p class="text-sm text-slate-500 italic">
                                                    {{ __('This record predates individual animal tracking. Run php artisan intake:backfill to generate item records.') }}
                                                </p>
                                            @else
                                                <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                                                    <table class="min-w-full text-sm">
                                                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                            <tr>
                                                                <th class="px-3 py-2">#</th>
                                                                <th class="px-3 py-2">{{ __('Ear tag') }}</th>
                                                                <th class="px-3 py-2">{{ __('Species') }}</th>
                                                                <th class="px-3 py-2">{{ __('Sex') }}</th>
                                                                <th class="px-3 py-2">{{ __('Age') }}</th>
                                                                <th class="px-3 py-2">{{ __('Weight') }}</th>
                                                                <th class="px-3 py-2">{{ __('Body condition') }}</th>
                                                                <th class="px-3 py-2 text-right">{{ __('Unit price') }}</th>
                                                                <th class="px-3 py-2">{{ __('Health') }}</th>
                                                                <th class="px-3 py-2">{{ __('Assigned plan') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-slate-100">
                                                            @foreach ($intake->items as $index => $item)
                                                                <tr>
                                                                    <td class="px-3 py-2 text-slate-500">{{ $index + 1 }}</td>
                                                                    <td class="px-3 py-2">
                                                                        <span class="font-mono text-xs text-slate-800">{{ $item->ear_tag }}</span>
                                                                        @if (str_starts_with($item->ear_tag, 'LEGACY-'))
                                                                            <span class="ml-1 inline-flex items-center rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-600">{{ __('legacy') }}</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="px-3 py-2">{{ __($item->species) }}</td>
                                                                    <td class="px-3 py-2">{{ ucfirst($item->sex) }}</td>
                                                                    <td class="px-3 py-2">{{ $item->age_months !== null ? $item->age_months.' '.__('months') : '—' }}</td>
                                                                    <td class="px-3 py-2">{{ $item->live_weight_kg !== null ? number_format((float) $item->live_weight_kg, 1).' kg' : '—' }}</td>
                                                                    <td class="px-3 py-2">{{ $item->body_condition_label ?? '—' }}</td>
                                                                    <td class="px-3 py-2 text-right tabular-nums">RWF {{ number_format((float) $item->unit_price, 0) }}</td>
                                                                    <td class="px-3 py-2">
                                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold
                                                                            @if ($item->health_status === AnimalIntakeItem::HEALTH_HEALTHY) bg-green-100 text-green-800
                                                                            @elseif ($item->health_status === AnimalIntakeItem::HEALTH_OBSERVATION) bg-amber-100 text-amber-800
                                                                            @else bg-red-100 text-red-800 @endif">
                                                                            {{ $item->health_status_label }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="px-3 py-2">
                                                                        @if ($item->slaughter_plan_id && $item->slaughterPlan)
                                                                            <a href="{{ route('slaughter-plans.show', $item->slaughterPlan) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                                                                {{ __('Plan') }} #{{ $item->slaughter_plan_id }}
                                                                            </a>
                                                                        @else
                                                                            —
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-100">{{ $intakes->links() }}</div>
                @endif
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('animal-intakes.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('All intakes (paginated list)') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Simple list view for quick scanning.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open list') }} →</span>
                </a>
                <a href="{{ route('slaughter-plans.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Slaughter planning') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Schedule sessions from approved intakes when rules allow.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Planning home') }} →</span>
                </a>
                <a href="{{ route('suppliers.index') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Suppliers') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('Link intakes to approved suppliers and active contracts.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Open suppliers') }} →</span>
                </a>
                <a href="{{ route('slaughter-executions.hub') }}" class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-bucha-primary/30 hover:shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-bucha-primary">{{ __('Slaughter execution') }}</h2>
                    <p class="mt-2 flex-1 text-sm text-slate-600">{{ __('After planning and ante-mortem, record the actual run.') }}</p>
                    <span class="mt-5 text-sm font-semibold text-bucha-primary">{{ __('Execution home') }} →</span>
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.intake-row').forEach(function (row) {
                row.addEventListener('click', function (event) {
                    if (event.target.closest('.intake-row-actions')) {
                        return;
                    }
                    var intakeId = row.getAttribute('data-intake-id');
                    var detailRow = document.getElementById('detail-' + intakeId);
                    if (!detailRow) {
                        return;
                    }
                    detailRow.classList.toggle('hidden');
                    row.classList.toggle('bg-slate-50');
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
