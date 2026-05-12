<?php

namespace App\Http\Requests\Farmer;

use App\Models\FeedType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'feed_name' => ['required', 'string', 'max:255'],
            'feed_category' => ['required', 'string', Rule::in(FeedType::CATEGORIES)],
            'feed_form' => ['required', 'string', Rule::in(FeedType::FORMS)],
            'unit' => ['required', 'string', 'max:32'],
            'protein_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'energy_value' => ['nullable', 'numeric', 'min:0'],
            'nutritional_value' => ['nullable', 'string', 'max:5000'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', Rule::in(FeedType::STATUSES)],
        ];
    }
}
