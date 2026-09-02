<?php

namespace App\Http\Requests\SalesCompliance;

use App\Models\SalesComplianceCertificateRule;
use App\Support\SalesComplianceCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RecordSalesComplianceInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $passFail = SalesComplianceCatalog::PASS_FAIL;
        $presentMissing = SalesComplianceCatalog::PRESENT_MISSING;

        return [
            'meat_source' => ['nullable', Rule::in(SalesComplianceCatalog::MEAT_SOURCES)],
            'inspector_notes' => ['nullable', 'string', 'max:5000'],
            'responses' => ['nullable', 'array'],
            'responses.*.result' => ['nullable', 'string', Rule::in(array_merge($passFail, $presentMissing))],
            'responses.*.notes' => ['nullable', 'string', 'max:2000'],
            'product_lines' => ['nullable', 'array'],
            'product_lines.*.product_name' => ['nullable', 'string', 'max:160'],
            'product_lines.*.quantity_description' => ['nullable', 'string', 'max:120'],
            'product_lines.*.certificate_status' => ['nullable', Rule::in($presentMissing)],
            'attachments' => ['nullable', 'array', 'max:12'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var \App\Models\SalesComplianceInspection|null $inspection */
            $inspection = $this->route('inspection');
            if (! $inspection) {
                return;
            }
            $inspection->loadMissing('site');
            $siteType = $inspection->site->site_type;
            $meatSource = $this->input('meat_source') ?: $inspection->meat_source;
            $certRequired = in_array($siteType, [
                SalesComplianceCatalog::SITE_RESTAURANT,
                SalesComplianceCatalog::SITE_BUTCHERY,
                SalesComplianceCatalog::SITE_PRIVATE_EVENT,
            ], true) && SalesComplianceCertificateRule::isCertificateRequired(
                (int) $inspection->business_id,
                $siteType,
                $meatSource
            );

            foreach (SalesComplianceCatalog::checklistItems($siteType) as $item) {
                if ($item['certificate'] && ! $certRequired) {
                    continue;
                }
                $result = data_get($this->input('responses'), $item['key'].'.result');
                if (! filled($result) || $result === SalesComplianceCatalog::RESULT_NA) {
                    $validator->errors()->add('responses.'.$item['key'].'.result', __('Complete :item.', ['item' => $item['label']]));
                }
            }

            if (in_array($siteType, [SalesComplianceCatalog::SITE_BUTCHERY, SalesComplianceCatalog::SITE_PRIVATE_EVENT], true)) {
                $lines = collect($this->input('product_lines', []))->filter(fn ($line) => filled($line['product_name'] ?? null));
                if ($lines->isEmpty()) {
                    $validator->errors()->add('product_lines', __('Record at least one meat product.'));
                }
            }

            if ($siteType === SalesComplianceCatalog::SITE_RESTAURANT && $certRequired && ! filled($meatSource)) {
                $validator->errors()->add('meat_source', __('Select the meat source to apply the certificate rule.'));
            }
        });
    }
}
