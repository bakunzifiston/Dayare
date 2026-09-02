<?php

namespace App\Support;

class SalesComplianceCatalog
{
    public const SITE_RESTAURANT = 'restaurant';

    public const SITE_BAR = 'bar';

    public const SITE_BUTCHERY = 'butchery';

    public const SITE_PRIVATE_EVENT = 'private_event';

    /** @var list<string> */
    public const SITE_TYPES = [
        self::SITE_RESTAURANT,
        self::SITE_BAR,
        self::SITE_BUTCHERY,
        self::SITE_PRIVATE_EVENT,
    ];

    public const RESULT_PASS = 'pass';

    public const RESULT_FAIL = 'fail';

    public const RESULT_PRESENT = 'present';

    public const RESULT_MISSING = 'missing';

    public const RESULT_NA = 'not_applicable';

    /** @var list<string> */
    public const PASS_FAIL = [
        self::RESULT_PASS,
        self::RESULT_FAIL,
        self::RESULT_NA,
    ];

    /** @var list<string> */
    public const PRESENT_MISSING = [
        self::RESULT_PRESENT,
        self::RESULT_MISSING,
        self::RESULT_NA,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_PASSED = 'passed';

    public const STATUS_FAILED = 'failed';

    /** @var list<string> */
    public const INSPECTION_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PASSED,
        self::STATUS_FAILED,
    ];

    public const ESCALATION_OPEN = 'open';

    public const ESCALATION_IN_REVIEW = 'in_review';

    public const ESCALATION_RESOLVED = 'resolved';

    /** @var list<string> */
    public const ESCALATION_STATUSES = [
        self::ESCALATION_OPEN,
        self::ESCALATION_IN_REVIEW,
        self::ESCALATION_RESOLVED,
    ];

    public const MEAT_SOURCE_PROCESSOR = 'processor';

    public const MEAT_SOURCE_CERTIFIED_SUPPLIER = 'certified_supplier';

    public const MEAT_SOURCE_WET_MARKET = 'wet_market';

    public const MEAT_SOURCE_OWN_FARM = 'own_farm';

    public const MEAT_SOURCE_UNKNOWN = 'unknown';

    /** @var list<string> */
    public const MEAT_SOURCES = [
        self::MEAT_SOURCE_PROCESSOR,
        self::MEAT_SOURCE_CERTIFIED_SUPPLIER,
        self::MEAT_SOURCE_WET_MARKET,
        self::MEAT_SOURCE_OWN_FARM,
        self::MEAT_SOURCE_UNKNOWN,
    ];

    public const KIND_PASS_FAIL = 'pass_fail';

    public const KIND_PRESENT_MISSING = 'present_missing';

    /**
     * @return array<string, string>
     */
    public static function siteTypeLabels(): array
    {
        return [
            self::SITE_RESTAURANT => __('Restaurant'),
            self::SITE_BAR => __('Bar'),
            self::SITE_BUTCHERY => __('Butchery'),
            self::SITE_PRIVATE_EVENT => __('Individual / private event'),
        ];
    }

    public static function siteTypeLabel(string $type): string
    {
        return self::siteTypeLabels()[$type] ?? $type;
    }

    /**
     * @return array<string, string>
     */
    public static function meatSourceLabels(): array
    {
        return [
            self::MEAT_SOURCE_PROCESSOR => __('Processor / abattoir'),
            self::MEAT_SOURCE_CERTIFIED_SUPPLIER => __('Certified supplier'),
            self::MEAT_SOURCE_WET_MARKET => __('Wet market / informal'),
            self::MEAT_SOURCE_OWN_FARM => __('Own farm'),
            self::MEAT_SOURCE_UNKNOWN => __('Unknown / not declared'),
        ];
    }

