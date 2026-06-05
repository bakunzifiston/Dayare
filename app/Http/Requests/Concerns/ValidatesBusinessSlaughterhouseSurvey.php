<?php

namespace App\Http\Requests\Concerns;

use App\Models\Business;
use Illuminate\Validation\Rule;

trait ValidatesBusinessSlaughterhouseSurvey
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function slaughterhouseSurveyRules(): array
    {
        return [
            'total_members' => ['nullable', 'integer', 'min:0'],
            'female_members' => ['nullable', 'integer', 'min:0'],
            'members_18_35' => ['nullable', 'integer', 'min:0'],
            'young_women_members' => ['nullable', 'integer', 'min:0'],
            'animals_processed' => ['nullable', 'array'],
            'animals_processed.*' => ['string', Rule::in(Business::ANIMALS_PROCESSED)],
            'animals_processed_other' => ['nullable', 'string', 'max:255'],
            'daily_processing' => ['nullable', 'array'],
            'daily_processing.*.species' => ['nullable', 'string', 'max:64'],
            'daily_processing.*.number' => ['nullable', 'integer', 'min:0'],
            'daily_processing.*.quantity_kg' => ['nullable', 'numeric', 'min:0'],
            'products_sold' => ['nullable', 'array'],
            'products_sold.*' => ['string', Rule::in(Business::PRODUCTS_SOLD)],
            'products_sold_other' => ['nullable', 'string', 'max:255'],
            'customer_segments' => ['nullable', 'array'],
            'customer_segments.*' => ['string', Rule::in(Business::CUSTOMER_SEGMENTS)],
            'customer_segments_other' => ['nullable', 'string', 'max:255'],
            'daily_sales_kg' => ['nullable', 'array'],
            'daily_sales_kg.*.species' => ['nullable', 'string', 'max:64'],
            'daily_sales_kg.*.quantity_kg' => ['nullable', 'numeric', 'min:0'],
            'buyer_count' => ['nullable', 'integer', 'min:0'],
            'contract_type' => ['nullable', 'string', Rule::in(Business::CONTRACT_TYPES)],
            'contracted_buyers' => ['nullable', 'string', 'max:5000'],
            'digital_marketplace' => ['nullable', 'boolean'],
            'digital_marketplace_name' => ['nullable', 'string', 'max:255'],
            'baseline_revenue_rwf' => ['nullable', 'numeric', 'min:0'],
            'has_receiving_area' => ['nullable', 'boolean'],
            'road_condition' => ['nullable', 'string', Rule::in(Business::ROAD_CONDITIONS)],
            'has_potable_water' => ['nullable', 'boolean'],
            'waste_system' => ['nullable', 'string', Rule::in(Business::WASTE_SYSTEMS)],
            'has_cold_storage' => ['nullable', 'boolean'],
            'cold_storage_capacity_kg' => ['nullable', 'integer', 'min:0'],
            'sanitary_certificate' => ['nullable', 'boolean'],
            'sanitary_certificate_expiry' => ['nullable', 'date'],
            'waste_disposal_plan' => ['nullable', 'boolean'],
            'has_sops' => ['nullable', 'boolean'],
            'workers_trained' => ['nullable', 'boolean'],
            'total_employees' => ['nullable', 'integer', 'min:0'],
            'female_employees' => ['nullable', 'integer', 'min:0'],
            'employees_18_35' => ['nullable', 'integer', 'min:0'],
            'female_employees_18_35' => ['nullable', 'integer', 'min:0'],
            'pwd_employees' => ['nullable', 'integer', 'min:0'],
            'refugee_employees' => ['nullable', 'integer', 'min:0'],
            'seasonal_workers' => ['nullable', 'integer', 'min:0'],
            'has_dedicated_manager' => ['nullable', 'string', Rule::in(Business::DEDICATED_MANAGER_OPTIONS)],
            'manager_first_name' => ['nullable', 'string', 'max:255'],
            'manager_gender' => ['nullable', 'string', Rule::in(Business::OWNER_GENDERS)],
            'manager_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'bank_account' => ['nullable', 'string', Rule::in(Business::BANK_ACCOUNT_TYPES)],
            'uses_mobile_money' => ['nullable', 'string', Rule::in(Business::MOBILE_MONEY_USAGE)],
            'digital_payment_willingness' => ['nullable', 'string', Rule::in(Business::DIGITAL_PAYMENT_WILLINGNESS)],
            'uses_digital_records' => ['nullable', 'boolean'],
            'digital_system_name' => ['nullable', 'string', 'max:255'],
            'digital_devices' => ['nullable', 'array'],
            'digital_devices.*' => ['string', Rule::in(Business::DIGITAL_DEVICES)],
            'network_connectivity' => ['nullable', 'string', Rule::in(Business::NETWORK_CONNECTIVITY)],
            'digital_ledger_willingness' => ['nullable', 'string', Rule::in(Business::DIGITAL_LEDGER_WILLINGNESS)],
            'supporting_documents' => ['nullable', 'array'],
            'supporting_documents.*' => ['string', Rule::in(Business::SUPPORTING_DOCUMENTS)],
            'supporting_documents_other' => ['nullable', 'string', 'max:255'],
            'document_uploads' => ['nullable', 'array'],
            'document_uploads.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @var list<string>
     */
    private const OPTIONAL_COUNT_FIELDS = [
        'total_members',
        'female_members',
        'members_18_35',
        'young_women_members',
        'buyer_count',
        'cold_storage_capacity_kg',
        'total_employees',
        'female_employees',
        'employees_18_35',
        'female_employees_18_35',
        'pwd_employees',
        'refugee_employees',
        'seasonal_workers',
        'manager_age',
    ];

    protected function prepareSlaughterhouseSurveyForValidation(): void
    {
        foreach (self::OPTIONAL_COUNT_FIELDS as $field) {
            $value = $this->input($field);
            if ($value === '' || $value === null) {
                $this->merge([$field => 0]);
            }
        }

        $nullableBooleans = [
            'digital_marketplace', 'has_receiving_area', 'has_potable_water', 'has_cold_storage',
            'sanitary_certificate', 'waste_disposal_plan', 'has_sops', 'workers_trained', 'uses_digital_records',
        ];

        foreach ($nullableBooleans as $key) {
            $value = $this->input($key);
            if ($value === '' || $value === null) {
                $this->merge([$key => null]);

                continue;
            }
            $this->merge([$key => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
        }

        if ($this->input('baseline_revenue_rwf') === '') {
            $this->merge(['baseline_revenue_rwf' => null]);
        }

        foreach (['animals_processed', 'products_sold', 'customer_segments', 'digital_devices', 'supporting_documents'] as $arrayKey) {
            $value = $this->input($arrayKey);
            if ($value === null || $value === '') {
                $this->merge([$arrayKey => null]);
            }
        }

        $this->merge([
            'daily_processing' => $this->filterRepeatingRows($this->input('daily_processing', [])),
            'daily_sales_kg' => $this->filterRepeatingRows($this->input('daily_sales_kg', []), includeNumber: false),
        ]);
    }

    /**
     * @param  mixed  $rows
     * @return list<array<string, mixed>>|null
     */
    private function filterRepeatingRows(mixed $rows, bool $includeNumber = true): ?array
    {
        if (! is_array($rows)) {
            return null;
        }

        $filtered = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $species = trim((string) ($row['species'] ?? ''));
            $hasNumber = $includeNumber && ($row['number'] ?? '') !== '';
            $hasKg = ($row['quantity_kg'] ?? '') !== '';
            if ($species === '' && ! $hasNumber && ! $hasKg) {
                continue;
            }
            $entry = ['species' => $species];
            if ($includeNumber) {
                $entry['number'] = $row['number'] !== '' && $row['number'] !== null ? (int) $row['number'] : null;
            }
            $entry['quantity_kg'] = $row['quantity_kg'] !== '' && $row['quantity_kg'] !== null ? (float) $row['quantity_kg'] : null;
            $filtered[] = $entry;
        }

        return $filtered === [] ? null : $filtered;
    }
}
