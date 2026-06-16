<?php

namespace App\Http\Requests\Concerns;

use App\Models\Certificate;
use App\Services\Processor\CertificateTransportDefaultsService;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesTransportTripAgainstCertificate
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $certificateId = $this->input('certificate_id');
            if ($certificateId === null || $certificateId === '') {
                return;
            }

            $certificate = Certificate::query()->find($certificateId);
            if ($certificate === null) {
                return;
            }

            $locked = app(CertificateTransportDefaultsService::class)->lockedTripFields($certificate);
            foreach ($locked as $field => $expected) {
                $submitted = $this->input($field);
                if ($submitted === null || trim((string) $submitted) === '') {
                    continue;
                }

                if (trim((string) $submitted) !== $expected) {
                    $validator->errors()->add(
                        $field,
                        __('This must match the transporter details recorded on the certificate.')
                    );
                }
            }
        });
    }
}
