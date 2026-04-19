<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AdministrativeDivision;

final class AdministrativeDivisionHierarchy
{
    /**
     * Verifies each division exists, types match the expected level, and parent_id forms a single chain.
     */
    public static function isValidChain(
        int $countryId,
        int $provinceId,
        int $districtId,
        int $sectorId,
        int $cellId,
        int $villageId,
    ): bool {
        $country = AdministrativeDivision::query()->find($countryId);
        if ($country === null || $country->type !== AdministrativeDivision::TYPE_COUNTRY) {
            return false;
        }

        return self::childMatches($provinceId, AdministrativeDivision::TYPE_PROVINCE, (int) $country->id)
            && self::childMatches($districtId, AdministrativeDivision::TYPE_DISTRICT, $provinceId)
            && self::childMatches($sectorId, AdministrativeDivision::TYPE_SECTOR, $districtId)
            && self::childMatches($cellId, AdministrativeDivision::TYPE_CELL, $sectorId)
            && self::childMatches($villageId, AdministrativeDivision::TYPE_VILLAGE, $cellId);
    }

    private static function childMatches(int $childId, string $expectedType, int $expectedParentId): bool
    {
        $row = AdministrativeDivision::query()->find($childId);

        return $row !== null
            && $row->type === $expectedType
            && (int) $row->parent_id === $expectedParentId;
    }
}
