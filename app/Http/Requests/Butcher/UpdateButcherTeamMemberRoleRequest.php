<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\BusinessUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateButcherTeamMemberRoleRequest extends FormRequest
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

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('business_user', 'user_id')->where('business_id', $business->id),
            ],
            'role' => ['required', 'string', Rule::in(BusinessUser::BUTCHER_ROLES)],
        ];
    }
}
