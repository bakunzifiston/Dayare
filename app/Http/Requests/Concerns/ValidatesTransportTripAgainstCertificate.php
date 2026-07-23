<?php

namespace App\Http\Requests\Concerns;

use App\Models\Certificate;
use App\Services\Processor\CertificateTransportDefaultsService;
use App\Support\CertificatePdfDetails;
use Carbon\Carbon;
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

            $this->validateDepartureDateAgainstCertificate($validator, $certificate);

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

    protected function validateDepartureDateAgainstCertificate(Validator $validator, Certificate $certificate): void
    {
        $departureRaw = $this->input('departure_date');
        if ($departureRaw === null || $departureRaw === '') {
            return;
        }

        try {
            $departureDate = Carbon::parse($departureRaw)->startOfDay();
        } catch (\Throwable) {
            return;
        }

        if ($certificate->issued_at !== null
            && $departureDate->lt($certificate->issued_at->copy()->startOfDay())) {
            $validator->errors()->add(
                'departure_date',
                __('Departure date cannot be before the certificate issue date (:date).', [
                    'date' => $certificate->issued_at->format('d M Y'),
                ])
            );
        }

        if ($certificate->expiry_date !== null
            && $departureDate->gt($certificate->expiry_date->copy()->startOfDay())) {
            $validator->errors()->add(
                'departure_date',
                __('Departure date cannot be after the certificate expiry date (:date).', [
                    'date' => $certificate->expiry_date->format('d M Y'),
                ])
            );
        }

        $pdf = is_array($certificate->pdf_details) ? $certificate->pdf_details : [];
        $certificateDeparture = CertificatePdfDetails::departureTimeDate($pdf['departure_time'] ?? null);
        if ($certificateDeparture !== null && ! $departureDate->equalTo($certificateDeparture)) {
            $validator->errors()->add(
                'departure_date',
                __('Departure date must match the departure date on the certificate (:date).', [
                    'date' => $certificateDeparture->format('d M Y'),
                ])
            );
        }
    }
}
