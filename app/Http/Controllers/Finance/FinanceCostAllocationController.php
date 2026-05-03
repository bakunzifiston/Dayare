<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\FinanceCostAllocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FinanceCostAllocationController extends Controller
{
    public function index(Request $request): View
    {
        $businessId = $this->activeBusinessId($request);
        $query = FinanceCostAllocation::query()
            ->with(['batch', 'creator'])
            ->where('business_id', $businessId);

        if ($request->filled('category')) {
            $query->where('category', (string) $request->query('category'));
        }
        if ($request->filled('batch_id')) {
            $query->where('batch_id', (int) $request->query('batch_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('allocation_date', '>=', (string) $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('allocation_date', '<=', (string) $request->query('to'));
        }

        $allocations = $query->orderByDesc('allocation_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $totalAmount = (float) (clone $query)->sum('amount');

        return view('finance.cost-allocations.index', [
            'allocations' => $allocations,
            'batches' => $this->businessBatches($businessId),
            'totalAmount' => $totalAmount,
            'filters' => [
                'category' => (string) $request->query('category', ''),
                'batch_id' => (string) $request->query('batch_id', ''),
                'from' => (string) $request->query('from', ''),
                'to' => (string) $request->query('to', ''),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $businessId = $this->activeBusinessId($request);

        return view('finance.cost-allocations.create', [
            'allocation' => null,
            'batches' => $this->businessBatches($businessId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        $data = $this->validated($request, $businessId);

        $allocation = FinanceCostAllocation::query()->create([
            'business_id' => $businessId,
            'batch_id' => $data['batch_id'],
            'source_type' => $data['source_type'],
            'source_id' => $data['source_id'],
            'created_by' => $request->user()->id,
            'category' => $data['category'],
            'amount' => $data['amount'],
            'allocation_date' => $data['allocation_date'],
            'notes' => $data['notes'],
        ]);

        return redirect()->route('finance.cost-allocations.edit', $allocation)->with('status', __('Cost allocation created.'));
    }

    public function edit(Request $request, FinanceCostAllocation $costAllocation): View
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $costAllocation->business_id === $businessId, 404);

        return view('finance.cost-allocations.edit', [
            'allocation' => $costAllocation,
            'batches' => $this->businessBatches($businessId),
        ]);
    }

    public function update(Request $request, FinanceCostAllocation $costAllocation): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $costAllocation->business_id === $businessId, 404);
        $data = $this->validated($request, $businessId);

        $costAllocation->update([
            'batch_id' => $data['batch_id'],
            'source_type' => $data['source_type'],
            'source_id' => $data['source_id'],
            'category' => $data['category'],
            'amount' => $data['amount'],
            'allocation_date' => $data['allocation_date'],
            'notes' => $data['notes'],
        ]);

        return redirect()->route('finance.cost-allocations.edit', $costAllocation)->with('status', __('Cost allocation updated.'));
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        $data = $request->validate([
            'category' => ['required', 'string', 'max:50'],
            'allocation_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'gt:0'],
            'distribution_mode' => ['required', 'in:equal,quantity'],
            'distribution_scope' => ['required', 'in:all,selected'],
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => ['integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $batches = $this->businessBatches($businessId);
        if ($data['distribution_scope'] === 'selected') {
            $selectedIds = collect($data['batch_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            $batches = $batches->whereIn('id', $selectedIds)->values();
        }

        abort_if($batches->isEmpty(), 422, __('Select at least one batch for allocation.'));

        $allocations = $this->buildTemplateAllocations(
            $batches,
            (float) $data['total_amount'],
            (string) $data['distribution_mode']
        );

        foreach ($allocations as $alloc) {
            FinanceCostAllocation::query()->create([
                'business_id' => $businessId,
                'batch_id' => $alloc['batch_id'],
                'source_type' => 'template',
                'source_id' => null,
                'created_by' => $request->user()->id,
                'category' => $data['category'],
                'amount' => $alloc['amount'],
                'allocation_date' => $data['allocation_date'],
                'notes' => $data['notes'],
            ]);
        }

        return redirect()
            ->route('finance.cost-allocations.index')
            ->with('status', __('Template allocation created across :count batch(es).', ['count' => count($allocations)]));
    }

    private function activeBusinessId(Request $request): int
    {
        $businessId = $request->user()->activeProcessorBusinessId();
        abort_if($businessId === null, 403, __('Select a processor business first.'));
        $request->user()->setActiveProcessorBusinessId($businessId);

        return $businessId;
    }

    private function validated(Request $request, int $businessId): array
    {
        $data = $request->validate([
            'batch_id' => ['required', 'integer'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'category' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'gte:0'],
            'allocation_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $batchExists = Batch::query()
            ->whereKey($data['batch_id'])
            ->whereHas('slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
            ->exists();
        abort_unless($batchExists, 422, __('Invalid batch selection.'));

        $data['amount'] = round((float) $data['amount'], 2);

        return $data;
    }

    private function businessBatches(int $businessId): Collection
    {
        return Batch::query()
            ->whereHas('slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('id')
            ->limit(150)
            ->get(['id', 'batch_code', 'quantity']);
    }

    /**
     * @return array<int, array{batch_id: int, amount: float}>
     */
    private function buildTemplateAllocations(Collection $batches, float $totalAmount, string $mode): array
    {
        $batchList = $batches->values();
        $count = max(1, $batchList->count());
        $result = [];

        if ($mode === 'quantity') {
            $weights = $batchList->map(fn ($b) => max(0.0001, (float) ($b->quantity ?? 0)));
            $weightSum = max(0.0001, (float) $weights->sum());
            $running = 0.0;

            foreach ($batchList as $index => $batch) {
                $raw = $totalAmount * ($weights[$index] / $weightSum);
                $amount = $index === ($count - 1)
                    ? round($totalAmount - $running, 2)
                    : round($raw, 2);
                $running += $amount;
                $result[] = ['batch_id' => (int) $batch->id, 'amount' => $amount];
            }

            return $result;
        }

        $perBatch = round($totalAmount / $count, 2);
        $running = 0.0;
        foreach ($batchList as $index => $batch) {
            $amount = $index === ($count - 1)
                ? round($totalAmount - $running, 2)
                : $perBatch;
            $running += $amount;
            $result[] = ['batch_id' => (int) $batch->id, 'amount' => $amount];
        }

        return $result;
    }
}
