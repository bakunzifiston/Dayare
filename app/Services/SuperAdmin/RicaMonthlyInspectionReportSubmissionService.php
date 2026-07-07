<?php

namespace App\Services\SuperAdmin;

use App\Models\Facility;
use App\Models\RicaMonthlyInspectionReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class RicaMonthlyInspectionReportSubmissionService
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function saveDraft(Facility $facility, Carbon $periodStart, array $input): RicaMonthlyInspectionReport
    {
        $record = $this->findOrCreate($facility, $periodStart);
        $this->assertEditable($record);

        $attributes = $this->mappedAttributes($input, $record);
        $record->fill([
            'challenges' => $attributes['challenges'],
            'recommendations' => $attributes['recommendations'],
            'inspector_signatures' => $attributes['inspector_signatures'],
            'operator_name' => $attributes['operator_name'],
            'stamp_acknowledged' => $attributes['stamp_acknowledged'],
            'status' => RicaMonthlyInspectionReport::STATUS_DRAFT,
            'submitted_at' => null,
            'submitted_by_user_id' => null,
        ]);

        if ($attributes['operator_signed_at'] !== null) {
            $record->operator_signed_at = $attributes['operator_signed_at'];
        }

        $record->save();

        return $record->fresh();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function submit(Facility $facility, Carbon $periodStart, array $input, User $user): RicaMonthlyInspectionReport
    {
        $record = $this->findOrCreate($facility, $periodStart);
        $this->assertEditable($record);

        $attributes = $this->mappedAttributes($input, $record);
        $this->assertSubmitRequirements($attributes);

        $record->fill([
            'challenges' => $attributes['challenges'],
            'recommendations' => $attributes['recommendations'],
            'inspector_signatures' => $attributes['inspector_signatures'],
            'operator_name' => $attributes['operator_name'],
            'operator_signed_at' => $attributes['operator_signed_at'],
            'stamp_acknowledged' => $attributes['stamp_acknowledged'],
            'status' => RicaMonthlyInspectionReport::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'submitted_by_user_id' => $user->id,
        ]);
        $record->save();

        return $record->fresh(['submittedBy']);
    }

    private function findOrCreate(Facility $facility, Carbon $periodStart): RicaMonthlyInspectionReport
    {
        return RicaMonthlyInspectionReport::query()->firstOrCreate(
            [
                'facility_id' => $facility->id,
                'period_year' => $periodStart->year,
                'period_month' => $periodStart->month,
            ],
            [
                'status' => RicaMonthlyInspectionReport::STATUS_DRAFT,
            ]
        );
    }

    private function assertEditable(RicaMonthlyInspectionReport $record): void
    {
        if ($record->isSubmitted()) {
            throw ValidationException::withMessages([
                'status' => __('This report has already been submitted and cannot be changed.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function mappedAttributes(array $input, RicaMonthlyInspectionReport $existing): array
    {
        $existingSignatures = collect($existing->inspector_signatures ?? [])->values();

        $signatures = collect(Arr::wrap($input['inspector_signatures'] ?? []))
            ->take(6)
            ->values()
            ->map(function ($row, int $index) use ($existingSignatures) {
                $name = trim((string) ($row['name'] ?? ''));
                $attest = filter_var($row['attest'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $priorSignedAt = $existingSignatures->get($index)['signed_at'] ?? null;

                $signedAt = null;
                if ($attest && $name !== '') {
                    $signedAt = now()->toIso8601String();
                } elseif ($name !== '' && $priorSignedAt) {
                    $signedAt = $priorSignedAt;
                }

                return [
                    'name' => $name !== '' ? $name : null,
                    'signed_at' => $signedAt,
                ];
            })
            ->filter(fn (array $row) => ($row['name'] ?? '') !== '' || ($row['signed_at'] ?? '') !== '')
            ->values()
            ->all();

        $operatorName = trim((string) ($input['operator_name'] ?? ''));
        $operatorAttest = filter_var($input['operator_attest'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $operatorSignedAt = null;
        if ($operatorAttest && $operatorName !== '') {
            $operatorSignedAt = now();
        } elseif ($existing->operator_signed_at && $operatorName !== '') {
            $operatorSignedAt = $existing->operator_signed_at;
        }

        return [
            'challenges' => $this->nullableText($input['challenges'] ?? null),
            'recommendations' => $this->nullableText($input['recommendations'] ?? null),
            'inspector_signatures' => $signatures === [] ? null : $signatures,
            'operator_name' => $operatorName !== '' ? $operatorName : null,
            'operator_signed_at' => $operatorSignedAt,
            'stamp_acknowledged' => filter_var($input['stamp_acknowledged'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertSubmitRequirements(array $attributes): void
    {
        $errors = [];

        $signedInspectors = collect(Arr::wrap($attributes['inspector_signatures'] ?? []))
            ->filter(fn (array $row) => ($row['name'] ?? '') !== '' && ($row['signed_at'] ?? '') !== '');

        if ($signedInspectors->isEmpty()) {
            $errors['inspector_signatures'] = __('At least one inspector must sign before submitting.');
        }

        if (($attributes['operator_name'] ?? '') === '') {
            $errors['operator_name'] = __('Slaughterhouse operator name is required before submitting.');
        }

        if ($attributes['operator_signed_at'] === null) {
            $errors['operator_attest'] = __('Slaughterhouse operator attestation is required before submitting.');
        }

        if (! ($attributes['stamp_acknowledged'] ?? false)) {
            $errors['stamp_acknowledged'] = __('Confirm the slaughterhouse stamp before submitting.');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
