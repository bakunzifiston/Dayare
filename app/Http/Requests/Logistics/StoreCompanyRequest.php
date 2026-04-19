<?php

declare(strict_types=1);

namespace App\Http\Requests\Logistics;

use App\Models\Business;
use App\Models\LogisticsCompany;
use App\Support\AdministrativeDivisionHierarchy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawType = $this->input('company_type');
        $type = ($rawType === null || trim((string) $rawType) === '')
            ? LogisticsCompany::TYPE_INDIVIDUAL
            : Str::lower(trim((string) $rawType));
        if (! in_array($type, LogisticsCompany::COMPANY_TYPES, true)) {
            $type = LogisticsCompany::TYPE_INDIVIDUAL;
        }
        $this->merge(['company_type' => $type]);

        if ($type === LogisticsCompany::TYPE_INDIVIDUAL) {
            $this->merge(['members' => []]);

            return;
        }

        $members = $this->input('members');
        if (! is_array($members)) {
            $this->merge(['members' => []]);

            return;
        }

        $normalized = [];
        foreach ($members as $row) {
            if (! is_array($row)) {
                continue;
            }
            $normalized[] = [
                'first_name' => trim((string) ($row['first_name'] ?? '')),
                'last_name' => trim((string) ($row['last_name'] ?? '')),
                'phone' => trim((string) ($row['phone'] ?? '')),
                'email' => Str::lower(trim((string) ($row['email'] ?? ''))),
            ];
        }

        $normalized = array_values(array_filter(
            $normalized,
            static fn (array $r) => $r['first_name'] !== '' || $r['last_name'] !== '' || $r['phone'] !== '' || $r['email'] !== ''
        ));

        $this->merge(['members' => $normalized]);
    }

    public function rules(): array
    {
        $isShared = $this->input('company_type') === LogisticsCompany::TYPE_SHARED_COMPANY;

        return [
            'business_id' => [
                'required',
                'integer',
                Rule::exists('businesses', 'id')->where(function ($q) {
                    $q->where('type', Business::TYPE_LOGISTICS)
                        ->whereIn('id', $this->user()->accessibleBusinessIds());
                }),
            ],
            'company_type' => ['required', 'string', Rule::in(LogisticsCompany::COMPANY_TYPES)],
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:120', 'unique:logistics_companies,registration_number'],
            'tax_id' => ['nullable', 'string', 'max:120'],
            'license_type' => ['required', 'string', 'max:120'],
            'license_expiry_date' => ['required', 'date'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'country_id' => ['required', 'integer', Rule::exists('administrative_divisions', 'id')],
            'province_id' => ['required', 'integer', Rule::exists('administrative_divisions', 'id')],
            'district_id' => ['required', 'integer', Rule::exists('administrative_divisions', 'id')],
            'sector_id' => ['required', 'integer', Rule::exists('administrative_divisions', 'id')],
            'cell_id' => ['required', 'integer', Rule::exists('administrative_divisions', 'id')],
            'village_id' => ['required', 'integer', Rule::exists('administrative_divisions', 'id')],
            'members' => $isShared ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'members.*.first_name' => [
                Rule::requiredIf($isShared),
                'string',
                'max:255',
            ],
            'members.*.last_name' => [
                Rule::requiredIf($isShared),
                'string',
                'max:255',
            ],
            'members.*.phone' => [
                Rule::requiredIf($isShared),
                'string',
                'max:50',
            ],
            'members.*.email' => [
                Rule::requiredIf($isShared),
                'email',
                'max:255',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $countryId = (int) $this->input('country_id');
            $provinceId = (int) $this->input('province_id');
            $districtId = (int) $this->input('district_id');
            $sectorId = (int) $this->input('sector_id');
            $cellId = (int) $this->input('cell_id');
            $villageId = (int) $this->input('village_id');

            if (! AdministrativeDivisionHierarchy::isValidChain($countryId, $provinceId, $districtId, $sectorId, $cellId, $villageId)) {
                $validator->errors()->add('country_id', __('The selected location does not form a valid administrative hierarchy.'));
            }

            if ($this->input('company_type') !== LogisticsCompany::TYPE_SHARED_COMPANY) {
                return;
            }

            $members = $this->input('members', []);
            if (! is_array($members)) {
                return;
            }

            $phones = array_column($members, 'phone');
            $emails = array_column($members, 'email');
            if (count($phones) !== count(array_unique($phones))) {
                $validator->errors()->add('members', __('Each member must have a unique phone number.'));
            }
            if (count($emails) !== count(array_unique($emails))) {
                $validator->errors()->add('members', __('Each member must have a unique email address.'));
            }
        });
    }
}
