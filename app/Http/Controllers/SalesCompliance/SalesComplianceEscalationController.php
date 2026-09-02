<?php

namespace App\Http\Controllers\SalesCompliance;

use App\Http\Controllers\Controller;
use App\Models\SalesComplianceEscalation;
use App\Models\SalesComplianceInspection;
use App\Models\SalesComplianceSite;
use App\Support\SalesComplianceCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalesComplianceEscalationController extends Controller
{
    public function create(Request $request): View
    {
        $businessId = $this->businessId($request);
        $inspection = null;
        if ($request->filled('inspection_id')) {
            $inspection = SalesComplianceInspection::query()
                ->where('business_id', $businessId)
                ->whereKey($request->query('inspection_id'))
                ->with('site')
                ->first();
        }

        return view('sales-compliance.escalations.create', [
            'sites' => SalesComplianceSite::query()->where('business_id', $businessId)->orderBy('name')->get(),
            'inspection' => $inspection,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $data = $request->validate([
            'site_id' => ['required', 'integer'],
            'inspection_id' => ['nullable', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $this->assertSite($businessId, (int) $data['site_id']);
        if (! empty($data['inspection_id'])) {
            $ok = SalesComplianceInspection::query()
                ->where('business_id', $businessId)
                ->whereKey($data['inspection_id'])
                ->exists();
            abort_unless($ok, 422, __('Invalid inspection.'));
        }

        $escalation = SalesComplianceEscalation::query()->create([
            'business_id' => $businessId,
            'site_id' => $data['site_id'],
            'inspection_id' => $data['inspection_id'] ?? null,
            'status' => SalesComplianceCatalog::ESCALATION_OPEN,
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('sales-compliance.escalations.show', $escalation)->with('status', __('Escalation opened.'));
    }

    public function show(Request $request, SalesComplianceEscalation $escalation): View
    {
        $this->authorizeEscalation($request, $escalation);
        $escalation->load(['site', 'inspection', 'createdBy', 'updatedBy']);

        return view('sales-compliance.escalations.show', compact('escalation'));
    }

    public function update(Request $request, SalesComplianceEscalation $escalation): RedirectResponse
    {
        $this->authorizeEscalation($request, $escalation);
        $data = $request->validate([
            'status' => ['required', Rule::in(SalesComplianceCatalog::ESCALATION_STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $escalation->update([
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $escalation->notes,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('sales-compliance.escalations.show', $escalation)->with('status', __('Escalation updated.'));
    }

    private function businessId(Request $request): int
    {
        $businessId = $request->user()->activeProcessorBusinessId();
        abort_if($businessId === null, 403, __('Select a processor business first.'));

        return $businessId;
    }

    private function authorizeEscalation(Request $request, SalesComplianceEscalation $escalation): void
    {
        abort_unless((int) $escalation->business_id === $this->businessId($request), 404);
    }

    private function assertSite(int $businessId, int $siteId): void
    {
        abort_unless(
            SalesComplianceSite::query()->where('business_id', $businessId)->whereKey($siteId)->exists(),
            422,
            __('Invalid site.')
        );
    }
}
