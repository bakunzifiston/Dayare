<?php

namespace App\Enums;

enum MeatExportDocumentType: string
{
    case VeterinaryHealthCertificate = 'veterinary_health_certificate';
    case CustomsExportPermit = 'customs_export_permit';
    case CommercialInvoice = 'commercial_invoice';
    case ColdChainLog = 'cold_chain_log';

    public const REQUIRED_TYPES = [
        self::VeterinaryHealthCertificate->value,
        self::CustomsExportPermit->value,
        self::CommercialInvoice->value,
        self::ColdChainLog->value,
    ];

    /** @return list<string> */
    public static function values(): array
    {
        return self::REQUIRED_TYPES;
    }

    public function label(): string
    {
        return match ($this) {
            self::VeterinaryHealthCertificate => __('Veterinary health certificate'),
            self::CustomsExportPermit => __('Customs / export permit'),
            self::CommercialInvoice => __('Commercial invoice & packing list'),
            self::ColdChainLog => __('Temperature / cold chain log'),
        };
    }

    public static function labelFor(string $type): string
    {
        return self::tryFrom($type)?->label() ?? $type;
    }
}
