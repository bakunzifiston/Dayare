<?php

namespace App\Services\SuperAdmin;

use App\Models\AnimalIntakeItem;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\CertificateQr;
use App\Models\DeliveryConfirmation;
use App\Models\SlaughterExecution;
use App\Models\TransportTrip;
use App\Support\TenantEnvironmentScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RicaTraceabilityDashboardService
{
    public function __construct(
        private readonly SuperAdminSlaughterDashboardService $slaughterDashboard,
        private readonly RicaSupplyChainDestinationResolver $destinationResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $filters = $this->slaughterDashboard->resolveHubFilters($request);
        $searchQuery = trim((string) $request->query('q', ''));
        $searchType = $this->normalizeSearchType($request->query('search_type'));
        $selectedBatchId = $this->normalizeBatchId($request->query('batch_id'));

        $batches = $this->batchRecords($filters);
        $evaluations = $batches->map(fn (Batch $batch) => $this->evaluateBatch($batch));

        $searchResults = $searchQuery !== ''
            ? $this->searchBatches($searchQuery, $searchType, $filters)
            : collect();

        if ($selectedBatchId === null && $searchResults->isNotEmpty()) {
            $selectedBatchId = (int) $searchResults->first()['batch_id'];
        }

        $featuredBatch = $selectedBatchId !== null
            ? $batches->firstWhere('id', $selectedBatchId) ?? $this->findBatchById($selectedBatchId)
            : $evaluations->sortByDesc('completion_percent')->first()['batch'] ?? null;

        $featuredEvaluation = $featuredBatch
            ? ($evaluations->firstWhere('batch_id', $featuredBatch->id) ?? $this->evaluateBatch($featuredBatch))
            : null;

        return [
            'filters' => $filters,
            'searchQuery' => $searchQuery,
            'searchType' => $searchType,
            'searchTypes' => $this->searchTypes(),
            'searchResults' => $searchResults->take(8)->values()->all(),
            'journeySteps' => $this->journeyStepDefinitions(),
            'journeyProgress' => $featuredEvaluation['steps'] ?? $this->emptyJourneyProgress(),
            'timeline' => $featuredEvaluation['timeline'] ?? [],
            'featuredBatch' => $featuredEvaluation,
            'alerts' => $this->liveAlerts($evaluations),
        ];
    }

    /**
     * @return Collection<int, Batch>
     */
    private function batchRecords(array $filters): Collection
    {
        $query = TenantEnvironmentScope::applyToBatches(
            Batch::query()
                ->with([
                    'slaughterExecution.slaughterPlan.animalIntake.district',
                    'slaughterExecution.slaughterPlan.animalIntake.province',
                    'slaughterExecution.slaughterPlan.facility',
                    'slaughterExecution.slaughterPlan.anteMortemInspections',
                    'postMortemInspection',
                    'certificates.certificateQr',
                    'certificates.transportTrips.originFacility',
                    'certificates.transportTrips.deliveryConfirmation.client.districtDivision',
                    'certificates.transportTrips.deliveryConfirmation.receivingFacility.districtDivision',
                    'certificates.transportTrips.destinationFacility.districtDivision',
                    'items.intakeItem',
                ])
        );

        $this->applyBatchDateScope($query, $filters);

        return $query->latest('id')->limit(500)->get();
    }

    private function findBatchById(int $batchId): ?Batch
    {
        return TenantEnvironmentScope::applyToBatches(
            Batch::query()
                ->with([
                    'slaughterExecution.slaughterPlan.animalIntake.district',
                    'slaughterExecution.slaughterPlan.animalIntake.province',
                    'slaughterExecution.slaughterPlan.facility',
                    'slaughterExecution.slaughterPlan.anteMortemInspections',
                    'postMortemInspection',
                    'certificates.certificateQr',
                    'certificates.transportTrips.originFacility',
                    'certificates.transportTrips.deliveryConfirmation.client.districtDivision',
                    'certificates.transportTrips.deliveryConfirmation.receivingFacility.districtDivision',
                    'certificates.transportTrips.destinationFacility.districtDivision',
                    'items.intakeItem',
                ])
                ->whereKey($batchId)
        )->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluateBatch(Batch $batch): array
    {
        $execution = $batch->slaughterExecution;
        $plan = $execution?->slaughterPlan;
        $intake = $plan?->animalIntake;
        $postMortem = $batch->postMortemInspection;
        $certificate = $batch->certificates->sortByDesc('issued_at')->first();
        $trip = $certificate?->transportTrips->sortByDesc('departure_date')->first();
        $delivery = $trip?->deliveryConfirmation;
        $destination = $trip ? $this->destinationResolver->resolveForTrip($trip) : null;

        $steps = [
            'origin' => $this->hasOrigin($intake),
            'transport' => $this->hasTransport($intake),
            'arrival' => $intake !== null && (int) $intake->facility_id > 0,
            'ante_mortem' => $plan && $plan->anteMortemInspections->isNotEmpty(),
            'slaughter' => $execution && $execution->status === SlaughterExecution::STATUS_COMPLETED,
            'post_mortem' => $postMortem !== null,
            'certification' => $certificate && $certificate->status === Certificate::STATUS_ACTIVE,
            'distribution' => $trip !== null,
            'destination' => $delivery && $delivery->confirmation_status === DeliveryConfirmation::STATUS_CONFIRMED,
        ];

        $completed = collect($steps)->filter()->count();
        $completionPercent = round($completed / count($steps) * 100, 1);
        $status = match (true) {
            $completed >= 8 => 'fully',
            $completed >= 5 => 'partial',
            default => 'not',
        };

        return [
            'batch' => $batch,
            'batch_id' => (int) $batch->id,
            'batch_code' => (string) $batch->batch_code,
            'certificate_number' => $certificate?->certificate_number,
            'completion_percent' => $completionPercent,
            'status' => $status,
            'steps' => $steps,
            'timeline' => $this->buildTimeline($batch, $intake, $plan, $execution, $postMortem, $certificate, $trip, $delivery, $destination),
            'origin_district_id' => $intake?->district_id ? (int) $intake->district_id : null,
            'origin_district_name' => $intake?->district?->name,
            'destination_district_id' => $destination['district_id'] ?? null,
            'destination_district_name' => $destination['district'] ?? null,
        ];
    }

    /**
     * @return list<array{key: string, title: string, detail: string, at: ?string, completed: bool}>
     */
    private function buildTimeline(
        Batch $batch,
        $intake,
        $plan,
        ?SlaughterExecution $execution,
        $postMortem,
        ?Certificate $certificate,
        ?TransportTrip $trip,
        $delivery,
        ?array $destination,
    ): array {
        $definitions = $this->journeyStepDefinitions();

        $timestamps = [
            'origin' => $intake?->intake_date,
            'transport' => $intake?->intake_date,
            'arrival' => $intake?->intake_date,
            'ante_mortem' => $plan?->anteMortemInspections->sortBy('inspection_date')->first()?->inspection_date,
            'slaughter' => $execution?->slaughter_time,
            'post_mortem' => $postMortem?->inspection_date,
            'certification' => $certificate?->issued_at,
            'distribution' => $trip?->departure_date,
            'destination' => $delivery?->received_date,
        ];

        $details = [
            'origin' => $this->originLabel($intake),
            'transport' => $intake?->movement_permit_no
                ? __('Permit :number', ['number' => $intake->movement_permit_no])
                : __('Transport recorded'),
            'arrival' => $intake?->facility?->facility_name ?? $plan?->facility?->facility_name ?? __('Slaughterhouse arrival'),
            'ante_mortem' => __('Ante-mortem inspection completed'),
            'slaughter' => $plan?->facility?->facility_name
                ? __('Slaughter at :facility', ['facility' => $plan->facility->facility_name])
                : __('Slaughter completed'),
            'post_mortem' => __('Post-mortem inspection completed'),
            'certification' => $certificate?->certificate_number
                ? __('Certificate :number', ['number' => $certificate->certificate_number])
                : __('Certificate pending'),
            'distribution' => $trip?->originFacility?->facility_name
                ? __('Dispatch from :facility', ['facility' => $trip->originFacility->facility_name])
                : __('Distribution in progress'),
            'destination' => $destination['label'] ?? __('Destination pending'),
        ];

        $steps = [
            'origin' => $this->hasOrigin($intake),
            'transport' => $this->hasTransport($intake),
            'arrival' => $intake !== null && (int) $intake->facility_id > 0,
            'ante_mortem' => $plan && $plan->anteMortemInspections->isNotEmpty(),
            'slaughter' => $execution && $execution->status === SlaughterExecution::STATUS_COMPLETED,
            'post_mortem' => $postMortem !== null,
            'certification' => $certificate && $certificate->status === Certificate::STATUS_ACTIVE,
            'distribution' => $trip !== null,
            'destination' => $delivery && $delivery->confirmation_status === DeliveryConfirmation::STATUS_CONFIRMED,
        ];

        return collect($definitions)->map(function (array $definition) use ($steps, $timestamps, $details): array {
            $key = $definition['key'];
            $at = $timestamps[$key] ?? null;

            return [
                'key' => $key,
                'title' => $definition['title'],
                'detail' => $details[$key] ?? '—',
                'at' => $at ? Carbon::parse($at)->format('M j, Y, h:i A') : null,
                'completed' => (bool) ($steps[$key] ?? false),
            ];
        })->all();
    }

    private function hasOrigin(?object $intake): bool
    {
        if ($intake === null) {
            return false;
        }

        return trim((string) ($intake->farm_name ?? '')) !== ''
            || trim((string) ($intake->supplier_firstname ?? '').' '.(string) ($intake->supplier_lastname ?? '')) !== ''
            || $intake->district_id !== null
            || $intake->farm_id !== null;
    }

    private function hasTransport(?object $intake): bool
    {
        if ($intake === null) {
            return false;
        }

        return trim((string) ($intake->movement_permit_no ?? '')) !== ''
            || $intake->supplier_id !== null
            || $intake->client_id !== null;
    }

    private function originLabel(?object $intake): string
    {
        if ($intake === null) {
            return __('Origin not recorded');
        }

        if (trim((string) ($intake->farm_name ?? '')) !== '') {
            $location = $intake->district?->name;

            return $location
                ? __(':farm, :district', ['farm' => $intake->farm_name, 'district' => $location])
                : (string) $intake->farm_name;
        }

        $supplier = trim((string) ($intake->supplier_firstname ?? '').' '.(string) ($intake->supplier_lastname ?? ''));

        return $supplier !== '' ? $supplier : __('Origin not recorded');
    }

    /**
     * @return list<array{key: string, title: string, glyph: string}>
     */
    private function journeyStepDefinitions(): array
    {
        return [
            ['key' => 'origin', 'title' => __('Farm / Origin'), 'glyph' => 'building'],
            ['key' => 'transport', 'title' => __('Transport'), 'glyph' => 'truck'],
            ['key' => 'arrival', 'title' => __('Arrival at slaughterhouse'), 'glyph' => 'building'],
            ['key' => 'ante_mortem', 'title' => __('Ante-mortem'), 'glyph' => 'shield'],
            ['key' => 'slaughter', 'title' => __('Slaughter'), 'glyph' => 'weight'],
            ['key' => 'post_mortem', 'title' => __('Post-mortem'), 'glyph' => 'clipboard-list'],
            ['key' => 'certification', 'title' => __('Certification'), 'glyph' => 'certificate'],
            ['key' => 'distribution', 'title' => __('Distribution'), 'glyph' => 'truck'],
            ['key' => 'destination', 'title' => __('Destination'), 'glyph' => 'tag'],
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function emptyJourneyProgress(): array
    {
        return collect($this->journeyStepDefinitions())
            ->mapWithKeys(fn (array $step) => [$step['key'] => false])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function searchTypes(): array
    {
        return [
            'animal_id' => __('Animal ID'),
            'ear_tag' => __('Ear tag'),
            'batch_id' => __('Batch ID'),
            'certificate_no' => __('Certificate no'),
            'qr_code' => __('QR code'),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchBatches(string $query, string $searchType, array $filters): Collection
    {
        $like = '%'.$query.'%';

        $batchQuery = TenantEnvironmentScope::applyToBatches(
            Batch::query()
                ->with([
                    'slaughterExecution.slaughterPlan.animalIntake',
                    'certificates.certificateQr',
                    'items.intakeItem',
                ])
        );

        $batchQuery->where(function (Builder $scoped) use ($searchType, $query, $like): void {
            match ($searchType) {
                'animal_id' => $scoped
                    ->whereKey((int) $query)
                    ->orWhereHas('items.intakeItem', fn (Builder $itemQuery) => $itemQuery->whereKey((int) $query)),
                'ear_tag' => $scoped->whereHas(
                    'items.intakeItem',
                    fn (Builder $itemQuery) => $itemQuery->where('ear_tag', 'like', $like),
                ),
                'certificate_no' => $scoped->whereHas(
                    'certificates',
                    fn (Builder $certQuery) => $certQuery->where('certificate_number', 'like', $like),
                ),
                'qr_code' => $scoped->whereHas(
                    'certificates.certificateQr',
                    fn (Builder $qrQuery) => $qrQuery->where('slug', 'like', $like),
                ),
                default => $scoped
                    ->where('batch_code', 'like', $like)
                    ->orWhereKey((int) $query),
            };
        });

        return $batchQuery
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (Batch $batch) => [
                'batch_id' => (int) $batch->id,
                'batch_code' => (string) $batch->batch_code,
                'certificate_number' => $batch->certificates->first()?->certificate_number,
                'ear_tags' => $batch->items->pluck('intakeItem.ear_tag')->filter()->take(3)->values()->all(),
                'completion_percent' => $this->evaluateBatch($batch)['completion_percent'],
            ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $evaluations
     * @return list<array{title: string, message: string, severity: string, ago: string}>
     */
    private function liveAlerts(Collection $evaluations): array
    {
        $alerts = [];

        foreach ($evaluations->take(200) as $evaluation) {
            $batchCode = $evaluation['batch_code'];
            $steps = $evaluation['steps'];

            if (! $steps['origin']) {
                $alerts[] = $this->alert(
                    __('Missing origin'),
                    __('Batch :batch — animal origin not recorded', ['batch' => $batchCode]),
                    'warning',
                );
            }

            if ($steps['slaughter'] && ! $steps['post_mortem']) {
                $alerts[] = $this->alert(
                    __('Missing post-mortem'),
                    __('Batch :batch — post-mortem inspection pending', ['batch' => $batchCode]),
                    'warning',
                );
            }

            if ($evaluation['status'] === 'fully') {
                $alerts[] = $this->alert(
                    __('Batch fully traced'),
                    __('Batch :batch — all traceability steps verified', ['batch' => $batchCode]),
                    'success',
                );
            }

            $certificateCount = $evaluation['batch']->certificates->count();
            if ($certificateCount > 1) {
                $alerts[] = $this->alert(
                    __('Duplicate certificate'),
                    __('Batch :batch — duplicate certificate detected', ['batch' => $batchCode]),
                    'danger',
                );
            }

            $trip = $evaluation['batch']->certificates->first()?->transportTrips->first();
            if ($trip && $trip->status === TransportTrip::STATUS_IN_TRANSIT && $trip->departure_date?->lt(now()->subDays(2))) {
                $alerts[] = $this->alert(
                    __('Transport delay'),
                    __('Batch :batch — transport exceeded time limit', ['batch' => $batchCode]),
                    'warning',
                );
            }

            if ($steps['distribution'] && ! $steps['destination']) {
                $alerts[] = $this->alert(
                    __('Unregistered destination'),
                    __('Batch :batch — destination not confirmed', ['batch' => $batchCode]),
                    'warning',
                );
            }
        }

        return collect($alerts)
            ->unique(fn (array $alert) => $alert['title'].'|'.$alert['message'])
            ->take(6)
            ->values()
            ->all();
    }

    /**
     * @return array{title: string, message: string, severity: string, ago: string}
     */
    private function alert(string $title, string $message, string $severity): array
    {
        return [
            'title' => $title,
            'message' => $message,
            'severity' => $severity,
            'ago' => __('recently'),
        ];
    }

    /**
     * @param  Builder<Batch>  $query
     */
    private function applyBatchDateScope(Builder $query, array $filters): void
    {
        if (! $filters['is_filtered'] || $filters['start'] === null || $filters['end'] === null) {
            return;
        }

        $start = $filters['start']->copy()->startOfDay();
        $end = $filters['end']->copy()->endOfDay();

        $query->where(function (Builder $scoped) use ($start, $end): void {
            $scoped->whereHas(
                'slaughterExecution',
                fn (Builder $executionQuery) => $executionQuery->whereBetween('slaughter_time', [$start, $end]),
            )->orWhereHas(
                'certificates',
                fn (Builder $certificateQuery) => $certificateQuery->whereBetween('issued_at', [$start, $end]),
            );
        });
    }

    private function normalizeSearchType(mixed $searchType): string
    {
        $searchType = (string) $searchType;

        return array_key_exists($searchType, $this->searchTypes()) ? $searchType : 'batch_id';
    }

    private function normalizeBatchId(mixed $batchId): ?int
    {
        if ($batchId === null || $batchId === '' || ! is_numeric($batchId)) {
            return null;
        }

        return (int) $batchId;
    }
}
