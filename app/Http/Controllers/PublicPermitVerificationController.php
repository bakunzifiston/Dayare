<?php

namespace App\Http\Controllers;

use App\Models\MovementLog;
use App\Models\MovementPermit;
use App\Services\Farmer\MovementHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicPermitVerificationController extends Controller
{
    public function lookup(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $identifier = trim((string) $request->query('identifier', ''));
        if ($identifier !== '') {
            return redirect()->route('verify.permit.show', ['identifier' => $identifier]);
        }

        return view('public.permit-verify-lookup');
    }

    public function show(string $identifier, MovementHistoryService $history, Request $request): View
    {
        $permit = $this->resolvePermit($identifier);
        $permit->syncExpiryStatus();
        $permit->load(['sourceFarm', 'animals.animal']);

        $history->log($permit, MovementLog::ACTION_VERIFIED, null, $request->ip(), __('Public permit verification.'));

        return view('public.permit-verify', [
            'permit' => $permit,
            'verifiedAt' => now(),
        ]);
    }

    public function api(string $identifier): JsonResponse
    {
        $permit = $this->resolvePermit($identifier);
        $permit->syncExpiryStatus();
        $permit->loadCount('animals');

        return response()->json([
            'success' => true,
            'data' => [
                'permit_number' => $permit->permit_number,
                'status' => $permit->permit_status,
                'issue_date' => $permit->issue_date?->toDateString(),
                'expiry_date' => $permit->expiry_date?->toDateString(),
                'owner_name' => $permit->owner_name,
                'animal_count' => $permit->animals_count,
                'origin' => $permit->sourceLocationLabel() ?: $permit->origin_location,
                'destination' => $permit->destinationLocationLabel() ?: $permit->destination_location,
                'issued_by' => $permit->issued_by,
                'issuing_authority' => $permit->issuing_authority,
                'verification_code' => $permit->verification_code,
                'pdf_download_url' => $permit->file_path ? Storage::disk('public')->url($permit->file_path) : null,
                'valid' => $permit->isValidOn(now()),
            ],
        ]);
    }

    private function resolvePermit(string $identifier): MovementPermit
    {
        $identifier = trim($identifier);

        return MovementPermit::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('permit_number', $identifier)
                    ->orWhere('verification_code', $identifier)
                    ->orWhere('verification_token', $identifier);
            })
            ->firstOrFail();
    }
}
