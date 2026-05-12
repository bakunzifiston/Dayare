<?php

namespace App\Http\Requests\Farmer;

use App\Http\Requests\Farmer\Concerns\ValidatesLivestockGroup;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLivestockRequest extends FormRequest
{
    use ValidatesLivestockGroup;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->livestockGroupRules();
    }
}
