<?php

namespace App\Http\Controllers;

use App\Http\Requests\Logistics\CompleteTripRequest;
use App\Http\Requests\Logistics\CreateInvoiceRequest;
use App\Http\Requests\Logistics\PlanTripRequest;
use App\Http\Requests\Logistics\StartTripRequest;
use App\Http\Requests\Logistics\StoreCompanyRequest;
use App\Http\Requests\Logistics\StoreComplianceDocumentRequest;
use App\Http\Requests\Logistics\StoreDriverRequest;
use App\Http\Requests\Logistics\StoreOrderRequest;
use App\Http\Requests\Logistics\StoreTrackingLogRequest;
use App\Http\Requests\Logistics\StoreVehicleRequest;
use App\Http\Requests\Logistics\UpdateCompanyRequest;
use App\Models\LogisticsCompany;
use App\Models\LogisticsTrip;
use App\Services\Logistics\BillingService;
use App\Services\Logistics\CompanyService;
use App\Services\Logistics\ComplianceService;
use App\Services\Logistics\DriverService;
use App\Services\Logistics\OrderService;
use App\Services\Logistics\TrackingService;
use App\Services\Logistics\TripExecutionService;
use App\Services\Logistics\TripPlanningService;
use App\Services\Logistics\VehicleService;
use App\Services\Logistics\WorkspaceContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LogisticsModuleController extends Controller
{
    public function __construct(
        private CompanyService $companyService,
        private VehicleService $vehicleService,
        private DriverService $driverService,
        private OrderService $orderService,
        private TripPlanningService $tripPlanningService,
        private TripExecutionService $tripExecutionService,
        private TrackingService $trackingService,
        private ComplianceService $complianceService,
        private BillingService $billingService,
        private WorkspaceContextService $workspaceContext
    ) {}

    public function company(Request $request): View
    {
        $context = $this->workspaceContext->build($request);
        $context['pageTitle'] = __('Company');
        $context['pageSubtitle'] = __('Register and manage logistics companies before operational modules.');
        $context['actionLabel'] = __('Register Company');

        return view('logistics.company.index', $context);
    }

    /**
     * Legacy URL: redirects to vehicles (fleet) index.
     */
    public function assets(Request $request): RedirectResponse
    {
        $companyId = (int) $request->query('company_id', 0);
        $params = $companyId > 0 ? ['company_id' => $companyId] : [];

        return redirect()->route('logistics.vehicles.index', $params);
    }

    public function vehicles(Request $request): View
    {
        $context = $this->workspaceContext->build($request);
        $context['pageTitle'] = __('Vehicles');
        $context['pageSubtitle'] = __('Register and manage fleet vehicles for the selected company.');

        return view('logistics.vehicles.index', $context);
    }

    public function drivers(Request $request): View
    {
        $context = $this->workspaceContext->build($request);
        $context['pageTitle'] = __('Drivers');
        $context['pageSubtitle'] = __('Register and manage drivers for the selected company.');

        return view('logistics.drivers.index', $context);
    }

    public function orders(Request $request): View
    {
        $context = $this->workspaceContext->build($request);
        $filters = [
            'status' => (string) $request->query('status', ''),
            'service_type' => (string) $request->query('service_type', ''),
            'transport_mode' => (string) $request->query('transport_mode', ''),
            'search' => trim((string) $request->query('search', '')),
        ];

        $context['orders'] = $this->filterOrders($context['orders'], $filters);
        $context['filters'] = $filters;
        $context['pageTitle'] = __('Orders');
        $context['pageSubtitle'] = __('Create and track logistics orders.');
        $context['actionLabel'] = __('Create Order');

        return view('logistics.orders.index', $context);
    }

    public function planning(Request $request): View
    {
        $context = $this->workspaceContext->build($request);
        $context['pageTitle'] = __('Trip Planning');
        $context['pageSubtitle'] = __('Assign confirmed orders to available driver and vehicle capacity.');
        $context['actionLabel'] = __('Plan Trip');

        return view('logistics.planning.index', $context);
    }

    public function trips(Request $request): View
    {
        $context = $this->workspaceContext->build($request);
        $context['pageTitle'] = __('Active Trips');
        $context['pageSubtitle'] = __('Monitor lifecycle and complete in-transit trips with outcomes.');
        $context['actionLabel'] = __('Start / Complete');

        return view('logistics.trips.index', $context);
    }

    public function tracking(Request $request): View
    {
        $context = $this->workspaceContext->build($request);
        $context['pageTitle'] = __('Tracking');
        $context['pageSubtitle'] = __('Capture real-time and delayed location updates without data loss.');
        $context['actionLabel'] = __('Add Tracking Log');

        return view('logistics.tracking.index', $context);
    }

    public function compliance(Request $request): View
    {
        $context = $this->workspaceContext->build($request);
        $context['pageTitle'] = __('Compliance');
        $context['pageSubtitle'] = __('Maintain valid trip documents before and during movement.');
        $context['actionLabel'] = __('Add Document');

        return view('logistics.compliance.index', $context);
    }

    public function billing(Request $request): View
    {
        $context = $this->workspaceContext->build($request);
        $context['pageTitle'] = __('Billing');
        $context['pageSubtitle'] = __('Generate invoices from trip execution and track payment status.');
        $context['actionLabel'] = __('Create Invoice');

        return view('logistics.billing.index', $context);
    }

    public function invoices(Request $request): RedirectResponse
    {
        $companyId = (int) $request->query('company_id', 0);
        $params = $companyId > 0 ? ['company_id' => $companyId] : [];

        return redirect()->route('logistics.billing.index', $params);
    }

    public function reporting(Request $request): RedirectResponse
    {
        $companyId = (int) $request->query('company_id', 0);
        $params = $companyId > 0 ? ['company_id' => $companyId] : [];

        return redirect()->route('logistics.dashboard.index', $params);
    }

    public function storeCompany(StoreCompanyRequest $request): RedirectResponse
    {
        $this->authorize('create', LogisticsCompany::class);
        $company = $this->companyService->create($request->user(), $request->validated());

        return redirect()->route('logistics.company.index', ['company_id' => $company->id])->with('status', __('Company registered.'));
    }

    public function showCompany(Request $request, LogisticsCompany $logistics_company): View
    {
        $this->authorize('view', $logistics_company);

        $logistics_company->load([
            'members',
            'business',
            'country',
            'province',
            'district',
            'sector',
            'cell',
            'village',
        ]);

        $context = $this->workspaceContext->build($request);
        $context['pageTitle'] = $logistics_company->name;
        $context['pageSubtitle'] = __('Company profile and members.');
        $context['logisticsCompany'] = $logistics_company;

        return view('logistics.company.show', $context);
    }

    public function editCompany(Request $request, LogisticsCompany $logistics_company): View
    {
        $this->authorize('update', $logistics_company);

        $logistics_company->load('members');

        $context = $this->workspaceContext->build($request);
        $context['pageTitle'] = __('Edit company');
        $context['pageSubtitle'] = $logistics_company->name;
        $context['logisticsCompany'] = $logistics_company;

        return view('logistics.company.edit', $context);
    }

    public function updateCompany(UpdateCompanyRequest $request, LogisticsCompany $logistics_company): RedirectResponse
    {
        $this->authorize('update', $logistics_company);
        $this->companyService->update($request->user(), $logistics_company, $request->validated());

        return redirect()
            ->route('logistics.company.show', $logistics_company)
            ->with('status', __('Company updated.'));
    }

    public function destroyCompany(Request $request, LogisticsCompany $logistics_company): RedirectResponse
    {
        $this->authorize('delete', $logistics_company);
        $this->companyService->delete($request->user(), $logistics_company);

        return redirect()
            ->route('logistics.company.index')
            ->with('status', __('Company deleted.'));
    }

    public function storeVehicle(StoreVehicleRequest $request): RedirectResponse
    {
        $this->authorize('create', LogisticsCompany::class);
        $payload = $request->validated();
        $this->vehicleService->create($request->user(), $payload);

        return redirect()->route('logistics.vehicles.index', ['company_id' => $payload['company_id']])->with('status', __('Vehicle added.'));
    }

    public function storeDriver(StoreDriverRequest $request): RedirectResponse
    {
        $this->authorize('create', LogisticsCompany::class);
        $payload = $request->validated();
        $this->driverService->create($request->user(), $payload);

        return redirect()->route('logistics.drivers.index', ['company_id' => $payload['company_id']])->with('status', __('Driver added.'));
    }

    public function storeOrder(StoreOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', LogisticsCompany::class);
        $payload = $request->validated();
        $this->orderService->create($request->user(), $payload);

        return redirect()->route('logistics.orders.index', ['company_id' => $payload['company_id']])->with('status', __('Order created.'));
    }

    public function planTrip(PlanTripRequest $request): RedirectResponse
    {
        $this->authorize('create', LogisticsCompany::class);
        $trip = $this->tripPlanningService->plan($request->user(), $request->validated());

        return redirect()->route('logistics.trips.index', ['company_id' => $trip->company_id])->with('status', __('Trip planned.'));
    }

    public function startTrip(StartTripRequest $request, LogisticsTrip $trip): RedirectResponse
    {
        $this->authorize('update', $trip);
        $this->tripExecutionService->start($request->user(), $trip, $request->validated('actual_departure'));

        return redirect()->route('logistics.trips.index', ['company_id' => $trip->company_id])->with('status', __('Trip started.'));
    }

    public function completeTrip(CompleteTripRequest $request, LogisticsTrip $trip): RedirectResponse
    {
        $this->authorize('update', $trip);
        $this->tripExecutionService->complete($request->user(), $trip, $request->validated());

        return redirect()->route('logistics.trips.index', ['company_id' => $trip->company_id])->with('status', __('Trip completed.'));
    }

    public function storeTracking(StoreTrackingLogRequest $request, LogisticsTrip $trip): RedirectResponse
    {
        $this->authorize('update', $trip);
        $this->trackingService->log($request->user(), $trip, $request->validated());

        return redirect()->route('logistics.tracking.index', ['company_id' => $trip->company_id])->with('status', __('Tracking logged.'));
    }

    public function storeCompliance(StoreComplianceDocumentRequest $request, LogisticsTrip $trip): RedirectResponse
    {
        $this->authorize('update', $trip);
        $this->complianceService->add($request->user(), $trip, $request->validated());

        return redirect()->route('logistics.compliance.index', ['company_id' => $trip->company_id])->with('status', __('Compliance document added.'));
    }

    public function storeInvoice(CreateInvoiceRequest $request, LogisticsTrip $trip): RedirectResponse
    {
        $this->authorize('update', $trip);
        $this->billingService->generate($request->user(), $trip, $request->validated());

        return redirect()->route('logistics.billing.index', ['company_id' => $trip->company_id])->with('status', __('Invoice generated.'));
    }

    /**
     * Filters are module-local by design and reset on route switch.
     */
    private function filterOrders(Collection $orders, array $filters): Collection
    {
        return $orders
            ->when($filters['status'] !== '', fn (Collection $rows): Collection => $rows->where('status', $filters['status']))
            ->when($filters['service_type'] !== '', fn (Collection $rows): Collection => $rows->where('service_type', $filters['service_type']))
            ->when($filters['transport_mode'] !== '', fn (Collection $rows): Collection => $rows->where('transport_mode', $filters['transport_mode']))
            ->when($filters['search'] !== '', function (Collection $rows) use ($filters): Collection {
                $needle = mb_strtolower($filters['search']);

                return $rows->filter(function ($order) use ($needle): bool {
                    return str_contains(mb_strtolower((string) $order->pickup_location), $needle)
                        || str_contains(mb_strtolower((string) $order->delivery_location), $needle)
                        || str_contains(mb_strtolower((string) ($order->order_number ?? '')), $needle)
                        || str_contains((string) $order->id, $needle);
                });
            })
            ->values();
    }
}
