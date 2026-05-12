<?php

namespace App\Services\Farmer;

use App\Models\Animal;
use App\Models\AnimalCertificate;
use App\Models\AnimalCertificateLog;
use App\Models\AnimalCertificateTemplate;
use Illuminate\Support\Str;

class AnimalCertificateService
{
    public function __construct(
        private readonly AnimalCertificatePdfService $pdfService,
    ) {}

    public function generateCertificateNumber(): string
    {
        do {
            $number = 'AC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (AnimalCertificate::withTrashed()->where('certificate_number', $number)->exists());

        return $number;
    }

    public function generateVerificationToken(): string
    {
        do {
            $token = Str::lower(Str::random(40));
        } while (
            AnimalCertificate::withTrashed()->where('verification_token', $token)->exists()
            || Animal::withTrashed()->where('public_verification_token', $token)->exists()
        );

        return $token;
    }

    public function ensureAnimalVerificationToken(Animal $animal): Animal
    {
        if ($animal->public_verification_token) {
            return $animal;
        }

        $animal->update(['public_verification_token' => $this->generateVerificationToken()]);

        return $animal->fresh();
    }

    public function digitalSignature(AnimalCertificate $certificate): string
    {
        $payload = implode('|', [
            $certificate->certificate_number,
            $certificate->animal_id,
            $certificate->certificate_type,
            $certificate->issue_date?->toDateString(),
            $certificate->expiry_date?->toDateString(),
        ]);

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    public function log(
        AnimalCertificate $certificate,
        string $action,
        ?int $userId = null,
        ?string $ip = null,
        ?string $notes = null,
    ): AnimalCertificateLog {
        return $certificate->logs()->create([
            'action_type' => $action,
            'action_by' => $userId,
            'action_date' => now(),
            'ip_address' => $ip,
            'notes' => $notes,
        ]);
    }

    public function issue(AnimalCertificate $certificate, ?int $userId = null): AnimalCertificate
    {
        $this->ensureAnimalVerificationToken($certificate->animal);
        $certificate->update([
            'certificate_status' => AnimalCertificate::STATUS_ACTIVE,
            'digital_signature' => $this->digitalSignature($certificate),
            'qr_code' => $certificate->verificationUrl(),
        ]);

        $this->pdfService->generate($certificate);
        $this->log($certificate, AnimalCertificateLog::ACTION_CREATED, $userId, null, __('Certificate activated.'));

        return $certificate->fresh();
    }

    public function titleForType(string $type): string
    {
        return match ($type) {
            AnimalCertificate::TYPE_OWNERSHIP => __('Animal Ownership Certificate'),
            AnimalCertificate::TYPE_HEALTH => __('Animal Health Certificate'),
            AnimalCertificate::TYPE_TRACEABILITY => __('Animal Traceability Certificate'),
            AnimalCertificate::TYPE_TRANSFER => __('Animal Transfer Certificate'),
            default => __('Animal Certificate'),
        };
    }

    public function resolveTemplate(?int $templateId, int $businessId, string $type): ?AnimalCertificateTemplate
    {
        if ($templateId) {
            return AnimalCertificateTemplate::query()
                ->whereKey($templateId)
                ->where('business_id', $businessId)
                ->first();
        }

        return AnimalCertificateTemplate::query()
            ->where('business_id', $businessId)
            ->where('certificate_type', $type)
            ->where('status', AnimalCertificateTemplate::STATUS_ACTIVE)
            ->where('is_default', true)
            ->first();
    }
}
