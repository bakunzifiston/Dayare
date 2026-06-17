<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherExpenseRequest;
use App\Http\Requests\Butcher\UpdateButcherExpenseRequest;
use App\Models\ButcherExpense;
use App\Models\ButcherOutlet;
use App\Services\Butcher\ButcherFinanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ButcherFinanceController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherFinanceService $finance,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        [$from, $to] = $this->parsePeriod($request);

        return view('butcher.finance.index', [
            'business' => $business,
            'summary' => $this->finance->getFinanceSummary($business, $from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function expensesIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        [$from, $to] = $this->parsePeriod($request);

        $expenses = $business->butcherExpenses()
            ->with(['outlet', 'recordedByUser'])
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->latest('expense_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $totalsByCategory = $business->butcherExpenses()
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return view('butcher.finance.expenses.index', [
            'business' => $business,
            'expenses' => $expenses,
            'totalsByCategory' => $totalsByCategory,
            'totalAmount' => round((float) $totalsByCategory->sum(), 2),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function expensesCreate(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        return view('butcher.finance.expenses.create', [
            'business' => $business,
            'outlets' => $business->butcherOutlets()->where('status', ButcherOutlet::STATUS_ACTIVE)->orderBy('name')->get(),
            'categories' => ButcherExpense::CATEGORIES,
            'paymentMethods' => ButcherExpense::PAYMENT_METHODS,
            'expense' => null,
        ]);
    }

    public function expensesStore(StoreButcherExpenseRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $data = $request->validated();
        $data['receipt_path'] = $this->storeReceipt($request, $business->id);

        $this->finance->logExpense($business, $data, $request->user());

        return redirect()
            ->route('butcher.finance.expenses.index')
            ->with('status', __('Expense recorded.'));
    }

    public function expensesEdit(Request $request, ButcherExpense $expense): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $expense->business_id === (int) $business->id, 404);

        return view('butcher.finance.expenses.create', [
            'business' => $business,
            'outlets' => $business->butcherOutlets()->where('status', ButcherOutlet::STATUS_ACTIVE)->orderBy('name')->get(),
            'categories' => ButcherExpense::CATEGORIES,
            'paymentMethods' => ButcherExpense::PAYMENT_METHODS,
            'expense' => $expense,
        ]);
    }

    public function expensesUpdate(UpdateButcherExpenseRequest $request, ButcherExpense $expense): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $expense->business_id === (int) $business->id, 404);

        $data = $request->validated();
        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $data['receipt_path'] = $this->storeReceipt($request, $business->id);
        }

        $this->finance->updateExpense($expense, $data);

        return redirect()
            ->route('butcher.finance.expenses.index')
            ->with('status', __('Expense updated.'));
    }

    public function expensesDestroy(Request $request, ButcherExpense $expense): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $expense->business_id === (int) $business->id, 404);

        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $expense->delete();

        return redirect()
            ->route('butcher.finance.expenses.index')
            ->with('status', __('Expense deleted.'));
    }

    public function reportsSales(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        [$from, $to] = $this->parsePeriod($request);
        $groupBy = (string) $request->query('group_by', 'day');

        return view('butcher.finance.reports.sales', [
            'business' => $business,
            'report' => $this->finance->getSalesReport($business, $groupBy, $from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'groupBy' => $groupBy,
        ]);
    }

    public function reportsPl(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        [$from, $to] = $this->parsePeriod($request);

        return view('butcher.finance.reports.pl', [
            'business' => $business,
            'pl' => $this->finance->getProfitAndLoss($business, $from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function reportsCashflow(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        [$from, $to] = $this->parsePeriod($request);

        return view('butcher.finance.reports.cashflow', [
            'business' => $business,
            'cashflow' => $this->finance->getCashFlow($business, $from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function reportsExport(Request $request): StreamedResponse|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        [$from, $to] = $this->parsePeriod($request);
        $type = (string) $request->query('type', 'pl');
        $format = (string) $request->query('format', 'csv');

        if (! in_array($type, ['sales', 'pl', 'cashflow', 'expenses'], true)) {
            $type = 'pl';
        }
        if (! in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            $format = 'csv';
        }

        $path = $this->finance->exportReport($business, $type, $from, $to, $format);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download(
            $path,
            sprintf('butcher-%s-%s-%s.%s', $type, $from->format('Ymd'), $to->format('Ymd'), $extension)
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function parsePeriod(Request $request): array
    {
        $from = Carbon::parse($request->query('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', now()->toDateString()))->endOfDay();

        return [$from, $to];
    }

    private function storeReceipt(Request $request, int $businessId): ?string
    {
        if (! $request->hasFile('receipt')) {
            return null;
        }

        return $request->file('receipt')->store("butcher-expenses/{$businessId}", 'public');
    }
}
