<?php

namespace App\Http\Controllers\SalesCompliance;

use App\Http\Controllers\Controller;
use App\Models\SalesComplianceCertificateRule;
use App\Support\SalesComplianceCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalesComplianceCertificateRuleController extends Controller
{
    public function index(Request $request): View
    {
        $businessId = $this->businessId($request);
        $rules = SalesComplianceCertificateRule::query()
            ->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $businessId))
            ->orderByRaw('CASE WHEN business_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('site_type')
            ->orderBy('meat_source')
            ->get();

        return view('sales-compliance.rules.index', compact('rules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $data = $request->validate([
            'site_type' => ['required', Rule::in(SalesComplianceCatalog::SITE_TYPES)],
            'meat_source' => ['required', Rule::in(SalesComplianceCatalog::MEAT_SOURCES)],
            'certificate_required' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        SalesComplianceCertificateRule::query()->updateOrCreate(
            [
                'business_id' => $businessId,
                'site_type' => $data['site_type'],
                'meat_source' => $data['meat_source'],
            ],
            [
                'certificate_required' => $request->boolean('certificate_required'),
                'notes' => $data['notes'] ?? null,
            ]
        );

        return redirect()->route('sales-compliance.rules.index')->with('status', __('Certificate rule saved. This overrides the default for your business.'));
    }

    public function destroy(Request $request, int $rule): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $model = SalesComplianceCertificateRule::query()->whereKey($rule)->firstOrFail();
        abort_unless((int) $model->business_id === $businessId, 404);
        $model->delete();

        return redirect()->route('sales-compliance.rules.index')->with('status', __('Business override removed. The default rule applies again.'));
    }

    private function businessId(Request $request): int
    {
        $businessId = $request->user()->activeProcessorBusinessId();
        abort_if($businessId === null, 403, __('Select a processor business first.'));

        return $businessId;
    }
}
