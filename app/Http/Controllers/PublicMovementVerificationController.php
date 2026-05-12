<?php

namespace App\Http\Controllers;

use App\Models\MovementLog;
use App\Models\MovementPermit;
use App\Services\Farmer\MovementHistoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicMovementVerificationController extends Controller
{
    public function __invoke(Request $request, string $token, MovementHistoryService $history): View
    {
        $permit = MovementPermit::query()
            ->where(function ($query) use ($token): void {
                $query->where('verification_token', $token)
                    ->orWhere('permit_number', $token);
            })
            ->with(['sourceFarm', 'animals.animal'])
            ->firstOrFail();

        $history->log($permit, MovementLog::ACTION_VERIFIED, null, $request->ip(), __('Public verification scan'));

        return view('public.movement-verify', [
            'permit' => $permit,
            'verifiedAt' => now(),
        ]);
    }
}
