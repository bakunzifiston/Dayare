<?php

namespace App\Http\Controllers;

use App\Enums\MeatExportDocumentType;
use App\Http\Controllers\Concerns\ScopesProcessorData;
use App\Http\Requests\StoreMeatExportDocumentRequest;
use App\Http\Requests\UpdateMeatExportDocumentRequest;
use App\Models\DeliveryConfirmation;
use App\Models\MeatExportDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeatExportDocumentController extends Controller
{
    use ScopesProcessorData;

    public function index(Request $request, DeliveryConfirmation $deliveryConfirmation): View
    {
        $this->ensureConfirmationInScope($request, $deliveryConfirmation);
        $deliveryConfirmation->load('exportDocuments');

        return view('export-documents.index', [
            'confirmation' => $deliveryConfirmation,
            'documents' => $deliveryConfirmation->exportDocuments,
        ]);
    }

    public function create(Request $request, DeliveryConfirmation $deliveryConfirmation): View
    {
        $this->ensureConfirmationInScope($request, $deliveryConfirmation);
        $this->ensureInternational($deliveryConfirmation);

        return view('export-documents.create', [
            'confirmation' => $deliveryConfirmation,
            'documentTypes' => MeatExportDocumentType::cases(),
            'statuses' => MeatExportDocument::STATUSES,
        ]);
    }

    public function store(
        StoreMeatExportDocumentRequest $request,
        DeliveryConfirmation $deliveryConfirmation
    ): RedirectResponse {
        $this->ensureConfirmationInScope($request, $deliveryConfirmation);
        $this->ensureInternational($deliveryConfirmation);

        $data = $request->validated();
        unset($data['file']);
        $data['delivery_confirmation_id'] = $deliveryConfirmation->id;
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store(
                'delivery-confirmations/'.$deliveryConfirmation->id.'/export-documents',
                'local'
            );
        }

        MeatExportDocument::create($data);

        return redirect()
            ->route('delivery-confirmations.show', $deliveryConfirmation)
            ->with('status', __('Export document recorded successfully.'));
    }

    public function show(
        Request $request,
        DeliveryConfirmation $deliveryConfirmation,
        MeatExportDocument $meatExportDocument
    ): View {
        $this->ensureDocumentInScope($request, $deliveryConfirmation, $meatExportDocument);

        return view('export-documents.show', [
            'confirmation' => $deliveryConfirmation,
            'document' => $meatExportDocument,
        ]);
    }

    public function edit(
        Request $request,
        DeliveryConfirmation $deliveryConfirmation,
        MeatExportDocument $meatExportDocument
    ): View {
        $this->ensureDocumentInScope($request, $deliveryConfirmation, $meatExportDocument);
        $this->ensureInternational($deliveryConfirmation);

        return view('export-documents.edit', [
            'confirmation' => $deliveryConfirmation,
            'document' => $meatExportDocument,
            'documentTypes' => MeatExportDocumentType::cases(),
            'statuses' => MeatExportDocument::STATUSES,
        ]);
    }

    public function update(
        UpdateMeatExportDocumentRequest $request,
        DeliveryConfirmation $deliveryConfirmation,
        MeatExportDocument $meatExportDocument
    ): RedirectResponse {
        $this->ensureDocumentInScope($request, $deliveryConfirmation, $meatExportDocument);
        $this->ensureInternational($deliveryConfirmation);

        $data = $request->validated();
        unset($data['file']);
        $data['updated_by'] = $request->user()->id;

        if ($request->hasFile('file')) {
            if ($meatExportDocument->file_path) {
                Storage::disk('local')->delete($meatExportDocument->file_path);
            }
            $data['file_path'] = $request->file('file')->store(
                'delivery-confirmations/'.$deliveryConfirmation->id.'/export-documents',
                'local'
            );
        }

        $meatExportDocument->update($data);

        return redirect()
            ->route('delivery-confirmations.show', $deliveryConfirmation)
            ->with('status', __('Export document updated successfully.'));
    }

    public function destroy(
        Request $request,
        DeliveryConfirmation $deliveryConfirmation,
        MeatExportDocument $meatExportDocument
    ): RedirectResponse {
        $this->ensureDocumentInScope($request, $deliveryConfirmation, $meatExportDocument);

        if ($meatExportDocument->file_path) {
            Storage::disk('local')->delete($meatExportDocument->file_path);
        }

        $meatExportDocument->delete();

        return redirect()
            ->route('delivery-confirmations.show', $deliveryConfirmation)
            ->with('status', __('Export document removed.'));
    }

    public function download(
        Request $request,
        DeliveryConfirmation $deliveryConfirmation,
        MeatExportDocument $meatExportDocument
    ): StreamedResponse {
        $this->ensureDocumentInScope($request, $deliveryConfirmation, $meatExportDocument);

        $path = $meatExportDocument->file_path;
        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path, basename($path));
    }

    protected function ensureInternational(DeliveryConfirmation $confirmation): void
    {
        if (! $confirmation->isInternationalExport()) {
            throw ValidationException::withMessages([
                'document_type' => [__('Export documents only apply to international deliveries.')],
            ]);
        }
    }

    protected function ensureDocumentInScope(
        Request $request,
        DeliveryConfirmation $deliveryConfirmation,
        MeatExportDocument $document
    ): void {
        $this->ensureConfirmationInScope($request, $deliveryConfirmation);
        if ((int) $document->delivery_confirmation_id !== (int) $deliveryConfirmation->id) {
            abort(404);
        }
    }
}
