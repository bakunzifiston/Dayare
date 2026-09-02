<?php

namespace App\Http\Requests\SalesCompliance;

use App\Support\SalesComplianceCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSalesComplianceSiteRequest extends FormRequest
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
            'site_type' => ['required', Rule::in(SalesComplianceCatalog::SITE_TYPES)],
            'name' => ['required', 'string', 'max:160'],
            'location_address' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'event_type' => ['nullable', 'string', 'max:80'],
            'event_name' => ['nullable', 'string', 'max:160'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('site_type');
            if ($type === SalesComplianceCatalog::SITE_PRIVATE_EVENT) {
                if (! filled($this->input('event_type')) && ! filled($this->input('event_name'))) {
                    $validator->errors()->add('event_type', __('Enter the event type or event name (for example wedding).'));
                }
            }
            if (SalesComplianceCatalog::contactRequired($type) && ! filled($this->input('contact_name'))) {
                $validator->errors()->add('contact_name', __('A contact person is required for this site type.'));
            }
        });
    }
}
