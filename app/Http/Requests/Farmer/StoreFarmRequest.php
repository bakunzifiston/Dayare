<?php

namespace App\Http\Requests\Farmer;

use App\Http\Requests\Farmer\Concerns\ValidatesFarmRegistration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFarmRequest extends FormRequest
{
    use ValidatesFarmRegistration;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $farmerIds = $this->user()->accessibleFarmerBusinessIds()->all();

        return array_merge($this->farmRegistrationRules(), [
            'business_id' => ['required', 'integer', Rule::in($farmerIds)],
        ]);
    }
}
