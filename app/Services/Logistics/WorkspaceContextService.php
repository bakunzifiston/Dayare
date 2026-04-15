<?php

namespace App\Services\Logistics;

use App\Models\Business;
use App\Models\User;
use App\Repositories\Logistics\ComplianceRepository;
use App\Repositories\Logistics\DriverRepository;
use App\Repositories\Logistics\InvoiceRepository;
use App\Repositories\Logistics\OrderRepository;
use App\Repositories\Logistics\TrackingRepository;
use App\Repositories\Logistics\TripRepository;
use App\Repositories\Logistics\VehicleRepository;
use Illuminate\Http\Request;

class WorkspaceContextService
{
    public function __construct(
        private CompanyService $companyService,
        private VehicleRepository $vehicles,
        private DriverRepository $drivers,
        private OrderRepository $orders,
        private TripRepository $trips,
        private TrackingRepository $tracking,
        private ComplianceRepository $compliance,
        private InvoiceRepository $invoices
    ) {}

    public function build(Request $request, bool $withOperationalData = true): array
    {
        /** @var User $user */
        $user = $request->user();
        $companies = $this->companyService->list($user);
        $selectedCompanyId = (int) ($request->query('company_id') ?? $request->session()->get('logistics.selected_company_id', 0));

        if ($selectedCompanyId === 0 && $companies->isNotEmpty()) {
            $selectedCompanyId = (int) $companies->first()->id;
        }

        if ($selectedCompanyId > 0) {
            $request->session()->put('logistics.selected_company_id', $selectedCompanyId);
        }

        $vehicles = collect();
        $drivers = collect();
        $orders = collect();
        $trips = collect();
        $trackingLogs = collect();
        $complianceDocuments = collect();
        $invoices = collect();

        if ($withOperationalData && $selectedCompanyId > 0) {
            $company = $this->companyService->requireAccessible($user, $selectedCompanyId);
            $vehicles = $this->vehicles->byCompany((int) $company->id);
            $drivers = $this->drivers->byCompany((int) $company->id);
            $orders = $this->orders->byCompany((int) $company->id);
            $trips = $this->trips->byCompany((int) $company->id);
            $tripIds = $trips->pluck('id')->all();

            if ($tripIds !== []) {
                $trackingLogs = $this->tracking->byTripIds($tripIds)->take(50);
                $complianceDocuments = $this->compliance->byTripIds($tripIds)->take(50);
                $invoices = $this->invoices->byTripIds($tripIds);
            }
        }

        $logisticsBusinesses = Business::query()
            ->where('type', Business::TYPE_LOGISTICS)
            ->whereIn('id', $user->accessibleBusinessIds())
            ->orderBy('business_name')
            ->get(['id', 'business_name']);

        return compact(
            'companies',
            'selectedCompanyId',
            'logisticsBusinesses',
            'vehicles',
            'drivers',
            'orders',
            'trips',
            'trackingLogs',
            'complianceDocuments',
            'invoices'
        );
    }
}
