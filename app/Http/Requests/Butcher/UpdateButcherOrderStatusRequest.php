<?php

namespace App\Http\Requests\Butcher;

use App\Models\ButcherOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateButcherOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    ButcherOrder::STATUS_PENDING,
                    ButcherOrder::STATUS_CONFIRMED,
                    ButcherOrder::STATUS_READY,
                    ButcherOrder::STATUS_CANCELLED,
                ]),
            ],
        ];
    }
}
