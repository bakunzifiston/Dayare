<?php

namespace App\Rules;

use App\Models\AdministrativeDivision;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RwandaDistrictName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = trim((string) $value);
        if ($name === '') {
            return;
        }

        $exists = AdministrativeDivision::query()
            ->where('type', AdministrativeDivision::TYPE_DISTRICT)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if (! $exists) {
            $fail(__('The :attribute must be a valid Rwanda district.', ['attribute' => $attribute]));
        }
    }
}
