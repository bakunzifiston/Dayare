<?php

namespace App\Http\Requests\SalesCompliance;

use App\Models\SalesComplianceSite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSalesComplianceInspectionRequest extends FormRequest
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
        return [
            'site_id' => ['required', 'integer'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'assignee' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $time = $this->input('scheduled_time');
        if (is_string($time) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $time) === 1) {
            $this->merge(['scheduled_time' => substr($time, 0, 5)]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $businessId = $this->user()?->activeProcessorBusinessId();
            $site = SalesComplianceSite::query()->whereKey($this->input('site_id'))->first();
            if (! $site || (int) $site->business_id !== (int) $businessId) {
                $validator->errors()->add('site_id', __('Select a valid site.'));
            }
            $assignee = (string) $this->input('assignee', '');
            if ($assignee !== '' && ! str_starts_with($assignee, 'inspector:') && ! str_starts_with($assignee, 'user:')) {
                $validator->errors()->add('assignee', __('Select an inspector.'));
            }
        });
    }
}
