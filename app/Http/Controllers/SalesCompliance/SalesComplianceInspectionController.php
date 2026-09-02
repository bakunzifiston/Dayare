<?php

namespace App\Http\Controllers\SalesCompliance;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesCompliance\RecordSalesComplianceInspectionRequest;
use App\Http\Requests\SalesCompliance\StoreSalesComplianceInspectionRequest;
use App\Models\SalesComplianceAttachment;
use App\Models\SalesComplianceCertificateRule;
use App\Models\SalesComplianceInspection;
use App\Models\SalesComplianceSite;
use App\Services\SalesCompliance\SalesComplianceInspectionService;
use App\Support\SalesComplianceCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesComplianceInspectionController extends Controller
{
    public function __construct(private SalesComplianceInspectionService $service) {}

    public function create(Request $request): View
    {
        $businessId = $this->businessId($request);

        return view('sales-compliance.inspections.create', [
            'sites' => SalesComplianceSite::query()->where('business_id', $businessId)->where('is_active', true)->orderBy('name')->get(),
            'inspectors' => $this->service->businessInspectors($businessId),
            'inspectorUsers' => $this->service->inspectorRoleUsers($businessId),
            'selectedSiteId' => $request->query('site_id'),
        ]);
    }

    public function store(StoreSalesComplianceInspectionRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $data = $request->validated();
        $assignee = $this->parseAssignee((string) $request->input('assignee', ''), $businessId);

        $inspection = SalesComplianceInspection::query()->create([
            'business_id' => $businessId,
            'site_id' => $data['site_id'],
            'inspector_id' => $assignee['inspector_id'],
            'assigned_user_id' => $assignee['assigned_user_id'],
            'scheduled_date' => $data['scheduled_date'],
            'scheduled_time' => $data['scheduled_time'],
            'status' => SalesComplianceCatalog::STATUS_PENDING,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('sales-compliance.inspections.show', $inspection)->with('status', __('Visit scheduled.'));
    }

    public function show(Request $request, SalesComplianceInspection $inspection): View
    {
        $this->authorizeInspection($request, $inspection);
        $inspection->load(['site', 'inspector', 'assignedUser', 'responses', 'productLines', 'attachments', 'escalations', 'createdBy', 'updatedBy']);

        return view('sales-compliance.inspections.show', [
            'inspection' => $inspection,
            'items' => SalesComplianceCatalog::checklistItems($inspection->site->site_type),
            'certificateRequired' => $this->service->certificateRequired($inspection),
        ]);
    }

    public function edit(Request $request, SalesComplianceInspection $inspection): View
    {
        $this->authorizeInspection($request, $inspection);
        $businessId = $this->businessId($request);
        $inspection->load(['site', 'responses', 'productLines', 'attachments']);
        $items = SalesComplianceCatalog::checklistItems($inspection->site->site_type);
        $certRequired = $this->service->certificateRequired($inspection);

        return view('sales-compliance.inspections.edit', [
            'inspection' => $inspection,
            'sites' => SalesComplianceSite::query()->where('business_id', $businessId)->orderBy('name')->get(),
            'inspectors' => $this->service->businessInspectors($businessId),
            'inspectorUsers' => $this->service->inspectorRoleUsers($businessId),
            'items' => $items,
            'certificateRequired' => $certRequired,
            'meatSources' => SalesComplianceCatalog::meatSourceLabels(),
            'rulesPreview' => SalesComplianceCertificateRule::query()
                ->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $businessId))
                ->where('site_type', $inspection->site->site_type)
                ->get()
                ->keyBy('meat_source'),
        ]);
    }

    public function update(StoreSalesComplianceInspectionRequest $request, SalesComplianceInspection $inspection): RedirectResponse
    {
        $this->authorizeInspection($request, $inspection);
        $businessId = $this->businessId($request);
        $data = $request->validated();
        $assignee = $this->parseAssignee((string) $request->input('assignee', ''), $businessId);

        $inspection->update([
            'site_id' => $data['site_id'],
            'inspector_id' => $assignee['inspector_id'],
            'assigned_user_id' => $assignee['assigned_user_id'],
            'scheduled_date' => $data['scheduled_date'],
            'scheduled_time' => $data['scheduled_time'],
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('sales-compliance.inspections.show', $inspection)->with('status', __('Visit updated.'));
    }

    public function record(RecordSalesComplianceInspectionRequest $request, SalesComplianceInspection $inspection): RedirectResponse
    {
        $this->authorizeInspection($request, $inspection);
        $inspection->load('site');

        $this->service->recordChecklist(
            $inspection,
            $request->input('responses', []),
            $request->input('product_lines', []),
            $request->file('attachments', []) ?? [],
            $request->user()->id,
            $request->input('meat_source'),
            $request->input('inspector_notes'),
        );

        return redirect()->route('sales-compliance.inspections.show', $inspection)->with('status', __('Inspection recorded.'));
    }

    public function destroy(Request $request, SalesComplianceInspection $inspection): RedirectResponse
    {
        $this->authorizeInspection($request, $inspection);
        $inspection->delete();

        return redirect()->route('sales-compliance.hub')->with('status', __('Visit deleted.'));
    }

    public function downloadAttachment(Request $request, SalesComplianceInspection $inspection, SalesComplianceAttachment $attachment): StreamedResponse
    {
        $this->authorizeInspection($request, $inspection);
        abort_unless((int) $attachment->inspection_id === (int) $inspection->id, 404);
        abort_unless($attachment->existsOnDisk(), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    /**
     * @return array{inspector_id: ?int, assigned_user_id: ?int}
     */
    private function parseAssignee(string $assignee, int $businessId): array
    {
        if (str_starts_with($assignee, 'inspector:')) {
            $id = (int) substr($assignee, 10);
            abort_unless($this->service->businessInspectors($businessId)->contains('id', $id), 422, __('Invalid inspector.'));

            return ['inspector_id' => $id, 'assigned_user_id' => null];
        }
        if (str_starts_with($assignee, 'user:')) {
            $id = (int) substr($assignee, 5);
            abort_unless($this->service->inspectorRoleUsers($businessId)->contains('id', $id), 422, __('Invalid inspector user.'));

            return ['inspector_id' => null, 'assigned_user_id' => $id];
        }

        abort(422, __('Select an inspector.'));
    }

    private function businessId(Request $request): int
    {
        $businessId = $request->user()->activeProcessorBusinessId();
        abort_if($businessId === null, 403, __('Select a processor business first.'));

        return $businessId;
    }

    private function authorizeInspection(Request $request, SalesComplianceInspection $inspection): void
    {
        abort_unless((int) $inspection->business_id === $this->businessId($request), 404);
    }
}
