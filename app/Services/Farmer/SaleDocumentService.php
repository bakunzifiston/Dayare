<?php

namespace App\Services\Farmer;

use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleLog;
use Illuminate\Support\Str;

class SaleDocumentService
{
    public function __construct(
        private readonly SalePdfService $pdf,
        private readonly SaleHistoryService $history,
    ) {}

    public function generate(Sale $sale, string $type, int $userId, ?string $ip = null): SaleDocument
    {
        $sale->load(['farm.business', 'buyer', 'saleAnimals.animal.livestock', 'payments']);

        $document = SaleDocument::query()->create([
            'sale_id' => $sale->id,
            'document_type' => $type,
            'document_number' => $this->generateNumber($sale->id, $type),
            'generated_by' => $userId,
            'generated_at' => now(),
        ]);

        $path = $this->pdf->generate($sale, $document);
        $document->update(['document_path' => $path]);

        $this->history->log($sale, SaleLog::ACTION_DOCUMENT_GENERATED, $userId, $ip, __('Generated :type.', ['type' => str_replace('_', ' ', $type)]));

        return $document->fresh();
    }

    private function generateNumber(int $saleId, string $type): string
    {
        do {
            $number = strtoupper(substr($type, 0, 3)).'-'.$saleId.'-'.Str::upper(Str::random(5));
        } while (SaleDocument::query()->where('document_number', $number)->exists());

        return $number;
    }
}
