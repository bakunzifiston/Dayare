<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCertificateIssue;
use App\Models\Batch;
use App\Support\CertificateAnimalSelection;
use App\Support\CertificatePdfDetails;
use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateRequest extends FormRequest
{
    use ValidatesCertificateIssue;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slaughter_execution_id' => ['nullable', 'required_without:batch_id', 'exists:slaughter_executions,id'],
            'batch_id' => ['nullable', 'required_without:slaughter_execution_id', 'exists:batches,id'],
            ...$this->certificateIssueRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'pdf_details' => CertificatePdfDetails::normalize($this->input('pdf_details')),
        ];

        if ($this->filled('slaughter_execution_id') && ! $this->filled('batch_id')) {
            $batch = Batch::certifiableForExecution(
                (int) $this->input('slaughter_execution_id'),
                $this->input('animal_intake_item_ids'),
            );
            if ($batch) {
                $merge['batch_id'] = $batch->id;
            }
        }

        if ($this->filled('batch_id') && ! $this->filled('animal_intake_item_ids')) {
            $batch = Batch::find($this->input('batch_id'));
            if ($batch?->hasPerAnimalData()) {
                $merge['animal_intake_item_ids'] = CertificateAnimalSelection::certifiableAnimals($batch)
                    ->pluck('animal_intake_item_id')
                    ->all();
            }
        }

        if ($this->filled('animal_intake_item_ids')) {
            $merge['animal_intake_item_ids'] = collect($this->input('animal_intake_item_ids'))
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        $this->merge($merge);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('slaughter_execution_id') && ! $this->filled('batch_id')) {
                $validator->errors()->add(
                    'slaughter_execution_id',
                    __('This slaughter execution is not ready for certification yet.')
                );
            }
        });

        $this->registerCertificateIssueValidation($validator, isCreate: true);
    }
}
