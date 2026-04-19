<?php

declare(strict_types=1);

namespace App\Http\Requests\Logistics;

use App\Models\LogisticsCompany;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends StoreCompanyRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        /** @var LogisticsCompany $company */
        $company = $this->route('logistics_company');
        $rules['registration_number'] = [
            'required',
            'string',
            'max:120',
            Rule::unique('logistics_companies', 'registration_number')->ignore($company->id),
        ];

        return $rules;
    }
}
