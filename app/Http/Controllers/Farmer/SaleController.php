<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleFarmerBusiness;
use App\Http\Requests\Farmer\StoreSaleRequest;
use App\Http\Requests\Farmer\UpdateSaleRequest;
use App\Models\Buyer;
use App\Models\Farm;
use App\Models\MovementPermit;
use App\Models\Sale;
use App\Services\Farmer\SaleHistoryService;
use App\Services\Farmer\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SaleController extends Controller
{
    use InteractsWithAccessibleAnimals;
    use InteractsWithAccessibleFarmerBusiness;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);

        $farmIds = $this->accessibleFarmIds($request);
        $query = Sale::query()->whereIn('farm_id', $farmIds)->with(['buyer', 'farm'])->latest('sale_date');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('sale_number', 'like', '%'.$search.'%')
                    ->orWhere('destination', 'like', '%'.$search.'%');
            });
        }

        foreach (['sale_type', 'sale_status', 'payment_status'] as $filter) {
            if ($value = (string) $request->query($filter, '')) {
                $query->where($filter, $value);
            }
        }

        $records = $query->paginate(20)->withQueryString();

        return view('farmer.sales.records.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Sale::class);

        return view('farmer.sales.records.create', [
            'farms' => $this->accessibleFarms($request),
            'buyers' => $this->accessibleBuyers($request),
            'animals' => $this->accessibleAnimalsQuery($request)->where('lifecycle_status', \App\Models\Animal::LIFECYCLE_ACTIVE)->orderBy('animal_code')->get(),
            'livestock' => $this->accessibleLivestockQuery($request)->orderBy('livestock_code')->get(),
            'permits' => $this->accessiblePermits($request),
        ]);
    }

    public function store(StoreSaleRequest $request, SaleService $service, SaleHistoryService $history): RedirectResponse
    {
        $this->authorize('create', Sale::class);
        $data = $this->payloadFromRequest($request);
        $sale = $service->create($data, $request->user()->id, $history->requestIp($request));

        return redirect()->route('farmer.sales.records.show', $sale)->with('status', __('Sale created.'));
    }

    public function show(Sale $sale): View
    {
        $this->authorize('view', $sale);
        $sale->load(['farm.business', 'buyer', 'saleAnimals.animal', 'saleAnimals.livestock', 'payments.receiver', 'documents', 'logs.actor', 'movementPermit', 'approver']);

        return view('farmer.sales.records.show', compact('sale'));
    }

    public function edit(Request $request, Sale $sale): View
    {
        $this->authorize('update', $sale);
        abort_unless($sale->isEditable(), 403);

        $sale->load('saleAnimals');

        return view('farmer.sales.records.edit', [
            'sale' => $sale,
            'farms' => $this->accessibleFarms($request),
            'buyers' => $this->accessibleBuyers($request),
            'animals' => $this->accessibleAnimalsQuery($request)->where('lifecycle_status', \App\Models\Animal::LIFECYCLE_ACTIVE)->orderBy('animal_code')->get(),
            'livestock' => $this->accessibleLivestockQuery($request)->orderBy('livestock_code')->get(),
            'permits' => $this->accessiblePermits($request),
        ]);
    }

    public function update(UpdateSaleRequest $request, Sale $sale, SaleService $service, SaleHistoryService $history): RedirectResponse
    {
        $this->authorize('update', $sale);
        $data = $this->payloadFromRequest($request);
        $service->update($sale, $data, $request->user()->id, $history->requestIp($request));

        return redirect()->route('farmer.sales.records.show', $sale)->with('status', __('Sale updated.'));
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $this->authorize('delete', $sale);
        $sale->delete();

        return redirect()->route('farmer.sales.records.index')->with('status', __('Sale archived.'));
    }

    public function confirm(Request $request, Sale $sale, SaleService $service, SaleHistoryService $history): RedirectResponse
    {
        $this->authorize('update', $sale);
        $service->confirm($sale, $request->user()->id, $history->requestIp($request));

        return back()->with('status', __('Sale confirmed.'));
    }

    public function approve(Request $request, Sale $sale, SaleService $service, SaleHistoryService $history): RedirectResponse
    {
        $this->authorize('approve', $sale);
        $service->approve($sale, $request->user()->id, $history->requestIp($request));

        return back()->with('status', __('Sale approved.'));
    }

    public function complete(Request $request, Sale $sale, SaleService $service, SaleHistoryService $history): RedirectResponse
    {
        $this->authorize('complete', $sale);
        $service->complete($sale, $request->user()->id, $history->requestIp($request));

        return back()->with('status', __('Sale completed.'));
    }

    public function cancel(Request $request, Sale $sale, SaleService $service, SaleHistoryService $history): RedirectResponse
    {
        $this->authorize('update', $sale);
        $service->cancel($sale, $request->user()->id, $history->requestIp($request), $request->input('notes'));

        return back()->with('status', __('Sale cancelled.'));
    }

    /** @return array<string, mixed> */
    private function payloadFromRequest(StoreSaleRequest $request): array
    {
        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('sales/attachments', 'public');
        }

        return $data;
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    private function accessibleFarmIds(Request $request)
    {
        return Farm::query()
            ->whereIn('business_id', $this->accessibleBusinessIds($request))
            ->pluck('id');
    }

    private function accessibleFarms(Request $request)
    {
        return Farm::query()
            ->whereIn('business_id', $this->accessibleBusinessIds($request))
            ->orderBy('name')
            ->get();
    }

    private function accessibleBuyers(Request $request)
    {
        return Buyer::query()
            ->whereIn('business_id', $this->accessibleBusinessIds($request))
            ->where('status', '!=', Buyer::STATUS_BLACKLISTED)
            ->orderBy('buyer_name')
            ->get();
    }

    private function accessiblePermits(Request $request)
    {
        $farmIds = $this->accessibleFarmIds($request);

        return MovementPermit::query()
            ->whereIn('source_farm_id', $farmIds)
            ->latest('issue_date')
            ->get();
    }
}
