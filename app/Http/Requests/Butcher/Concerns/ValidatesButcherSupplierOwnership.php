<?php

namespace App\Http\Requests\Butcher\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesButcherSupplierOwnership
{
  /**
   * @return array<int, mixed>
   */
    protected function activeSupplierRule(int $businessId): array
    {
        return [
            'required',
            'integer',
            Rule::exists('butcher_suppliers', 'id')->where(function ($query) use ($businessId) {
                $query->where('business_id', $businessId)
                    ->where('is_active', true);
            }),
        ];
    }
}
