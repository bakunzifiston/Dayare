<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CasualWorker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceCasualWorkerController extends Controller
{
    public function index(Request $request): View
    {
        $businessId = $this->activeBusinessId($request);
        $workers = CasualWorker::query()
            ->where('business_id', $businessId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('finance.casual-workers.index', [
            'workers' => $workers,
        ]);
    }

    public function create(Request $request): View
    {
        $this->activeBusinessId($request);

        return view('finance.casual-workers.create', [
            'worker' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'national_id' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['business_id'] = $businessId;
        $data['is_active'] = $request->boolean('is_active', true);

        $worker = CasualWorker::query()->create($data);

        return redirect()->route('finance.casual-workers.edit', $worker)->with('status', __('Casual worker saved.'));
    }

    public function edit(Request $request, CasualWorker $casual_worker): View
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $casual_worker->business_id === $businessId, 404);

        return view('finance.casual-workers.edit', [
            'worker' => $casual_worker,
        ]);
    }

    public function update(Request $request, CasualWorker $casual_worker): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $casual_worker->business_id === $businessId, 404);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'national_id' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $casual_worker->update($data);

        return redirect()->route('finance.casual-workers.edit', $casual_worker)->with('status', __('Casual worker updated.'));
    }

    public function destroy(Request $request, CasualWorker $casual_worker): RedirectResponse
    {
        $businessId = $this->activeBusinessId($request);
        abort_unless((int) $casual_worker->business_id === $businessId, 404);

        if ($casual_worker->financePayables()->exists()) {
            return redirect()->route('finance.casual-workers.index')
                ->with('error', __('This casual worker cannot be deleted while linked to payables.'));
        }

        $casual_worker->delete();

        return redirect()->route('finance.casual-workers.index')->with('status', __('Casual worker removed.'));
    }

    private function activeBusinessId(Request $request): int
    {
        $businessId = $request->user()->activeProcessorBusinessId();
        abort_if($businessId === null, 403, __('Select a processor business first.'));
        $request->user()->setActiveProcessorBusinessId($businessId);

        return $businessId;
    }
}
