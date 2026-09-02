<?php

namespace App\Services\SalesCompliance;

use App\Models\Inspector;
use App\Models\SalesComplianceCertificateRule;
use App\Models\SalesComplianceInspection;
use App\Models\User;
use App\Support\SalesComplianceCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class SalesComplianceInspectionService
{
    public function certificateRequired(SalesComplianceInspection $inspection): bool
    {
        $siteType = $inspection->site?->site_type;
        if (! $siteType) {
            return false;
        }

        if ($siteType === SalesComplianceCatalog::SITE_BAR) {
            return false;
        }

        return SalesComplianceCertificateRule::isCertificateRequired(
            (int) $inspection->business_id,
            $siteType,
            $inspection->meat_source
        );
    }

    /**
     * @param  array<string, array{result?: string, notes?: string|null}>  $responses
     * @param  array<int, array{product_name?: string, quantity_description?: string|null, certificate_status?: string}>  $productLines
     * @param  list<UploadedFile>  $files
     */
    public function recordChecklist(
        SalesComplianceInspection $inspection,
        array $responses,
        array $productLines,
        array $files,
        int $userId,
        ?string $meatSource,
        ?string $notes,
    ): SalesComplianceInspection {
        $inspection->meat_source = $meatSource ?: $inspection->meat_source;
        $inspection->inspector_notes = $notes;
        $inspection->updated_by = $userId;
        $inspection->save();

        $siteType = $inspection->site->site_type;
        $requiredCert = $this->certificateRequired($inspection);

        foreach (SalesComplianceCatalog::checklistItems($siteType) as $item) {
            if ($item['certificate'] && ! $requiredCert) {
                $inspection->responses()->updateOrCreate(
                    ['item_key' => $item['key']],
                    ['result' => SalesComplianceCatalog::RESULT_NA, 'notes' => $responses[$item['key']]['notes'] ?? null],
                );
                continue;
            }

            $row = $responses[$item['key']] ?? [];
            $inspection->responses()->updateOrCreate(
                ['item_key' => $item['key']],
                [
                    'result' => $row['result'] ?? ($item['kind'] === SalesComplianceCatalog::KIND_PASS_FAIL
                        ? SalesComplianceCatalog::RESULT_FAIL
                        : SalesComplianceCatalog::RESULT_MISSING),
                    'notes' => $row['notes'] ?? null,
                ],
            );
        }

        $inspection->productLines()->delete();
        $order = 0;
        foreach ($productLines as $line) {
            $name = trim((string) ($line['product_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $status = $line['certificate_status'] ?? ($requiredCert
                ? SalesComplianceCatalog::RESULT_MISSING
                : SalesComplianceCatalog::RESULT_NA);
            if (! $requiredCert) {
                $status = SalesComplianceCatalog::RESULT_NA;
            }
            $inspection->productLines()->create([
                'product_name' => $name,
                'quantity_description' => $line['quantity_description'] ?? null,
                'certificate_status' => $status,
                'sort_order' => $order++,
            ]);
        }

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $path = $file->store('sales-compliance/'.$inspection->id, 'local');
            $inspection->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime' => $file->getClientMimeType(),
                'size' => (int) $file->getSize(),
                'uploaded_by' => $userId,
            ]);
        }

        $inspection->status = $this->computeStatus($inspection->fresh(['responses', 'productLines', 'site']));
        $inspection->completed_at = now();
        $inspection->updated_by = $userId;
        $inspection->save();

        return $inspection->fresh(['responses', 'productLines', 'attachments', 'site']);
    }

    public function computeStatus(SalesComplianceInspection $inspection): string
    {
        $failed = $inspection->responses->contains(function ($response): bool {
            return in_array($response->result, [
                SalesComplianceCatalog::RESULT_FAIL,
                SalesComplianceCatalog::RESULT_MISSING,
            ], true);
        });

        if (! $failed && $this->certificateRequired($inspection)) {
            $failed = $inspection->productLines->contains(
                fn ($line) => $line->certificate_status === SalesComplianceCatalog::RESULT_MISSING
            );
        }

        return $failed
            ? SalesComplianceCatalog::STATUS_FAILED
            : SalesComplianceCatalog::STATUS_PASSED;
    }

    /**
     * @return Collection<int, Inspector>
     */
    public function businessInspectors(int $businessId): Collection
    {
        return Inspector::query()
            ->whereHas('facility', fn ($q) => $q->where('business_id', $businessId))
            ->where('status', Inspector::STATUS_ACTIVE)
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function inspectorRoleUsers(int $businessId): Collection
    {
        return User::query()
            ->whereHas('memberBusinesses', function ($q) use ($businessId): void {
                $q->where('businesses.id', $businessId)
                    ->where('business_user.role', \App\Models\BusinessUser::ROLE_INSPECTOR);
            })
            ->orderBy('name')
            ->get();
    }

    public function storeAttachmentPath(UploadedFile $file, int $inspectionId): string
    {
        return $file->store('sales-compliance/'.$inspectionId, 'local');
    }

    public function deleteAttachment(SalesComplianceInspection $inspection, int $attachmentId): void
    {
        $attachment = $inspection->attachments()->whereKey($attachmentId)->first();
        if (! $attachment) {
            return;
        }
        if ($attachment->existsOnDisk()) {
            Storage::disk('local')->delete($attachment->path);
        }
        $attachment->delete();
    }
}
