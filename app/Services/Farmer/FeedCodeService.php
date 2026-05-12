<?php

namespace App\Services\Farmer;

use App\Models\FeedInventory;
use App\Models\FeedSupplier;
use App\Models\FeedType;
use App\Models\FeedingRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FeedCodeService
{
    public function generateFeedTypeCode(int $businessId): string
    {
        return $this->generateUniqueCode('FDT', FeedType::class, 'feed_code', ['business_id' => $businessId]);
    }

    public function generateSupplierCode(int $businessId): string
    {
        return $this->generateUniqueCode('FSP', FeedSupplier::class, 'supplier_code', ['business_id' => $businessId]);
    }

    public function generateInventoryCode(): string
    {
        return $this->generateUniqueCode('FIN', FeedInventory::class, 'inventory_code');
    }

    public function generateFeedingCode(): string
    {
        return $this->generateUniqueCode('FED', FeedingRecord::class, 'feeding_code');
    }

    /**
     * @param  array<string, mixed>  $scope
     */
    private function generateUniqueCode(string $prefix, string $modelClass, string $column, array $scope = []): string
    {
        do {
            $code = $prefix.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
            $query = $modelClass::withTrashed()->where($column, $code);
            foreach ($scope as $key => $value) {
                $query->where($key, $value);
            }
        } while ($query->exists());

        return $code;
    }
}