    public static function meatSourceLabel(string $source): string
    {
        return self::meatSourceLabels()[$source] ?? $source;
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_PASSED => __('Passed'),
            self::STATUS_FAILED => __('Failed'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function escalationStatusLabels(): array
    {
        return [
            self::ESCALATION_OPEN => __('Open'),
            self::ESCALATION_IN_REVIEW => __('In review'),
            self::ESCALATION_RESOLVED => __('Resolved'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function resultLabels(): array
    {
        return [
            self::RESULT_PASS => __('Pass'),
            self::RESULT_FAIL => __('Fail'),
            self::RESULT_PRESENT => __('Present'),
            self::RESULT_MISSING => __('Missing'),
            self::RESULT_NA => __('N/A'),
        ];
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_PASSED => 'bg-emerald-50 text-emerald-800',
            self::STATUS_FAILED => 'bg-red-50 text-red-800',
            default => 'bg-amber-50 text-amber-900',
        };
    }

    public static function escalationBadgeClass(string $status): string
    {
        return match ($status) {
            self::ESCALATION_RESOLVED => 'bg-emerald-50 text-emerald-800',
            self::ESCALATION_IN_REVIEW => 'bg-blue-50 text-blue-800',
            default => 'bg-amber-50 text-amber-900',
        };
    }

    /**
     * @return list<array{key: string, label: string, kind: string, certificate: bool}>
     */
    public static function checklistItems(string $siteType): array
    {
        return match ($siteType) {
            self::SITE_RESTAURANT => [
                ['key' => 'cold_room_condition', 'label' => __('Cold room condition'), 'kind' => self::KIND_PASS_FAIL, 'certificate' => false],
                ['key' => 'freezer_condition', 'label' => __('Freezer condition'), 'kind' => self::KIND_PASS_FAIL, 'certificate' => false],
                ['key' => 'general_hygiene', 'label' => __('General hygiene standards'), 'kind' => self::KIND_PASS_FAIL, 'certificate' => false],
                ['key' => 'certificate_of_origin', 'label' => __('Certificate of origin for meat'), 'kind' => self::KIND_PRESENT_MISSING, 'certificate' => true],
            ],
            self::SITE_BAR => [
                ['key' => 'freezer_condition', 'label' => __('Freezer condition'), 'kind' => self::KIND_PASS_FAIL, 'certificate' => false],
                ['key' => 'chiller_condition', 'label' => __('Chiller condition'), 'kind' => self::KIND_PASS_FAIL, 'certificate' => false],
                ['key' => 'grilling_cold_storage', 'label' => __('Cold storage at brochette / grilling station'), 'kind' => self::KIND_PASS_FAIL, 'certificate' => false],
            ],
            self::SITE_BUTCHERY => [
                ['key' => 'hygiene_standards', 'label' => __('General hygiene standards'), 'kind' => self::KIND_PASS_FAIL, 'certificate' => false],
            ],
            self::SITE_PRIVATE_EVENT => [
                ['key' => 'certificate_for_meat', 'label' => __('Certificate for the meat product'), 'kind' => self::KIND_PRESENT_MISSING, 'certificate' => true],
                ['key' => 'proof_of_purchase', 'label' => __('Proof of purchase / receipt'), 'kind' => self::KIND_PRESENT_MISSING, 'certificate' => false],
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function certificateItemKeys(): array
    {
        $keys = [];
        foreach (self::SITE_TYPES as $type) {
            foreach (self::checklistItems($type) as $item) {
                if ($item['certificate']) {
                    $keys[] = $item['key'];
                }
            }
        }

        return array_values(array_unique($keys));
    }

    public static function contactRequired(string $siteType): bool
    {
        return in_array($siteType, [self::SITE_RESTAURANT, self::SITE_BAR, self::SITE_BUTCHERY], true);
    }

    /**
     * @return array<int, array{site_type: string, meat_source: string, certificate_required: bool}>
     */
    public static function defaultCertificateRules(): array
    {
        $rules = [];
        foreach ([self::SITE_RESTAURANT, self::SITE_BUTCHERY, self::SITE_PRIVATE_EVENT] as $type) {
            foreach (self::MEAT_SOURCES as $source) {
                $rules[] = [
                    'site_type' => $type,
                    'meat_source' => $source,
                    'certificate_required' => $source !== self::MEAT_SOURCE_OWN_FARM,
                ];
            }
        }

        return $rules;
    }
}
