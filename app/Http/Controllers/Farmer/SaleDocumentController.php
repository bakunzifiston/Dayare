<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleLog;
use App\Services\Farmer\SaleDocumentService;
use App\Services\Farmer\SaleHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);

        $farmIds = \App\Models\Farm::query()
            ->whereIn('business_id', $request->user()->accessibleFarmerBusinessIds())
            ->pluck('id');

        $records = SaleDocument::query()
            ->whereHas('sale', fn ($q) => $q->whereIn('farm_id', $farmIds))
            ->with(['sale.buyer', 'generator'])
            ->latest('generated_at')
            ->paginate(20)
            ->withQueryString();

        return view('farmer.sales.documents.index', compact('records'));
    }

    public function store(Request $request, Sale $sale, SaleDocumentService $documents, SaleHistoryService $history): RedirectResponse
    {
        $this->authorize('update', $sale);

        $validated = $request->validate([
            'document_type' => ['required', 'string', Rule::in(SaleDocument::TYPES)],
        ]);

        $document = $documents->generate($sale, $validated['document_type'], $request->user()->id, $history->requestIp($request));

        return redirect()->route('farmer.sales.documents.download', $document)->with('status', __('Document generated.'));
    }

    public function download(Request $request, SaleDocument $document, SaleHistoryService $history): StreamedResponse
    {
        $this->authorize('view', $document->sale);
        abort_unless($document->document_path && Storage::disk('public')->exists($document->document_path), 404);

        $history->log($document->sale, SaleLog::ACTION_DOCUMENT_DOWNLOADED, $request->user()->id, $history->requestIp($request));

        return Storage::disk('public')->download($document->document_path, $document->document_number.'.pdf');
    }
}
