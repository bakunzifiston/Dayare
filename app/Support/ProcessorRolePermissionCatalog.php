<?php

namespace App\Support;

use App\Models\BusinessUser;

class ProcessorRolePermissionCatalog
{
    /**
     * Permissions grouped by the workspace module presented to administrators.
     *
     * @return array<string, array{
     *     label: string,
     *     description: string,
     *     permissions: array<string, string>
     * }>
     */
    public static function groups(): array
    {
        return [
            'workspace' => [
                'label' => __('Workspace administration'),
                'description' => __('Cross-module visibility and team administration.'),
                'permissions' => [
                    BusinessUser::PERMISSION_VIEW_PROCESSOR_DASHBOARD => __('View processor dashboard'),
                    BusinessUser::PERMISSION_VIEW_ALL_MODULES => __('View all modules'),
                    BusinessUser::PERMISSION_MANAGE_BUSINESS_USERS => __('View and manage business users'),
                    BusinessUser::PERMISSION_ASSIGN_BUSINESS_ROLES => __('Assign users to business roles'),
                ],
            ],
            'operations' => [
                'label' => __('Operations'),
                'description' => __('Animal intake, slaughter planning, processing batches, and inspector assignment.'),
                'permissions' => [
                    BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE => __('Manage animal intake'),
                    BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER => __('Manage slaughter plans and executions'),
                    BusinessUser::PERMISSION_CREATE_BATCH => __('Manage processing batches'),
                    BusinessUser::PERMISSION_ASSIGN_BATCH_TO_INSPECTOR => __('Manage inspectors and batch assignments'),
                ],
            ],
            'inspections' => [
                'label' => __('Inspections and certificates'),
                'description' => __('Inspection visibility, assigned batches, ante/post-mortem records, and certificates.'),
                'permissions' => [
                    BusinessUser::PERMISSION_VIEW_INSPECTIONS => __('View inspections and monthly reports'),
                    BusinessUser::PERMISSION_VIEW_ASSIGNED_BATCHES => __('View assigned batches'),
                    BusinessUser::PERMISSION_RECORD_ANTE_MORTEM => __('Record ante-mortem inspections'),
                    BusinessUser::PERMISSION_RECORD_POST_MORTEM => __('Record post-mortem inspections'),
                    BusinessUser::PERMISSION_VIEW_CERTIFICATES => __('View certificates'),
                    BusinessUser::PERMISSION_ISSUE_CERTIFICATE => __('Issue and update certificates'),
                ],
            ],
            'compliance' => [
                'label' => __('Compliance and cold chain'),
                'description' => __('Compliance controls, evidence, metrics, and temperature monitoring.'),
                'permissions' => [
                    BusinessUser::PERMISSION_SUBMIT_CHECKLIST => __('Submit compliance checklists'),
                    BusinessUser::PERMISSION_LOG_NON_COMPLIANCE => __('Manage non-compliance records'),
                    BusinessUser::PERMISSION_UPLOAD_COMPLIANCE_EVIDENCE => __('Upload compliance evidence'),
                    BusinessUser::PERMISSION_MONITOR_COMPLIANCE_METRICS => __('View compliance metrics'),
                    BusinessUser::PERMISSION_MONITOR_TEMPERATURE_LOGS => __('View cold rooms and temperature logs'),
                ],
            ],
            'transport' => [
                'label' => __('Transport and delivery'),
                'description' => __('Trip creation, dispatch, vehicle assignment, tracking, and delivery confirmation.'),
                'permissions' => [
                    BusinessUser::PERMISSION_CREATE_TRANSPORT_TRIP => __('Create transport trips'),
                    BusinessUser::PERMISSION_ASSIGN_VEHICLE_DRIVER => __('Assign vehicles and drivers'),
                    BusinessUser::PERMISSION_DISPATCH_DELIVERY => __('Update and dispatch deliveries'),
                    BusinessUser::PERMISSION_TRACK_DELIVERY_STATUS => __('View and track deliveries'),
                    BusinessUser::PERMISSION_CONFIRM_DELIVERY => __('Confirm deliveries'),
                ],
            ],
            'exports' => [
                'label' => __('Exports'),
                'description' => __('Operational exports, traceability exports, and export documents.'),
                'permissions' => [
                    BusinessUser::PERMISSION_EXPORT_RECORDS => __('Export operational records'),
                    BusinessUser::PERMISSION_EXPORT_TRACEABILITY => __('Export traceability records'),
                    BusinessUser::PERMISSION_VIEW_EXPORT_DOCUMENTS => __('View export documents'),
                    BusinessUser::PERMISSION_MANAGE_EXPORT_DOCUMENTS => __('Manage export documents'),
                ],
            ],
            'finance' => [
                'label' => __('Finance'),
                'description' => __('Finance dashboard, receivables, payables, and cost reports.'),
                'permissions' => [
                    BusinessUser::PERMISSION_VIEW_FINANCE_DASHBOARD => __('View finance dashboard'),
                    BusinessUser::PERMISSION_MANAGE_AR_INVOICES => __('Manage accounts receivable invoices'),
                    BusinessUser::PERMISSION_MANAGE_AP_PAYABLES => __('Manage accounts payable and casual workers'),
                    BusinessUser::PERMISSION_VIEW_FINANCE_REPORTS => __('View finance reports and cost allocations'),
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function permissionLabels(): array
    {
        $labels = [];

        foreach (self::groups() as $group) {
            foreach ($group['permissions'] as $permission => $label) {
                $labels[$permission] = $label;
            }
        }

        return $labels;
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    public static function labelsForPermissions(array $permissions, int $limit = 8): array
    {
        $labels = self::permissionLabels();

        return collect($permissions)
            ->map(fn (string $permission): string => $labels[$permission] ?? $permission)
            ->take($limit)
            ->values()
            ->all();
    }
}
