<?php

namespace App\Services\SuperAdmin;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Support\AnteMortemChecklist;
use App\Models\Certificate;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Support\PostMortemChecklist;
use App\Models\RicaMonthlyInspectionReport;
use App\Models\SlaughterPlan;
use App\Models\WarehouseStorage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RicaMonthlyInspectionReportService
{
    public const FORM_ID = 'FPU/FRM/018';

    public const FORM_REVISION = '01';

    public const FORM_EFFECTIVE_DATE = '2024-04-29';

    /**
     * @return array{
     *     meta: array<string, mixed>,
     *     inspector: array<string, mixed>|null,
     *     inspectors: list<array<string, mixed>>,
     *     slaughterhouse: array<string, mixed>,
     *     received_animals: array{rows: list<array<string, mixed>>, totals_by_species: list<array<string, mixed>>},
     *     ante_mortem: array{rows: list<array<string, mixed>>},
     *     post_mortem: array{rows: list<array<string, mixed>>, totals_by_species: list<array<string, mixed>>},
     *     meat_supply: array{rows: list<array<string, mixed>>, totals_by_species: list<array<string, mixed>>, certificate_serial_range: array{start: string|null, end: string|null}},
     *     closure: array<string, mixed>,
     *     submission: RicaMonthlyInspectionReport|null
     * }
     */
    public function build(
        Facility $facility,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?RicaMonthlyInspectionReport $submission = null,
    ): array {
        $facility->loadMissing([
            'business',
            'districtDivision',
            'sectorDivision',
            'cell',
            'village',
        ]);

        $periodStart = $periodStart->copy()->startOfDay();
        $periodEnd = $periodEnd->copy()->endOfDay();

        $planIds = SlaughterPlan::query()
            ->where('facility_id', $facility->id)
            ->pluck('id');

        $inspectors = $this->inspectorsForPeriod((int) $facility->id, $planIds, $periodStart, $periodEnd);
        $primaryInspector = $inspectors->first();
        $submission ??= RicaMonthlyInspectionReport::findForPeriod((int) $facility->id, $periodStart);
        $submission?->loadMissing('submittedBy');

        return [
            'meta' => $this->buildMeta($facility, $periodStart, $periodEnd, $submission),
            'inspector' => $primaryInspector,
            'inspectors' => $inspectors->values()->all(),
            'slaughterhouse' => $this->buildSlaughterhouseSection($facility),
            'received_animals' => $this->buildReceivedAnimalsSection((int) $facility->id, $periodStart, $periodEnd),
            'ante_mortem' => $this->buildAnteMortemSection((int) $facility->id, $periodStart, $periodEnd),
            'post_mortem' => $this->buildPostMortemSection((int) $facility->id, $periodStart, $periodEnd),
            'meat_supply' => $this->buildMeatSupplySection((int) $facility->id, $periodStart, $periodEnd),
            'closure' => $this->buildClosureSection($submission, $inspectors),
            'submission' => $submission,
        ];
    }

    /**
     * @return array{year: int, month: int, periodStart: Carbon, periodEnd: Carbon}
     */
    public function resolvePeriod(Request $request): array
    {
        if ($request->filled('year') && $request->filled('month')) {
            $year = max(2000, (int) $request->integer('year'));
            $month = min(12, max(1, (int) $request->integer('month')));
            $periodStart = Carbon::create($year, $month, 1)->startOfMonth();

            return [
                'year' => $year,
                'month' => $month,
                'periodStart' => $periodStart,
                'periodEnd' => $periodStart->copy()->endOfMonth(),
            ];
        }

        if ($request->filled('month')) {
            $parsed = Carbon::parse($request->string('month').'-01');

            return [
                'year' => (int) $parsed->year,
                'month' => (int) $parsed->month,
                'periodStart' => $parsed->copy()->startOfMonth(),
                'periodEnd' => $parsed->copy()->endOfMonth(),
            ];
        }

        $now = now();

        return [
            'year' => (int) $now->year,
            'month' => (int) $now->month,
            'periodStart' => $now->copy()->startOfMonth(),
            'periodEnd' => $now->copy()->endOfMonth(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMeta(
        Facility $facility,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?RicaMonthlyInspectionReport $submission = null,
    ): array {
        $periodKey = $periodStart->format('Ym');

        return [
            'form_id' => self::FORM_ID,
            'revision' => self::FORM_REVISION,
            'effective_date' => self::FORM_EFFECTIVE_DATE,
            'title_en' => __('Private Meat Inspection Report Form'),
            'title_rw' => __('IFISHI YA RAPORO Y\'UMUGENZUZI W\'INYAMA WIGENGA'),
            'reporting_date' => $submission?->submitted_at ?? now(),
            'report_number' => sprintf('%s-%d-%s', self::FORM_ID, $facility->id, $periodKey),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'period_label' => $periodStart->format('F Y'),
            'status' => $submission?->status ?? RicaMonthlyInspectionReport::STATUS_DRAFT,
            'submitted_at' => $submission?->submitted_at,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $inspectors
     * @return array<string, mixed>
     */
    private function buildClosureSection(?RicaMonthlyInspectionReport $submission, Collection $inspectors): array
    {
        $defaultSignatures = $inspectors
            ->take(3)
            ->map(fn (array $inspector) => [
                'name' => $inspector['name'],
                'signed_at' => null,
            ])
            ->values()
            ->all();

        if ($defaultSignatures === []) {
            $defaultSignatures = [
                ['name' => null, 'signed_at' => null],
                ['name' => null, 'signed_at' => null],
            ];
        }

        if ($submission === null) {
            return [
                'challenges' => null,
                'recommendations' => null,
                'inspector_signatures' => $defaultSignatures,
                'operator_name' => null,
                'operator_signed_at' => null,
                'stamp_acknowledged' => false,
                'status' => RicaMonthlyInspectionReport::STATUS_DRAFT,
                'submitted_at' => null,
                'submitted_by_name' => null,
            ];
        }

        $storedSignatures = $submission->normalizedInspectorSignatures();
        $inspectorSignatures = $storedSignatures !== [] ? $storedSignatures : $defaultSignatures;

        return [
            'challenges' => $submission->challenges,
            'recommendations' => $submission->recommendations,
            'inspector_signatures' => $inspectorSignatures,
            'operator_name' => $submission->operator_name,
            'operator_signed_at' => $submission->operator_signed_at,
            'stamp_acknowledged' => (bool) $submission->stamp_acknowledged,
            'status' => $submission->status,
            'submitted_at' => $submission->submitted_at,
            'submitted_by_name' => $submission->submittedBy?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSlaughterhouseSection(Facility $facility): array
    {
        $facility->loadMissing([
            'business.districtDivision',
            'business.sectorDivision',
            'business.cellDivision',
            'business.villageDivision',
            'country',
            'province',
            'districtDivision',
            'sectorDivision',
            'cell',
            'village',
        ]);

        $business = $facility->business;

        return [
            'name' => $facility->facility_name,
            'facility_type' => $facility->facility_type,
            'registration_number' => $facility->registration_number,
            'license_number' => $facility->license_number,
            'license_issue_date' => $facility->license_issue_date,
            'email' => $business?->email ?: $business?->owner_email,
            'phone' => $facility->phone
                ?: $business?->contact_phone
                ?: $business?->owner_phone,
            'district' => $facility->districtDivision?->name
                ?? $facility->getRawOriginal('district')
                ?? $business?->districtDivision?->name
                ?? $business?->getRawOriginal('district'),
            'sector' => $facility->sectorDivision?->name
                ?? $facility->getRawOriginal('sector')
                ?? $business?->sectorDivision?->name
                ?? $business?->getRawOriginal('sector'),
            'cell' => $facility->cell?->name ?? $business?->cellDivision?->name,
            'village' => $facility->village?->name ?? $business?->villageDivision?->name,
            'operator_name' => $business?->business_name,
        ];
    }

    /**
     * @param  Collection<int, int|string>  $planIds
     * @return Collection<int, array<string, mixed>>
     */
    private function inspectorsForPeriod(int $facilityId, Collection $planIds, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $inspectorIds = collect();

        if ($planIds->isNotEmpty()) {
            $planInspectorIds = SlaughterPlan::query()
                ->whereIn('id', $planIds)
                ->whereBetween('slaughter_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->pluck('inspector_id');
            $inspectorIds = $inspectorIds->merge($planInspectorIds);
        }

        $certificateInspectorIds = Certificate::query()
            ->where('facility_id', $facilityId)
            ->whereBetween('issued_at', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->pluck('inspector_id');

        $inspectorIds = $inspectorIds
            ->merge($certificateInspectorIds)
            ->filter()
            ->unique()
            ->values();

        if ($inspectorIds->isEmpty()) {
            $fallback = Inspector::query()
                ->where('facility_id', $facilityId)
                ->where('status', Inspector::STATUS_ACTIVE)
                ->orderBy('last_name')
                ->first();

            return $fallback ? collect([$this->formatInspector($fallback)]) : collect();
        }

        $activityCounts = Certificate::query()
            ->where('facility_id', $facilityId)
            ->whereBetween('issued_at', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->selectRaw('inspector_id, COUNT(*) as activity_count')
            ->groupBy('inspector_id')
            ->pluck('activity_count', 'inspector_id');

        return Inspector::query()
            ->whereIn('id', $inspectorIds)
            ->get()
            ->sortByDesc(fn (Inspector $inspector) => (int) ($activityCounts[$inspector->id] ?? 0))
            ->values()
            ->map(fn (Inspector $inspector) => $this->formatInspector($inspector));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatInspector(Inspector $inspector): array
    {
        return [
            'id' => $inspector->id,
            'name' => $inspector->full_name,
            'email' => $inspector->email,
            'phone' => $inspector->phone_number,
            'authorization_number' => $inspector->authorization_number,
            'authorization_issue_date' => $inspector->authorization_issue_date,
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totals_by_species: list<array<string, mixed>>}
     */
    private function buildReceivedAnimalsSection(int $facilityId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $items = AnimalIntakeItem::query()
            ->join('animal_intakes', 'animal_intake_items.animal_intake_id', '=', 'animal_intakes.id')
            ->where('animal_intakes.facility_id', $facilityId)
            ->where('animal_intakes.is_draft', false)
            ->whereIn('animal_intakes.status', [AnimalIntake::STATUS_RECEIVED, AnimalIntake::STATUS_APPROVED])
            ->whereBetween('animal_intakes.intake_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->select([
                'animal_intake_items.species',
                'animal_intake_items.sex',
                'animal_intake_items.notes',
                'animal_intakes.id as intake_id',
            ])
            ->get();

        if ($items->isEmpty()) {
            return ['rows' => [], 'totals_by_species' => []];
        }

        $intakes = AnimalIntake::query()
            ->with(['province', 'district', 'sector', 'cell', 'village', 'country'])
            ->whereIn('id', $items->pluck('intake_id')->unique())
            ->get()
            ->keyBy('id');

        $grouped = [];
        foreach ($items as $item) {
            $intake = $intakes->get($item->intake_id);
            $origin = $intake ? $this->resolveIntakeOrigin($intake) : __('Unknown origin');
            $species = $item->species ?: __('Unknown');
            $key = Str::lower($origin).'|'.Str::lower($species);

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'origin' => $origin,
                    'species' => $species,
                    'male' => 0,
                    'female' => 0,
                    'comments' => [],
                ];
            }

            if ($item->sex === AnimalIntake::SEX_FEMALE) {
                $grouped[$key]['female']++;
            } elseif ($item->sex === AnimalIntake::SEX_MALE) {
                $grouped[$key]['male']++;
            }

            $notes = trim((string) ($item->notes ?? ''));
            if ($notes !== '') {
                $grouped[$key]['comments'][$notes] = $notes;
            }
        }

        $rows = collect($grouped)
            ->map(function (array $row) {
                $row['comment'] = implode('; ', array_values($row['comments']));
                unset($row['comments']);

                return $row;
            })
            ->sortBy(['species', 'origin'])
            ->values()
            ->all();
        $totalsBySpecies = $this->sumSexTotalsBySpecies($rows);

        return [
            'rows' => $rows,
            'totals_by_species' => $totalsBySpecies,
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>}
     */
    private function buildAnteMortemSection(int $facilityId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $periodStartStr = $periodStart->toDateString();
        $periodEndStr = $periodEnd->toDateString();

        $items = AnteMortemInspectionItem::query()
            ->with(['intakeItem', 'inspection.observations'])
            ->whereHas('inspection.slaughterPlan', fn ($q) => $q->where('facility_id', $facilityId))
            ->whereHas('inspection', fn ($q) => $q
                ->whereBetween('inspection_date', [$periodStartStr, $periodEndStr]))
            ->get();

        $legacyInspections = AnteMortemInspection::query()
            ->with(['observations'])
            ->whereHas('slaughterPlan', fn ($q) => $q->where('facility_id', $facilityId))
            ->whereBetween('inspection_date', [$periodStartStr, $periodEndStr])
            ->whereDoesntHave('inspectionItems')
            ->get();

        if ($items->isEmpty() && $legacyInspections->isEmpty()) {
            return ['rows' => []];
        }

        $bySpecies = [];

        foreach ($items as $item) {
            $species = $item->intakeItem?->species
                ?? $item->inspection?->species
                ?? __('Unknown');

            if (! isset($bySpecies[$species])) {
                $bySpecies[$species] = [
                    'species' => $species,
                    'healthy' => 0,
                    'unhealthy' => [],
                ];
            }

            if ($item->outcome === AnteMortemInspectionItem::OUTCOME_APPROVED) {
                $bySpecies[$species]['healthy']++;

                continue;
            }

            $inspection = $item->inspection;
            $observations = $inspection
                ? $inspection->observations->where('animal_intake_item_id', $item->animal_intake_item_id)
                : collect();

            $bySpecies[$species]['unhealthy'][] = [
                'number' => count($bySpecies[$species]['unhealthy']) + 1,
                'conditions' => $this->resolveAnteMortemConditions($item, $observations, $species),
                'action_taken' => $this->resolveAnteMortemActionTaken($item, $inspection),
                'final_action' => $this->anteMortemFinalAction($item->outcome),
            ];
        }

        foreach ($legacyInspections as $inspection) {
            $species = $inspection->species ?: __('Unknown');

            if (! isset($bySpecies[$species])) {
                $bySpecies[$species] = [
                    'species' => $species,
                    'healthy' => 0,
                    'unhealthy' => [],
                ];
            }

            $bySpecies[$species]['healthy'] += (int) $inspection->number_approved;

            if ((int) $inspection->number_rejected <= 0) {
                continue;
            }

            $bySpecies[$species]['unhealthy'][] = [
                'number' => (int) $inspection->number_rejected,
                'conditions' => $this->formatAnteMortemConditions(
                    $inspection->observations->whereNull('animal_intake_item_id'),
                    $species,
                ),
                'action_taken' => $this->legacyAnteMortemActionTaken($inspection),
                'final_action' => __('Rejected'),
            ];
        }

        $rows = collect($bySpecies)
            ->map(function (array $row) {
                $row['unhealthy_count'] = count($row['unhealthy']);

                return $row;
            })
            ->sortBy('species')
            ->values()
            ->all();

        return ['rows' => $rows];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totals_by_species: list<array<string, mixed>>}
     */
    private function buildPostMortemSection(int $facilityId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $periodStartStr = $periodStart->toDateString();
        $periodEndStr = $periodEnd->toDateString();

        $items = PostMortemInspectionItem::query()
            ->with(['intakeItem', 'inspection.observations'])
            ->whereHas('inspection', function ($query) use ($facilityId, $periodStartStr, $periodEndStr) {
                $query->whereBetween('inspection_date', [$periodStartStr, $periodEndStr])
                    ->whereHas(
                        'batch.slaughterExecution.slaughterPlan',
                        fn ($planQuery) => $planQuery->where('facility_id', $facilityId),
                    );
            })
            ->get();

        $legacyInspections = PostMortemInspection::query()
            ->with(['observations'])
            ->whereBetween('inspection_date', [$periodStartStr, $periodEndStr])
            ->whereHas(
                'batch.slaughterExecution.slaughterPlan',
                fn ($planQuery) => $planQuery->where('facility_id', $facilityId),
            )
            ->whereDoesntHave('inspectionItems')
            ->get();

        if ($items->isEmpty() && $legacyInspections->isEmpty()) {
            return ['rows' => [], 'totals_by_species' => []];
        }

        $bySpecies = [];

        foreach ($items as $item) {
            $species = $item->intakeItem?->species
                ?? $item->inspection?->species
                ?? __('Unknown');

            if (! isset($bySpecies[$species])) {
                $bySpecies[$species] = [
                    'species' => $species,
                    'approved' => 0,
                    'condemned' => [],
                ];
            }

            if ($item->outcome === PostMortemInspectionItem::OUTCOME_APPROVED) {
                $bySpecies[$species]['approved']++;

                continue;
            }

            if ($item->outcome !== PostMortemInspectionItem::OUTCOME_CONDEMNED) {
                continue;
            }

            $observations = $item->inspection
                ? $item->inspection->observations->where('animal_intake_item_id', $item->animal_intake_item_id)
                : collect();

            $bySpecies[$species]['condemned'][] = [
                'number' => count($bySpecies[$species]['condemned']) + 1,
                'seized_part' => $this->resolvePostMortemSeizedPart($item, $observations, $species),
                'reason' => $this->resolvePostMortemReason($item, $observations, $species),
                'qty_kg' => round((float) ($item->carcass_weight_kg ?? 0), 2),
            ];
        }

        foreach ($legacyInspections as $inspection) {
            $species = $inspection->species ?: __('Unknown');

            if (! isset($bySpecies[$species])) {
                $bySpecies[$species] = [
                    'species' => $species,
                    'approved' => 0,
                    'condemned' => [],
                ];
            }

            if ((float) $inspection->condemned_quantity <= 0) {
                continue;
            }

            $observations = $inspection->observations->whereNull('animal_intake_item_id');

            $bySpecies[$species]['condemned'][] = [
                'number' => count($bySpecies[$species]['condemned']) + 1,
                'seized_part' => $this->formatPostMortemSeizedPart($observations, $species),
                'reason' => $this->formatPostMortemReason($observations, $species, $inspection->notes),
                'qty_kg' => round((float) $inspection->condemned_quantity, 2),
            ];
        }

        $rows = collect($bySpecies)
            ->map(function (array $row) {
                $row['condemned_count'] = count($row['condemned']);

                return $row;
            })
            ->sortBy('species')
            ->values()
            ->all();

        $totalsBySpecies = collect($rows)
            ->map(fn (array $row) => [
                'species' => $row['species'],
                'qty_kg' => round((float) collect($row['condemned'])->sum('qty_kg'), 2),
            ])
            ->filter(fn (array $total) => $total['qty_kg'] > 0)
            ->sortBy('species')
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'totals_by_species' => $totalsBySpecies,
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totals_by_species: list<array<string, mixed>>, certificate_serial_range: array{start: string|null, end: string|null}}
     */
    private function buildMeatSupplySection(int $facilityId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $periodStart = $periodStart->copy()->startOfDay();
        $periodEnd = $periodEnd->copy()->endOfDay();

        $certificates = Certificate::query()
            ->where(function ($query) use ($facilityId) {
                $query->where('facility_id', $facilityId)
                    ->orWhereHas('batch.slaughterExecution.slaughterPlan', function ($planQuery) use ($facilityId) {
                        $planQuery->where('facility_id', $facilityId);
                    });
            })
            ->where('status', '!=', Certificate::STATUS_REVOKED)
            ->whereBetween('issued_at', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->with([
                'batch.items.intakeItem',
                'batch.slaughterExecution.slaughterPlan',
                'warehouseStorages.intakeItem',
                'transportTrips.destinationFacility.districtDivision',
                'transportTrips.destinationFacility.sectorDivision',
                'transportTrips.destinationFacility.cell',
                'transportTrips.deliveryConfirmation.client.districtDivision',
                'transportTrips.deliveryConfirmation.client.sectorDivision',
                'transportTrips.deliveryConfirmation.client.cell',
            ])
            ->orderBy('issued_at')
            ->orderBy('certificate_number')
            ->get();

        if ($certificates->isEmpty()) {
            return [
                'rows' => [],
                'totals_by_species' => [],
                'certificate_serial_range' => ['start' => null, 'end' => null],
            ];
        }

        $certificateIds = $certificates->pluck('id');
        $releasedKgByCertificate = WarehouseStorage::query()
            ->whereIn('certificate_id', $certificateIds)
            ->where('status', WarehouseStorage::STATUS_RELEASED)
            ->selectRaw('certificate_id, COALESCE(SUM(quantity_stored), 0) as total_kg')
            ->groupBy('certificate_id')
            ->pluck('total_kg', 'certificate_id')
            ->all();

        $batchIds = $certificates->pluck('batch_id')->filter()->unique();
        $releasedKgByBatch = $batchIds->isEmpty()
            ? []
            : WarehouseStorage::query()
                ->whereIn('batch_id', $batchIds)
                ->where('status', WarehouseStorage::STATUS_RELEASED)
                ->selectRaw('batch_id, COALESCE(SUM(quantity_stored), 0) as total_kg')
                ->groupBy('batch_id')
                ->pluck('total_kg', 'batch_id')
                ->all();

        $rows = $certificates->map(function (Certificate $certificate) use ($releasedKgByCertificate, $releasedKgByBatch) {
            $destination = $this->resolveCertificateDestination($certificate);

            return [
                'species' => $this->resolveCertificateSpecies($certificate),
                'qty_kg' => $this->resolveCertificateQtyKg($certificate, $releasedKgByCertificate, $releasedKgByBatch),
                'certificate_number' => $certificate->certificate_number ?: ('#'.$certificate->id),
                'issued_at' => $certificate->issued_at,
                'destination_district' => $destination['district'],
                'destination_sector' => $destination['sector'],
                'destination_other' => $destination['other'],
            ];
        })->values()->all();

        $totalsBySpecies = collect($rows)
            ->groupBy('species')
            ->map(fn (Collection $group, string $species) => [
                'species' => $species,
                'qty_kg' => round((float) $group->sum('qty_kg'), 2),
            ])
            ->sortBy('species')
            ->values()
            ->all();

        $certNumbers = $certificates
            ->map(fn (Certificate $c) => $c->certificate_number)
            ->filter()
            ->sort()
            ->values();

        return [
            'rows' => $rows,
            'totals_by_species' => $totalsBySpecies,
            'certificate_serial_range' => [
                'start' => $certNumbers->first(),
                'end' => $certNumbers->last(),
            ],
        ];
    }

    private function resolveIntakeOrigin(AnimalIntake $intake): string
    {
        $intake->loadMissing(['province', 'district', 'sector', 'cell', 'village', 'country']);

        $parts = array_values(array_filter([
            $intake->province?->name,
            $intake->district?->name,
            $intake->sector?->name,
            $intake->cell?->name,
            $intake->village?->name,
        ], fn (?string $name) => $name !== null && $name !== ''));

        if ($parts !== []) {
            return implode(', ', $parts);
        }

        if ($intake->country?->name) {
            return $intake->country->name;
        }

        return __('Unknown origin');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sumSexTotalsBySpecies(array $rows): array
    {
        return collect($rows)
            ->groupBy('species')
            ->map(fn (Collection $group, string $species) => [
                'species' => $species,
                'male' => (int) $group->sum('male'),
                'female' => (int) $group->sum('female'),
            ])
            ->sortBy('species')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, \App\Models\AnteMortemObservation>  $observations
     */
    private function resolveAnteMortemConditions(
        AnteMortemInspectionItem $item,
        Collection $observations,
        ?string $species,
    ): string {
        $explicit = trim((string) ($item->conditions ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        return $this->formatAnteMortemConditions($observations, $species);
    }

    private function resolveAnteMortemActionTaken(AnteMortemInspectionItem $item, ?AnteMortemInspection $inspection): string
    {
        $explicit = trim((string) ($item->action_taken ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        return $this->anteMortemActionTaken($item, $inspection);
    }

    /**
     * @param  Collection<int, \App\Models\AnteMortemObservation>  $observations
     */
    private function formatAnteMortemConditions(Collection $observations, ?string $species): string
    {
        $checklistItems = AnteMortemChecklist::itemsForSpecies($species);
        $parts = [];

        foreach ($observations as $observation) {
            $itemKey = (string) $observation->item;
            $value = trim((string) $observation->value);
            $notes = trim((string) ($observation->notes ?? ''));

            if ($itemKey === 'decision') {
                continue;
            }

            if ($itemKey === 'observation') {
                if ($value !== '') {
                    $parts[] = $notes !== '' ? $value.' ('.$notes.')' : $value;
                } elseif ($notes !== '') {
                    $parts[] = $notes;
                }

                continue;
            }

            $label = $checklistItems[$itemKey]['label']
                ?? Str::of($itemKey)->replace('_', ' ')->title()->toString();

            if (in_array($value, ['abnormal', 'no'], true)) {
                $parts[] = $notes !== ''
                    ? $label.': '.ucfirst($value).' ('.$notes.')'
                    : $label.': '.ucfirst($value);
            }
        }

        return $parts !== [] ? implode('; ', $parts) : __('Not recorded');
    }

    /**
     * @param  Collection<int, \App\Models\PostMortemObservation>  $observations
     */
    private function resolvePostMortemSeizedPart(
        PostMortemInspectionItem $item,
        Collection $observations,
        ?string $species,
    ): string {
        $explicit = trim((string) ($item->seized_part ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        return $this->formatPostMortemSeizedPart($observations, $species);
    }

    /**
     * @param  Collection<int, \App\Models\PostMortemObservation>  $observations
     */
    private function resolvePostMortemReason(
        PostMortemInspectionItem $item,
        Collection $observations,
        ?string $species,
    ): string {
        $explicit = trim((string) ($item->reason ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        return $this->formatPostMortemReason($observations, $species, $item->outcome_notes);
    }

    /**
     * @param  Collection<int, \App\Models\PostMortemObservation>  $observations
     */
    private function formatPostMortemSeizedPart(Collection $observations, ?string $species): string
    {
        $checklistItems = PostMortemChecklist::itemsForSpecies($species);
        $parts = [];

        foreach ($observations as $observation) {
            $itemKey = (string) $observation->item;
            $meta = $checklistItems[$itemKey] ?? null;

            if ($itemKey === 'decision' || $itemKey === 'comment' || ($meta['category'] ?? '') !== 'organ') {
                continue;
            }

            if (PostMortemChecklist::isAbnormalValue((string) $observation->value)) {
                $parts[] = $meta['label']
                    ?? Str::of($itemKey)->replace('_', ' ')->title()->toString();
            }
        }

        return $parts !== [] ? implode(', ', $parts) : __('Whole carcass');
    }

    /**
     * @param  Collection<int, \App\Models\PostMortemObservation>  $observations
     */
    private function formatPostMortemReason(Collection $observations, ?string $species, ?string $outcomeNotes): string
    {
        $checklistItems = PostMortemChecklist::itemsForSpecies($species);
        $parts = [];

        foreach ($observations as $observation) {
            if ((string) $observation->item !== 'comment') {
                continue;
            }

            $value = trim((string) $observation->value);
            $notes = trim((string) ($observation->notes ?? ''));

            if ($value !== '') {
                $parts[] = $value;
            }

            if ($notes !== '') {
                $parts[] = $notes;
            }
        }

        foreach ($observations as $observation) {
            $itemKey = (string) $observation->item;
            $meta = $checklistItems[$itemKey] ?? null;

            if (($meta['category'] ?? '') !== 'organ' || ! PostMortemChecklist::isAbnormalValue((string) $observation->value)) {
                continue;
            }

            $notes = trim((string) ($observation->notes ?? ''));
            if ($notes === '') {
                continue;
            }

            $label = $meta['label'] ?? $itemKey;
            $parts[] = $label.': '.$notes;
        }

        $notes = trim((string) ($outcomeNotes ?? ''));
        if ($notes !== '') {
            $parts[] = $notes;
        }

        return $parts !== [] ? implode('; ', array_unique($parts)) : __('Condemned at post-mortem');
    }

    private function anteMortemActionTaken(AnteMortemInspectionItem $item, ?AnteMortemInspection $inspection): string
    {
        $notes = trim((string) ($item->outcome_notes ?? ''));

        return match ($item->outcome) {
            AnteMortemInspectionItem::OUTCOME_DEFERRED => $notes !== ''
                ? $notes
                : (trim((string) ($inspection?->notes_for_under_observation ?? '')) ?: __('Deferred / under observation')),
            AnteMortemInspectionItem::OUTCOME_REJECTED => $notes !== ''
                ? $notes
                : __('Rejected at ante-mortem inspection'),
            default => $notes !== '' ? $notes : __('Approved for slaughter'),
        };
    }

    private function legacyAnteMortemActionTaken(AnteMortemInspection $inspection): string
    {
        $notes = trim((string) ($inspection->notes_for_under_observation ?? ''));

        if ($notes !== '') {
            return $notes;
        }

        $generalNotes = trim((string) ($inspection->notes ?? ''));

        return $generalNotes !== '' ? $generalNotes : __('Rejected at ante-mortem inspection');
    }

    private function anteMortemFinalAction(string $outcome): string
    {
        return match ($outcome) {
            AnteMortemInspectionItem::OUTCOME_REJECTED => __('Rejected'),
            AnteMortemInspectionItem::OUTCOME_DEFERRED => __('Deferred'),
            AnteMortemInspectionItem::OUTCOME_APPROVED => __('Approved'),
            default => ucfirst($outcome),
        };
    }

    /**
     * @param  array<int|string, float|int|string>  $releasedKgByCertificate
     * @param  array<int|string, float|int|string>  $releasedKgByBatch
     */
    private function resolveCertificateQtyKg(
        Certificate $certificate,
        array $releasedKgByCertificate,
        array $releasedKgByBatch = [],
    ): float {
        $carcassKg = data_get($certificate->pdf_details, 'carcass_meat_kg');
        $otherKg = data_get($certificate->pdf_details, 'other_meat_kg');
        $pdfTotal = 0.0;

        if (is_numeric($carcassKg)) {
            $pdfTotal += (float) $carcassKg;
        }

        if (is_numeric($otherKg)) {
            $pdfTotal += (float) $otherKg;
        }

        if ($pdfTotal > 0) {
            return round($pdfTotal, 2);
        }

        if (isset($releasedKgByCertificate[$certificate->id])) {
            return round((float) $releasedKgByCertificate[$certificate->id], 2);
        }

        if ($certificate->batch_id && isset($releasedKgByBatch[$certificate->batch_id])) {
            return round((float) $releasedKgByBatch[$certificate->batch_id], 2);
        }

        return round((float) ($certificate->batch?->quantity ?? 0), 2);
    }

    private function resolveCertificateSpecies(Certificate $certificate): string
    {
        $fromPdf = $this->nullablePdfDetail($certificate, 'species');
        if ($fromPdf !== null) {
            return $fromPdf;
        }

        $fromBatch = $certificate->batch?->items->first()?->intakeItem?->species
            ?? $certificate->batch?->species
            ?? $certificate->batch?->slaughterExecution?->slaughterPlan?->species;
        if ($fromBatch) {
            return $fromBatch;
        }

        $fromStorage = $certificate->warehouseStorages
            ->first(fn (WarehouseStorage $storage) => $storage->intakeItem?->species !== null)
            ?->intakeItem
            ?->species;
        if ($fromStorage) {
            return $fromStorage;
        }

        return __('Unknown');
    }

    private function nullablePdfDetail(Certificate $certificate, string $key): ?string
    {
        $value = trim((string) data_get($certificate->pdf_details, $key));

        return $value !== '' && $value !== '—' ? $value : null;
    }

    /**
     * @return array{district: string|null, sector: string|null, other: string|null}
     */
    private function parseLocationLine(?string $line): array
    {
        $line = trim((string) $line);
        if ($line === '' || $line === '—') {
            return [
                'district' => null,
                'sector' => null,
                'other' => null,
            ];
        }

        $parts = array_values(array_filter(
            array_map('trim', explode(',', $line)),
            fn (string $part) => $part !== '',
        ));

        return [
            'district' => $parts[0] ?? null,
            'sector' => $parts[1] ?? null,
            'other' => isset($parts[2]) ? implode(', ', array_slice($parts, 2)) : null,
        ];
    }

    /**
     * @return array{district: string|null, sector: string|null, other: string|null}
     */
    private function resolveCertificateDestination(Certificate $certificate): array
    {
        $trip = $certificate->transportTrips->sortByDesc('departure_date')->first();
        if ($trip?->destinationFacility) {
            $facility = $trip->destinationFacility;
            $other = trim((string) ($facility->cell?->name ?? $facility->getRawOriginal('cell') ?? ''));
            if ($other === '') {
                $other = $this->nullablePdfDetail($certificate, 'shop_name')
                    ?? $this->nullablePdfDetail($certificate, 'departure_destination');
            }

            return [
                'district' => $facility->districtDivision?->name ?? $facility->getRawOriginal('district'),
                'sector' => $facility->sectorDivision?->name ?? $facility->getRawOriginal('sector'),
                'other' => $other ?: null,
            ];
        }

        if ($trip && $trip->isExternalDestination()) {
            $confirmation = $trip->deliveryConfirmation;
            $client = $confirmation?->client;
            if ($client) {
                $client->loadMissing(['districtDivision', 'sectorDivision', 'cell']);

                return [
                    'district' => $client->districtDivision?->name,
                    'sector' => $client->sectorDivision?->name,
                    'other' => trim(collect([
                        $trip->destination_name,
                        $client->cell?->name,
                        $client->address_line_1,
                    ])->filter(fn (?string $part) => $part !== null && $part !== '')->first() ?? '')
                        ?: $trip->destination_name,
                ];
            }

            $addressLine = trim((string) ($trip->destination_address ?? ''));
            if ($addressLine !== '') {
                $parsed = $this->parseLocationLine($addressLine);
                if ($parsed['district'] || $parsed['sector'] || $parsed['other']) {
                    if (! $parsed['other']) {
                        $parsed['other'] = $trip->destination_name;
                    }

                    return $parsed;
                }
            }

            return [
                'district' => null,
                'sector' => null,
                'other' => $trip->destination_name ?: $trip->destination_display,
            ];
        }

        $sellingLocation = $this->nullablePdfDetail($certificate, 'selling_location');
        if ($sellingLocation) {
            $parsed = $this->parseLocationLine($sellingLocation);
            if (! $parsed['other']) {
                $parsed['other'] = $this->nullablePdfDetail($certificate, 'shop_name')
                    ?? $this->nullablePdfDetail($certificate, 'departure_destination');
            }

            return $parsed;
        }

        $departureDestination = $this->nullablePdfDetail($certificate, 'departure_destination');
        if ($departureDestination) {
            $parsed = $this->parseLocationLine($departureDestination);
            if ($parsed['district'] || $parsed['sector']) {
                if (! $parsed['other']) {
                    $parsed['other'] = $this->nullablePdfDetail($certificate, 'shop_name');
                }

                return $parsed;
            }

            return [
                'district' => null,
                'sector' => null,
                'other' => $departureDestination,
            ];
        }

        $shopName = $this->nullablePdfDetail($certificate, 'shop_name');
        if ($shopName) {
            return [
                'district' => null,
                'sector' => null,
                'other' => $shopName,
            ];
        }

        return [
            'district' => null,
            'sector' => null,
            'other' => null,
        ];
    }
}
