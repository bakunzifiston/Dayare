<?php

namespace App\Http\Requests\Concerns;

use App\Models\Certificate;
use App\Services\Processor\CertificateTransportDefaultsService;

trait PreparesTransportTripFromCertificate
{
    protected function prepareForValidation(): void
    {
        $certificateId = $this->input('certificate_id');
        $merge = ['warehouse_storage_id' => null];

        if ($certificateId === null || $certificateId === '') {
            $this->merge($merge);

            return;
        }

        $certificate = Certificate::query()->find($certificateId);
        if ($certificate === null) {
            $this->merge($merge);

            return;
        }

        $merge['batch_id'] = $certificate->batch_id;

        if (! $this->filled('origin_facility_id') && $certificate->facility_id !== null) {
            $merge['origin_facility_id'] = $certificate->facility_id;
        }

        $defaultsService = app(CertificateTransportDefaultsService::class);
        $suggested = $defaultsService->suggestedForCertificate($certificate);

        foreach ([
            'vehicle_plate_number',
            'driver_name',
            'driver_phone',
        ] as $field) {
            if (! $this->filled($field) && $suggested[$field] !== null) {
                $merge[$field] = $suggested[$field];
            }
        }

        foreach ($defaultsService->lockedTripFields($certificate) as $field => $value) {
            if (! $this->filled($field)) {
                $merge[$field] = $value;
            }
        }

        $this->merge($merge);
    }
}
