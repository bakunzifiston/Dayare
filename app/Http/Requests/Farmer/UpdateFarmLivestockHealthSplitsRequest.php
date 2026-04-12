<?php

namespace App\Http\Requests\Farmer;

use App\Models\Farm;
use App\Models\Livestock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateFarmLivestockHealthSplitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'splits' => ['required', 'array'],
            'splits.*.healthy' => ['required', 'integer', 'min:0'],
            'splits.*.sick' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Farm $farm */
            $farm = $this->route('farm');
            $validIds = $farm->livestock()->pluck('id')->all();

            foreach ($this->input('splits', []) as $livestockId => $row) {
                $livestockId = (int) $livestockId;
                if (! in_array($livestockId, $validIds, true)) {
                    $validator->errors()->add('splits', __('Invalid livestock selection.'));

                    return;
                }

                $livestock = Livestock::query()->where('farm_id', $farm->id)->find($livestockId);
                if (! $livestock) {
                    continue;
                }

                $h = (int) ($row['healthy'] ?? 0);
                $s = (int) ($row['sick'] ?? 0);
                if ($h + $s !== (int) $livestock->total_quantity) {
                    $validator->errors()->add(
                        'splits.'.$livestockId,
                        __('Healthy plus sick must equal total (:total) for this row.', ['total' => $livestock->total_quantity])
                    );
                }
            }
        });
    }
}
