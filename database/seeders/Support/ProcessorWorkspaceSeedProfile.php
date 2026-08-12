<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

/**
 * Configurable naming and volume defaults for {@see ProcessorWorkspaceSeedBuilder}.
 */
final class ProcessorWorkspaceSeedProfile
{
    public function __construct(
        public readonly string $regPrefix = 'PWS-RDB-',
        public readonly string $healthCertPrefix = 'PWS-AHC',
        public readonly string $certNumberPrefix = 'PWS-CERT',
        public readonly string $ownerEmailDomain = 'processor.rw',
        public readonly string $teamEmailDomain = 'processor.rw',
        public readonly string $businessEmailDomain = 'business.rw',
        public readonly string $earTagPrefix = 'PWS',
        public readonly bool $growthWeightedDates = false,
        public readonly float $growthExponent = 0.65,
        public readonly int $defaultIntakeCount = 15,
        public readonly int $defaultSupplierCount = 5,
        public readonly int $defaultClientCount = 6,
        public readonly int $defaultEmployeeCount = 8,
        public readonly int $defaultInspectorCount = 2,
        public readonly int $defaultDemandCount = 10,
        public readonly int $defaultActivityCount = 14,
        public readonly int $openIntakeSlots = 5,
        public readonly bool $seedButchery = false,
        public readonly bool $seedMonthlyReports = false,
        public readonly bool $mixClientIntakes = false,
        public readonly bool $appendBusinessIdToClientNames = true,
        public readonly bool $useRealisticContractPrefixes = false,
    ) {}

    public static function processorWorkspace(): self
    {
        return new self;
    }

    public static function processorWorkspaceWithMonthlyReports(): self
    {
        return new self(seedMonthlyReports: true);
    }

    public static function rwandaMeatProcessor(): self
    {
        return new self(
            regPrefix: 'RDB/NPM/2022/',
            healthCertPrefix: 'AHC-NPM',
            certNumberPrefix: 'CERT-NPM',
            ownerEmailDomain: 'nyagataprime.rw',
            teamEmailDomain: 'nyagataprime.rw',
            businessEmailDomain: 'nyagataprime.rw',
            earTagPrefix: 'NPM',
            growthWeightedDates: true,
            growthExponent: 0.62,
            defaultIntakeCount: 185,
            defaultSupplierCount: 28,
            defaultClientCount: 24,
            defaultEmployeeCount: 22,
            defaultInspectorCount: 4,
            defaultDemandCount: 48,
            defaultActivityCount: 36,
            openIntakeSlots: 12,
            seedButchery: true,
            seedMonthlyReports: true,
            mixClientIntakes: true,
            appendBusinessIdToClientNames: false,
            useRealisticContractPrefixes: true,
        );
    }

    public function ownerEmail(int $businessNumber, ?string $override = null): string
    {
        if ($override !== null && $override !== '') {
            return $override;
        }

        return "owner.pws.{$businessNumber}@{$this->ownerEmailDomain}";
    }

    public function teamEmail(int $businessNumber, int $index): string
    {
        return "team.pws.{$businessNumber}.{$index}@{$this->teamEmailDomain}";
    }
}
