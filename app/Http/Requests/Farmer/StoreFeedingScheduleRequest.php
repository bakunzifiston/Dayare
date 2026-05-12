<?php

namespace App\Http\Requests\Farmer;

use App\Models\FeedingSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedingScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'schedule_name' => ['required', 'string', 'max:255'],
            'target_type' => ['required', 'string', Rule::in(['animal', 'livestock'])],
            'animal_id' => ['nullable', 'integer', 'exists:animals,id', 'required_if:target_type,animal'],
            'livestock_id' => ['nullable', 'integer', 'exists:livestock,id', 'required_if:target_type,livestock'],
            'feed_type_id' => ['required', 'integer', 'exists:feed_types,id'],
            'feeding_time' => ['required', 'date_format:H:i'],
            'feeding_frequency' => ['required', 'string', Rule::in(FeedingSchedule::FREQUENCIES)],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', Rule::in(FeedingSchedule::STATUSES)],
        ];
    }
}
