<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\RicaSetting;
use App\Models\User;
use App\Support\TenantEnvironmentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RicaSettingsController extends Controller
{
    public function edit(): View
    {
        return view('superadmin.rica.settings', [
            'settings' => RicaSetting::allMerged(),
            'tenantEnvironmentOptions' => TenantEnvironmentScope::filterOptions(),
            'dashboardPeriodOptions' => [
                'all' => __('All time'),
                'day' => __('Daily'),
                'month' => __('Monthly'),
                'year' => __('Yearly'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'workspace_name' => ['required', 'string', 'max:255'],
            'default_tenant_environment' => ['required', 'string', Rule::in([
                User::TENANT_ENVIRONMENT_LIVE,
                User::TENANT_ENVIRONMENT_TEST,
                TenantEnvironmentScope::FILTER_ALL,
            ])],
            'default_dashboard_period' => ['required', 'string', Rule::in(['all', 'day', 'month', 'year'])],
            'notification_email' => ['nullable', 'email', 'max:255'],
            'monthly_report_deadline_day' => ['required', 'integer', 'min:1', 'max:28'],
            'condemnation_loss_per_kg_rwf' => ['required', 'numeric', 'min:100', 'max:50000'],
        ]);

        RicaSetting::setMany([
            'workspace_name' => $data['workspace_name'],
            'default_tenant_environment' => $data['default_tenant_environment'],
            'default_dashboard_period' => $data['default_dashboard_period'],
            'notification_email' => $data['notification_email'] ?? '',
            'monthly_report_deadline_day' => (string) $data['monthly_report_deadline_day'],
            'condemnation_loss_per_kg_rwf' => (string) $data['condemnation_loss_per_kg_rwf'],
        ]);

        return redirect()
            ->route('rica.settings')
            ->with('status', __('RICA settings updated successfully.'));
    }
}
