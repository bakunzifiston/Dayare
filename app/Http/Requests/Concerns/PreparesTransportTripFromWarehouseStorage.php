<?php

namespace App\Http\Requests\Concerns;

use App\Models\WarehouseStorage;

trait PreparesTransportTripFromWarehouseStorage
{
    protected function prepareForValidation(): void
    {
        $storageId = $this->input('warehouse_storage_id');
        if ($storageId === null || $storageId === '' || $storageId === 'manual') {
            $this->merge([
                'warehouse_storage_id' => null,
                'require_released_storage' => false,
            ]);

            return;
        }

        $storage = WarehouseStorage::query()->find($storageId);
        if (! $storage) {
            return;
        }

        $this->merge([
            'certificate_id' => $storage->certificate_id,
            'batch_id' => $storage->batch_id,
        ]);
    }
}
