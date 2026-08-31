@php
    use App\Models\Batch;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Batches') }}
            </h2>
            <a href="{{ route('batches.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy shrink-0">
                {{ __('Create batch') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="profile-list-shell">
                @if (session('status'))
                    <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
                @endif

                <form method="get" action="{{ route('batches.hub') }}" class="hub-period-filter">
                    <div class="hub-period-filter__bar">
                        <div class="hub-period-filter__toggles" role="group" aria-label="{{ __('Batch period') }}">
                            @foreach (['all' => __('All'), 'day' => __('Daily'), 'month' => __('Monthly'), 'year' => __('Yearly')] as $periodKey => $periodLabel)
                                <label class="hub-period-filter__toggle">
                                    <input type="radio" name="period" value="{{ $periodKey }}" @checked($filters['period'] === $periodKey)>
                                    <span>{{ $periodLabel }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="hub-period-filter__range">
                            <label for="filter_date_from" class="hub-period-filter__range-label">{{ __('From') }}</label>
                            <input id="filter_date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="hub-period-filter__input" aria-label="{{ __('Date from') }}">
                            <span class="hub-period-filter__sep" aria-hidden="true">–</span>
                            <label for="filter_date_to" class="hub-period-filter__range-label">{{ __('To') }}</label>
                            <input id="filter_date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="hub-period-filter__input" aria-label="{{ __('Date to') }}">
                        </div>

                        <div class="hub-period-filter__actions">
                            <button type="submit" class="hub-period-filter__apply">{{ __('Apply') }}</button>
                            @if ($filters['is_filtered'])
                                <a href="{{ route('batches.hub') }}" class="hub-period-filter__clear">{{ __('Clear') }}</a>
                            @endif
                        </div>
                    </div>
                    <p class="hub-period-filter__hint">{{ $filters['range_label'] }}</p>
                </form>

                <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Batches summary') }}">
                    <x-kpi-card stat compact color="slate" :title="$hubStats['batches_label']" :value="number_format($hubStats['total_batches'])" glyph="box" />
                    <x-kpi-card stat compact color="bucha-success" :title="__('Total quantity')" :value="number_format($hubStats['total_quantity'], 2)" glyph="weight" />
                    <x-kpi-card stat compact color="amber" :title="__('Pending post-mortem')" :value="number_format($hubStats['pending_pm'])" glyph="clipboard" />
                    <x-kpi-card stat compact color="bucha" :title="__('Ready for certificate')" :value="number_format($hubStats['ready_for_cert'])" glyph="certificate" />
                </section>

                @if ($batches->isEmpty())
                    <div class="profile-empty">
                        <p class="mb-4">
                            {{ $filters['is_filtered'] ? __('No batches in this period.') : __('No batches recorded yet.') }}
                        </p>
                        <a href="{{ route('batches.create') }}" class="inline-flex items-center px-4 py-2 bg-bucha-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-bucha-burgundy">
                            {{ __('Create first batch') }}
                        </a>
                    </div>
                @else
                    <div class="profile-cards-grid">
                        @foreach ($batches as $batch)
                            @php
                                $facility = $batch->slaughterExecution?->slaughterPlan?->facility;
                                $statusTone = match ($batch->status) {
                                    Batch::STATUS_APPROVED => 'active',
                                    Batch::STATUS_REJECTED => 'danger',
                                    default => 'warning',
                                };
                                $coldChainTone = match ($batch->cold_chain_status) {
                                    Batch::COLD_CHAIN_OK => 'active',
                                    Batch::COLD_CHAIN_AT_RISK => 'warning',
                                    Batch::COLD_CHAIN_COMPROMISED => 'danger',
                                    default => 'muted',
                                };
                                $initial = strtoupper(substr($batch->batch_code, 0, 1));
                                $quantityLabel = $batch->hasPerAnimalData()
                                    ? number_format($batch->animal_count).' '.__('animals')
                                    : number_format($batch->quantity, 2).' '.$batch->quantity_unit_label;
                            @endphp
                            <x-entity.profile-card>
                                <x-slot:avatar>{{ $initial }}</x-slot:avatar>
                                <x-slot:title>
                                    <a href="{{ route('batches.show', $batch) }}">{{ $batch->batch_code }}</a>
                                </x-slot:title>
                                <x-slot:subtitle>{{ $facility?->facility_name ?? '—' }}</x-slot:subtitle>
                                <x-slot:badge>
                                    <x-entity.status-pill :tone="$statusTone" :label="ucfirst($batch->status)" />
                                </x-slot:badge>

                                <x-entity.profile-row :label="__('Created')">{{ $batch->created_at->format('d M Y') }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Species')">{{ $batch->species }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Quantity')">{{ $quantityLabel }}</x-entity.profile-row>
                                <x-entity.profile-row :label="__('Cold chain')">
                                    <x-entity.status-pill
                                        :tone="$coldChainTone"
                                        :label="ucfirst(str_replace('_', ' ', $batch->cold_chain_status))"
                                    />
                                </x-entity.profile-row>
                                <x-entity.profile-row :label="__('Post-mortem')">
                                    {{ $batch->postMortemInspection ? __('Done') : __('Pending') }}
                                </x-entity.profile-row>
                                <x-entity.profile-row :label="__('Certificate')">
                                    {{ $batch->certificate ? __('Issued') : '—' }}
                                </x-entity.profile-row>
                                <x-entity.profile-row :label="__('Execution')">
                                    @if ($batch->slaughterExecution)
                                        <a href="{{ route('slaughter-executions.show', $batch->slaughterExecution) }}" class="text-xs font-semibold text-bucha-primary hover:text-bucha-burgundy">
                                            {{ __('Plan #:id', ['id' => $batch->slaughterExecution->slaughter_plan_id]) }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </x-entity.profile-row>

                                <x-slot:highlights>
                                    <x-entity.profile-highlight
                                        :value="$batch->hasPerAnimalData() ? number_format($batch->animal_count) : number_format($batch->quantity, 2)"
                                        :label="$batch->hasPerAnimalData() ? __('Animals') : $batch->quantity_unit_label"
                                    />
                                    <x-entity.profile-highlight
                                        :value="ucfirst(str_replace('_', ' ', $batch->cold_chain_status))"
                                        :label="__('Cold chain')"
                                    />
                                </x-slot:highlights>

                                <x-slot:actions>
                                    <x-entity.text-action :href="route('batches.show', $batch)">{{ __('View') }}</x-entity.text-action>
                                    <x-entity.text-action :href="route('batches.edit', $batch)">{{ __('Edit') }}</x-entity.text-action>
                                    @if (! $batch->postMortemInspection)
                                        <x-entity.text-action :href="route('post-mortem-inspections.create', ['batch_id' => $batch->id])">{{ __('Post-mortem') }}</x-entity.text-action>
                                    @elseif ($batch->canIssueCertificate())
                                        <x-entity.text-action :href="route('certificates.create', ['slaughter_execution_id' => $batch->slaughter_execution_id])">{{ __('Issue certificate') }}</x-entity.text-action>
                                    @endif
                                    <x-entity.text-action-delete
                                        :action="route('batches.destroy', $batch)"
                                        :confirm="__('Are you sure you want to delete this batch? This cannot be undone.')"
                                    >{{ __('Delete') }}</x-entity.text-action-delete>
                                </x-slot:actions>
                            </x-entity.profile-card>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $batches->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
