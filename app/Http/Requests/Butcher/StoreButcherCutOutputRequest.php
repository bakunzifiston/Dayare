<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherCuttingSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherCutOutputRequest extends FormRequest
{
    use ResolvesButcherBusiness;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $business = $this->butcherBusiness();
        $session = $this->route('session');

        return [
            'cut_type_id' => [
                'required',
                'integer',
                Rule::exists('butcher_cut_types', 'id')->where(function ($query) use ($business) {
                    $query->where('business_id', $business->id)->where('is_active', true);
                }),
            ],
            'weight_kg' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $session = $this->route('session');
        if ($session instanceof ButcherCuttingSession) {
            abort_unless((int) $session->business_id === (int) $this->butcherBusiness()->id, 404);
        }
    }
}
