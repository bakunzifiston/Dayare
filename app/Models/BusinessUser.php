<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BusinessUser extends Pivot
{
    protected $table = 'business_user';

    public $incrementing = true;

    protected $fillable = ['business_id', 'user_id', 'role'];

    public const ROLE_AUDITOR = 'auditor';
    public const ROLE_ORG_ADMIN = 'org_admin';
    public const ROLE_COMPLIANCE_OFFICER = 'compliance_officer';
    public const ROLE_INSPECTOR = 'inspector';
    public const ROLE_OPERATIONS_MANAGER = 'operations_manager';
    public const ROLE_LOGISTICS_MANAGER = 'logistics_manager';
    public const ROLE_DRIVER = 'driver';
    public const ROLE_BUYER = 'buyer';
    public const ROLE_FARMER = 'farmer';
    public const ROLE_PROGRAMME_MANAGER = 'programme_manager';
    public const ROLE_COLD_ROOM_OPERATOR = 'cold_room_operator';

    public const ROLES = [
        self::ROLE_AUDITOR,
        self::ROLE_ORG_ADMIN,
        self::ROLE_COMPLIANCE_OFFICER,
        self::ROLE_INSPECTOR,
        self::ROLE_OPERATIONS_MANAGER,
        self::ROLE_LOGISTICS_MANAGER,
        self::ROLE_DRIVER,
        self::ROLE_BUYER,
        self::ROLE_FARMER,
        self::ROLE_PROGRAMME_MANAGER,
        self::ROLE_COLD_ROOM_OPERATOR,
    ];

    public const PERMISSION_VIEW_ALL_MODULES = 'view_all_modules';
    public const PERMISSION_MANAGE_BUSINESS_USERS = 'manage_business_users';
    public const PERMISSION_ASSIGN_BUSINESS_ROLES = 'assign_business_roles';
    public const PERMISSION_CREATE_ANIMAL_INTAKE = 'create_animal_intake';
    public const PERMISSION_SCHEDULE_SLAUGHTER = 'schedule_slaughter';
    public const PERMISSION_CREATE_BATCH = 'create_batch';
    public const PERMISSION_ASSIGN_BATCH_TO_INSPECTOR = 'assign_batch_to_inspector';
    public const PERMISSION_VIEW_INSPECTIONS = 'view_inspections';
    public const PERMISSION_RECORD_ANTE_MORTEM = 'record_ante_mortem';
    public const PERMISSION_RECORD_POST_MORTEM = 'record_post_mortem';
    public const PERMISSION_ISSUE_CERTIFICATE = 'issue_certificate';
    public const PERMISSION_VIEW_CERTIFICATES = 'view_certificates';
    public const PERMISSION_SUBMIT_CHECKLIST = 'submit_checklist';
    public const PERMISSION_LOG_NON_COMPLIANCE = 'log_non_compliance';
    public const PERMISSION_UPLOAD_COMPLIANCE_EVIDENCE = 'upload_compliance_evidence';
    public const PERMISSION_MONITOR_COMPLIANCE_METRICS = 'monitor_compliance_metrics';
    public const PERMISSION_CREATE_TRANSPORT_TRIP = 'create_transport_trip';
    public const PERMISSION_ASSIGN_VEHICLE_DRIVER = 'assign_vehicle_driver';
    public const PERMISSION_DISPATCH_DELIVERY = 'dispatch_delivery';
    public const PERMISSION_TRACK_DELIVERY_STATUS = 'track_delivery_status';
    public const PERMISSION_CONFIRM_DELIVERY = 'confirm_delivery';
    public const PERMISSION_MONITOR_TEMPERATURE_LOGS = 'monitor_temperature_logs';
    public const PERMISSION_VIEW_ASSIGNED_BATCHES = 'view_assigned_batches';

    public const ACTION_PERMISSIONS = [
        self::PERMISSION_VIEW_ALL_MODULES,
        self::PERMISSION_MANAGE_BUSINESS_USERS,
        self::PERMISSION_ASSIGN_BUSINESS_ROLES,
        self::PERMISSION_CREATE_ANIMAL_INTAKE,
        self::PERMISSION_SCHEDULE_SLAUGHTER,
        self::PERMISSION_CREATE_BATCH,
        self::PERMISSION_ASSIGN_BATCH_TO_INSPECTOR,
        self::PERMISSION_VIEW_INSPECTIONS,
        self::PERMISSION_RECORD_ANTE_MORTEM,
        self::PERMISSION_RECORD_POST_MORTEM,
        self::PERMISSION_ISSUE_CERTIFICATE,
        self::PERMISSION_VIEW_CERTIFICATES,
        self::PERMISSION_SUBMIT_CHECKLIST,
        self::PERMISSION_LOG_NON_COMPLIANCE,
        self::PERMISSION_UPLOAD_COMPLIANCE_EVIDENCE,
        self::PERMISSION_MONITOR_COMPLIANCE_METRICS,
        self::PERMISSION_CREATE_TRANSPORT_TRIP,
        self::PERMISSION_ASSIGN_VEHICLE_DRIVER,
        self::PERMISSION_DISPATCH_DELIVERY,
        self::PERMISSION_TRACK_DELIVERY_STATUS,
        self::PERMISSION_CONFIRM_DELIVERY,
        self::PERMISSION_MONITOR_TEMPERATURE_LOGS,
        self::PERMISSION_VIEW_ASSIGNED_BATCHES,
    ];

    public const ROLE_PERMISSION_MAP = [
        self::ROLE_AUDITOR => [
            self::PERMISSION_VIEW_INSPECTIONS,
            self::PERMISSION_VIEW_CERTIFICATES,
            self::PERMISSION_MONITOR_COMPLIANCE_METRICS,
            self::PERMISSION_MONITOR_TEMPERATURE_LOGS,
        ],
        self::ROLE_ORG_ADMIN => [
            self::PERMISSION_VIEW_ALL_MODULES,
            self::PERMISSION_MANAGE_BUSINESS_USERS,
            self::PERMISSION_ASSIGN_BUSINESS_ROLES,
            self::PERMISSION_VIEW_INSPECTIONS,
            self::PERMISSION_VIEW_CERTIFICATES,
            self::PERMISSION_MONITOR_COMPLIANCE_METRICS,
            self::PERMISSION_TRACK_DELIVERY_STATUS,
            self::PERMISSION_MONITOR_TEMPERATURE_LOGS,
        ],
        self::ROLE_OPERATIONS_MANAGER => [
            self::PERMISSION_CREATE_ANIMAL_INTAKE,
            self::PERMISSION_SCHEDULE_SLAUGHTER,
            self::PERMISSION_CREATE_BATCH,
            self::PERMISSION_ASSIGN_BATCH_TO_INSPECTOR,
            self::PERMISSION_VIEW_INSPECTIONS,
            self::PERMISSION_VIEW_CERTIFICATES,
        ],
        self::ROLE_COMPLIANCE_OFFICER => [
            self::PERMISSION_SUBMIT_CHECKLIST,
            self::PERMISSION_LOG_NON_COMPLIANCE,
            self::PERMISSION_UPLOAD_COMPLIANCE_EVIDENCE,
            self::PERMISSION_MONITOR_COMPLIANCE_METRICS,
            self::PERMISSION_MONITOR_TEMPERATURE_LOGS,
            self::PERMISSION_VIEW_CERTIFICATES,
        ],
        self::ROLE_INSPECTOR => [
            self::PERMISSION_RECORD_ANTE_MORTEM,
            self::PERMISSION_RECORD_POST_MORTEM,
            self::PERMISSION_ISSUE_CERTIFICATE,
            self::PERMISSION_VIEW_ASSIGNED_BATCHES,
            self::PERMISSION_VIEW_INSPECTIONS,
            self::PERMISSION_VIEW_CERTIFICATES,
        ],
        self::ROLE_LOGISTICS_MANAGER => [
            self::PERMISSION_CREATE_TRANSPORT_TRIP,
            self::PERMISSION_ASSIGN_VEHICLE_DRIVER,
            self::PERMISSION_DISPATCH_DELIVERY,
            self::PERMISSION_TRACK_DELIVERY_STATUS,
            self::PERMISSION_CONFIRM_DELIVERY,
            self::PERMISSION_MONITOR_TEMPERATURE_LOGS,
            self::PERMISSION_VIEW_CERTIFICATES,
        ],
        self::ROLE_DRIVER => [
            self::PERMISSION_TRACK_DELIVERY_STATUS,
            self::PERMISSION_CONFIRM_DELIVERY,
        ],
        self::ROLE_BUYER => [
            self::PERMISSION_VIEW_CERTIFICATES,
        ],
        self::ROLE_FARMER => [
            self::PERMISSION_VIEW_CERTIFICATES,
        ],
        self::ROLE_PROGRAMME_MANAGER => [
            self::PERMISSION_VIEW_ALL_MODULES,
            self::PERMISSION_MONITOR_COMPLIANCE_METRICS,
        ],
    ];

    public static function permissionsForRole(?string $role): array
    {
        return self::ROLE_PERMISSION_MAP[$role ?? ''] ?? [];
    }

    public static function roleHasPermission(?string $role, string $permission): bool
    {
        return in_array($permission, self::permissionsForRole($role), true);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
