<?php

namespace App\Http\Requests\Farmer;

use App\Http\Requests\Farmer\Concerns\ValidatesFarmRegistration;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFarmRequest extends FormRequest
{
    use ValidatesFarmRegistration;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->farmRegistrationRules();
    }
}
