<?php

namespace App\Http\Controllers\Processor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Processor\StoreProcessorSupplyRequestRequest;
use App\Models\Business;
use App\Models\FarmerHealthCertificate;
use App\Models\Facility;
use App\Models\Livestock;
use App\Models\SupplyRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProcessorSupplyRequestController extends Controller
{
    public function index(Request $request): View
    {
        $processorIds = $request->user()->accessibleProcessorBusinessIds();
        $requests = SupplyRequest::query()
            ->with(['farmer', 'destinationFacility'])
            ->whereIn('processor_id', $processorIds)
            ->latest()
            ->paginate(15);

        return view('processor.supply-requests.index', compact('requests'));
    }

    public function create(Request $request): View
    {
        $processorIds = $request->user()->accessibleProcessorBusinessIds();
        $processorBusinesses = Business::query()
            ->whereIn('id', $processorIds)
            ->orderBy('business_name')
            ->get();

        $facilities = Facility::query()
            ->whereIn('business_id', $processorIds)
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'business_id']);

        if ($facilities->isEmpty()) {
            return redirect()->route('businesses.hub')
                ->with('error', __('Add at least one facility to your processor business before requesting supply from farmers.'));
        }

        $livestockRows = Livestock::query()
            ->with([
                'detail',
                'farm.district',
                'farm.sector',
                'farm.cell',
                'farm.village',
                'farm.business',
            ])
            ->whereHas('farm.business', function ($query) {
                $query->where('type', Business::TYPE_FARMER)->where('status', Business::STATUS_ACTIVE);
            })
            ->where('healthy_quantity', '>', 0)
            ->orderBy('type')
            ->orderBy('breed')
            ->limit(300)
            ->get();

        $reservedByLivestock = SupplyRequest::query()
            ->whereIn('requested_livestock_id', $livestockRows->pluck('id'))
            ->whereIn('status', [SupplyRequest::STATUS_PENDING, SupplyRequest::STATUS_ACCEPTED])
            ->selectRaw('requested_livestock_id, SUM(quantity_requested) as reserved_total')
            ->groupBy('requested_livestock_id')
            ->pluck('reserved_total', 'requested_livestock_id');

        $certs = FarmerHealthCertificate::query()
            ->whereIn('farm_id', $livestockRows->pluck('farm_id')->unique()->values())
            ->where('status', FarmerHealthCertificate::STATUS_VALID)
            ->whereDate('issue_date', '<=', Carbon::today())
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', Carbon::today());
            })
            ->orderBy('expiry_date')
            ->get();

        $certsByFarm = $certs->groupBy('farm_id');
        $certsByLivestock = $certs->filter(fn ($c) => $c->livestock_id !== null)->groupBy('livestock_id');

        $discoveries = $livestockRows
            ->map(function (Livestock $row) use ($reservedByLivestock, $certsByFarm, $certsByLivestock): array {
                $reserved = (int) ($reservedByLivestock[$row->id] ?? 0);
                $available = max(0, (int) $row->healthy_quantity - $reserved);
                $weight = $this->extractAverageWeight($row->detail?->weight_range);
                $livestockCerts = collect($certsByLivestock[$row->id] ?? []);
                $farmCerts = collect($certsByFarm[$row->farm_id] ?? []);
                $validCerts = $livestockCerts->isNotEmpty() ? $livestockCerts : $farmCerts;

                return [
                    'livestock' => $row,
                    'available_quantity' => $available,
                    'reserved_quantity' => $reserved,
                    'average_weight' => $weight,
                    'location' => collect([
                        $row->farm?->district?->name,
                        $row->farm?->sector?->name,
                        $row->farm?->cell?->name,
                        $row->farm?->village?->name,
                    ])->filter()->implode(', '),
                    'health_status' => $row->health_status ?: $row->herd_health_status,
                    'has_valid_certification' => $validCerts->isNotEmpty(),
                    'certifications' => $validCerts->values(),
                ];
            })
            ->filter(fn (array $item) => $this->isLivestockAllowedForFarmerTenant($item['livestock']))
            ->filter(fn (array $item) => $item['available_quantity'] > 0)
            ->values();

        $selectedLivestockId = (int) $request->integer('livestock_id');
        $selectedDiscovery = $selectedLivestockId > 0
            ? $discoveries->first(fn (array $d) => $d['livestock']->id === $selectedLivestockId)
            : null;

        return view('processor.supply-requests.create', compact('processorBusinesses', 'facilities', 'discoveries', 'selectedDiscovery'));
    }

    public function store(StoreProcessorSupplyRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $livestock = Livestock::query()->with('farm')->findOrFail((int) $data['requested_livestock_id']);

        SupplyRequest::create([
            'processor_id' => $data['processor_business_id'],
            'farmer_id' => $livestock->farm->business_id,
            'destination_facility_id' => $data['destination_facility_id'],
            'animal_type' => $livestock->type,
            'quantity_requested' => $data['quantity_requested'],
            'required_breed' => $data['required_breed'] ?? null,
            'required_weight' => $data['required_weight'] ?? null,
            'healthy_stock_required' => $data['healthy_stock_required'] ?? true,
            'certification_required' => $data['certification_required'] ?? false,
            'required_certification_type' => $data['required_certification_type'] ?? null,
            'preferred_date' => $data['preferred_date'] ?? null,
            'source_farm_id' => $livestock->farm_id,
            'requested_livestock_id' => $livestock->id,
            'status' => SupplyRequest::STATUS_PENDING,
        ]);

        return redirect()->route('processor.supply-requests.index')
            ->with('status', __('Supply request sent to the farmer.'));
    }

    private function extractAverageWeight(?string $weightRange): ?float
    {
        if ($weightRange === null || trim($weightRange) === '') {
            return null;
        }

        preg_match_all('/\d+(?:\.\d+)?/', $weightRange, $matches);
        $values = array_map('floatval', $matches[0] ?? []);
        if ($values === []) {
            return null;
        }

        return round(array_sum($values) / count($values), 2);
    }

    private function isLivestockAllowedForFarmerTenant(Livestock $livestock): bool
    {
        $farmerBusiness = $livestock->farm?->business;
        if (! $farmerBusiness) {
            return false;
        }

        $configured = $farmerBusiness->activeConfiguredSpecies()
            ->pluck('name')
            ->map(fn (string $value) => $this->normalizeSpeciesToken($value))
            ->filter()
            ->values();

        if ($configured->isEmpty()) {
            return true;
        }

        return $configured->contains($this->normalizeSpeciesToken((string) $livestock->type));
    }

    private function normalizeSpeciesToken(string $value): string
    {
        $token = Str::lower(trim($value));
        $aliases = [
            'cow' => 'cattle',
            'cows' => 'cattle',
            'cattle' => 'cattle',
            'goat' => 'goat',
            'goats' => 'goat',
            'sheep' => 'sheep',
            'pig' => 'pig',
            'pigs' => 'pig',
            'poultry' => 'poultry',
            'rabbit' => 'rabbit',
            'rabbits' => 'rabbit',
        ];

        return $aliases[$token] ?? Str::singular($token);
    }
}
