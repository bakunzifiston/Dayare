<?php

namespace App\Http\Controllers;

use App\Services\Logistics\WorkspaceContextService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogisticsDashboardController extends Controller
{
    public function __construct(private WorkspaceContextService $workspaceContext) {}

    public function __invoke(Request $request): View
    {
        $context = $this->workspaceContext->build($request, withOperationalData: false);
        $context['user'] = $request->user();

        return view('logistics.dashboard', $context);
    }
}
