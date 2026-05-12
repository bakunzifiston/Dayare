<?php

namespace App\Services\Farmer;

use App\Models\Animal;
use App\Models\AnimalCertificate;
use App\Models\Sale;
use App\Models\SaleAnimal;
use App\Models\SaleLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly SaleCodeService $codes,
        private readonly SaleHistoryService $history,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data, int $userId, ?string $ip = null): Sale
    {
        return DB::transaction(function () use ($data, $userId, $ip): Sale {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $data['sale_number'] = $this->codes->generate();
            $data['created_by'] = $userId;
            $data = $this->applyTotals($data, $lines);

            $sale = Sale::query()->create($data);
            $this->syncLines($sale, $lines, null);
            $this->refreshCertificateStatus($sale);
            $this->history->log($sale, SaleLog::ACTION_CREATED, $userId, $ip);

            return $sale->fresh(['saleAnimals.animal', 'buyer', 'farm']);
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(Sale $sale, array $data, int $userId, ?string $ip = null): Sale
    {
        if (! $sale->isEditable()) {
            throw ValidationException::withMessages([
                'sale_status' => __('This sale can no longer be edited.'),
            ]);
        }

        return DB::transaction(function () use ($sale, $data, $userId, $ip): Sale {
            $lines = $data['lines'] ?? null;
            unset($data['lines']);

            if (is_array($lines)) {
                $data = $this->applyTotals($data, $lines);
                $sale->update($data);
                $sale->saleAnimals()->delete();
                $this->syncLines($sale, $lines, $sale->id);
            } else {
                $sale->update($data);
            }

            $this->refreshCertificateStatus($sale->fresh());
            $this->history->log($sale, SaleLog::ACTION_UPDATED, $userId, $ip);

            return $sale->fresh(['saleAnimals.animal', 'buyer', 'farm']);
        });
    }

    public function confirm(Sale $sale, int $userId, ?string $ip = null): Sale
    {
        $this->assertTransition($sale, [Sale::STATUS_DRAFT, Sale::STATUS_PENDING]);
        $sale->update(['sale_status' => Sale::STATUS_CONFIRMED]);
        $this->history->log($sale, SaleLog::ACTION_UPDATED, $userId, $ip, __('Sale confirmed.'));

        return $sale->fresh();
    }

    public function approve(Sale $sale, int $userId, ?string $ip = null): Sale
    {
        $this->assertTransition($sale, [Sale::STATUS_PENDING, Sale::STATUS_CONFIRMED]);
        $sale->update([
            'sale_status' => Sale::STATUS_CONFIRMED,
            'approved_by' => $userId,
        ]);
        $this->history->log($sale, SaleLog::ACTION_APPROVED, $userId, $ip);

        return $sale->fresh();
    }

    public function complete(Sale $sale, int $userId, ?string $ip = null): Sale
    {
        $this->assertTransition($sale, [Sale::STATUS_CONFIRMED, Sale::STATUS_PENDING]);
        $sale->load('saleAnimals.animal');

        foreach ($sale->saleAnimals as $line) {
            if ($line->animal) {
                $line->animal->update(['lifecycle_status' => Animal::LIFECYCLE_SOLD]);
            }
        }

        $sale->update(['sale_status' => Sale::STATUS_COMPLETED]);
        $this->history->log($sale, SaleLog::ACTION_COMPLETED, $userId, $ip);

        return $sale->fresh();
    }

    public function cancel(Sale $sale, int $userId, ?string $ip = null, ?string $notes = null): Sale
    {
        if (in_array($sale->sale_status, [Sale::STATUS_COMPLETED, Sale::STATUS_CANCELLED, Sale::STATUS_REFUNDED], true)) {
            throw ValidationException::withMessages([
                'sale_status' => __('This sale cannot be cancelled.'),
            ]);
        }

        $sale->update(['sale_status' => Sale::STATUS_CANCELLED]);
        $this->history->log($sale, SaleLog::ACTION_CANCELLED, $userId, $ip, $notes);

        return $sale->fresh();
    }

    public function refreshPaymentStatus(Sale $sale): Sale
    {
        $paid = (float) $sale->payments()->sum('amount_paid');
        $total = (float) $sale->total_amount;

        $status = Sale::PAYMENT_PENDING;
        if ($paid <= 0) {
            $status = Sale::PAYMENT_PENDING;
        } elseif ($paid + 0.009 < $total) {
            $status = Sale::PAYMENT_PARTIAL;
        } else {
            $status = Sale::PAYMENT_PAID;
        }

        if ($sale->payment_status === Sale::PAYMENT_REFUNDED) {
            $status = Sale::PAYMENT_REFUNDED;
        }

        $sale->update(['payment_status' => $status]);

        return $sale->fresh();
    }

    /** @param  list<array<string, mixed>>  $lines */
    private function syncLines(Sale $sale, array $lines, ?int $ignoreSaleId = null): void
    {
        foreach ($lines as $line) {
            $animalId = isset($line['animal_id']) ? (int) $line['animal_id'] : null;
            $livestockId = isset($line['livestock_id']) ? (int) $line['livestock_id'] : null;

            if ($animalId) {
                $this->assertAnimalSellable($animalId, $ignoreSaleId);
            }

            $salePrice = (float) ($line['sale_price'] ?? 0);
            $liveWeight = isset($line['live_weight']) ? (float) $line['live_weight'] : null;
            $pricePerKg = isset($line['price_per_kg']) ? (float) $line['price_per_kg'] : null;

            if ($liveWeight !== null && $liveWeight > 0 && $pricePerKg !== null && $pricePerKg > 0 && $salePrice <= 0) {
                $salePrice = round($liveWeight * $pricePerKg, 2);
            }

            $certificateVerified = $animalId
                ? AnimalCertificate::query()
                    ->where('animal_id', $animalId)
                    ->where('certificate_status', AnimalCertificate::STATUS_ACTIVE)
                    ->exists()
                : false;

            $movementVerified = $sale->movement_permit_id !== null;

            SaleAnimal::query()->create([
                'sale_id' => $sale->id,
                'animal_id' => $animalId,
                'livestock_id' => $livestockId,
                'sale_price' => $salePrice,
                'live_weight' => $liveWeight,
                'price_per_kg' => $pricePerKg,
                'animal_condition' => $line['animal_condition'] ?? SaleAnimal::CONDITION_HEALTHY,
                'certificate_verified' => (bool) ($line['certificate_verified'] ?? $certificateVerified),
                'movement_permit_verified' => (bool) ($line['movement_permit_verified'] ?? $movementVerified),
                'remarks' => $line['remarks'] ?? null,
            ]);
        }
    }

    private function assertAnimalSellable(int $animalId, ?int $ignoreSaleId = null): void
    {
        $animal = Animal::query()->find($animalId);
        if (! $animal) {
            throw ValidationException::withMessages(['lines' => __('One or more animals could not be found.')]);
        }

        if ($animal->lifecycle_status === Animal::LIFECYCLE_DEAD) {
            throw ValidationException::withMessages(['lines' => __('Dead animals cannot be sold.')]);
        }

        if ($animal->lifecycle_status === Animal::LIFECYCLE_SOLD) {
            throw ValidationException::withMessages(['lines' => __('One or more animals are already sold.')]);
        }

        if (in_array($animal->health_status, [Animal::HEALTH_SICK, Animal::HEALTH_QUARANTINED], true)) {
            throw ValidationException::withMessages(['lines' => __('Animals that are sick or quarantined cannot be sold.')]);
        }

        $activeSaleExists = SaleAnimal::query()
            ->where('animal_id', $animalId)
            ->whereHas('sale', function ($query) use ($ignoreSaleId): void {
                $query->whereIn('sale_status', Sale::OPEN_STATUSES);
                if ($ignoreSaleId) {
                    $query->where('id', '!=', $ignoreSaleId);
                }
            })
            ->exists();

        if ($activeSaleExists) {
            throw ValidationException::withMessages(['lines' => __('One or more animals already belong to an active sale.')]);
        }
    }

    /** @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    private function applyTotals(array $data, array $lines): array
    {
        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += (float) ($line['sale_price'] ?? 0);
        }

        $discount = (float) ($data['discount_amount'] ?? 0);
        $tax = (float) ($data['tax_amount'] ?? 0);
        $data['subtotal_amount'] = round($subtotal, 2);
        $data['discount_amount'] = round($discount, 2);
        $data['tax_amount'] = round($tax, 2);
        $data['total_amount'] = round(max(0, $subtotal - $discount + $tax), 2);

        return $data;
    }

    private function refreshCertificateStatus(Sale $sale): void
    {
        $sale->load('saleAnimals');
        $lines = $sale->saleAnimals;
        if ($lines->isEmpty()) {
            $sale->update(['certificate_status' => Sale::CERT_UNVERIFIED]);

            return;
        }

        $verified = $lines->where('certificate_verified', true)->count();
        $status = match (true) {
            $verified === 0 => Sale::CERT_UNVERIFIED,
            $verified < $lines->count() => Sale::CERT_PARTIAL,
            default => Sale::CERT_VERIFIED,
        };

        $sale->update(['certificate_status' => $status]);
    }

    /** @param  list<string>  $allowed */
    private function assertTransition(Sale $sale, array $allowed): void
    {
        if (! in_array($sale->sale_status, $allowed, true)) {
            throw ValidationException::withMessages([
                'sale_status' => __('This sale cannot move to the requested status.'),
            ]);
        }
    }
}
