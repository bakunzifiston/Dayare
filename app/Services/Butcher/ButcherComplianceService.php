<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherHygieneLog;
use App\Models\ButcherPermit;
use App\Models\ButcherSanitationRecord;
use App\Models\ButcherStaffHealthRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ButcherComplianceService
{
    public function logHygiene(Business $business, array $data, User $user): ButcherHygieneLog
    {
        $logDate = isset($data['log_date'])
            ? Carbon::parse($data['log_date'])->toDateString()
            : now()->toDateString();

        $outletId = (int) $data['outlet_id'];

        if (ButcherHygieneLog::query()->where('outlet_id', $outletId)->whereDate('log_date', $logDate)->exists()) {
            throw ValidationException::withMessages([
                'log_date' => [__('A hygiene log already exists for this outlet on this date.')],
            ]);
        }

        $checklist = $this->normalizeChecklist($data['checklist'] ?? []);
        $status = ButcherHygieneLog::resolveStatus($checklist);

        return ButcherHygieneLog::query()->create([
            'business_id' => $business->id,
            'outlet_id' => $outletId,
            'log_date' => $logDate,
            'checklist' => $checklist,
            'issues_found' => $data['issues_found'] ?? null,
            'corrective_action' => $data['corrective_action'] ?? null,
            'signed_by' => $user->id,
            'status' => $status,
        ]);
    }

    public function logSanitation(Business $business, array $data, User $user): ButcherSanitationRecord
    {
        $performedAt = isset($data['performed_at'])
            ? Carbon::parse($data['performed_at'])
            : now();

        return ButcherSanitationRecord::query()->create([
            'business_id' => $business->id,
            'outlet_id' => (int) $data['outlet_id'],
            'equipment_name' => (string) $data['equipment_name'],
            'cleaning_type' => (string) $data['cleaning_type'],
            'chemical_used' => $data['chemical_used'] ?? null,
            'performed_at' => $performedAt,
            'performed_by' => $user->id,
            'next_due_at' => isset($data['next_due_at']) && $data['next_due_at'] !== ''
                ? Carbon::parse($data['next_due_at'])
                : null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function upsertStaffHealth(Business $business, array $data): ButcherStaffHealthRecord
    {
        $issuedDate = Carbon::parse($data['issued_date'])->toDateString();
        $expiryDate = Carbon::parse($data['expiry_date'])->toDateString();

        return ButcherStaffHealthRecord::query()->updateOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => (int) $data['user_id'],
            ],
            [
                'medical_card_number' => (string) $data['medical_card_number'],
                'issued_date' => $issuedDate,
                'expiry_date' => $expiryDate,
                'health_status' => (string) ($data['health_status'] ?? ButcherStaffHealthRecord::STATUS_FIT),
                'last_checked_at' => isset($data['last_checked_at'])
                    ? Carbon::parse($data['last_checked_at'])->toDateString()
                    : now()->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getComplianceAlerts(Business $business): array
    {
        $today = now()->toDateString();

        $outlets = $business->butcherOutlets()->where('status', 'active')->get();
        $loggedOutletIds = ButcherHygieneLog::query()
            ->where('business_id', $business->id)
            ->whereDate('log_date', $today)
            ->pluck('outlet_id');

        $missingHygieneOutlets = $outlets->filter(fn ($outlet) => ! $loggedOutletIds->contains($outlet->id));

        $expiringHealthCards = $business->butcherStaffHealthRecords()
            ->with('user')
            ->get()
            ->filter(fn (ButcherStaffHealthRecord $record) => $record->isExpiringSoon(30) || $record->isExpired());

        $expiringPermits = $business->butcherPermits()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(60)->toDateString())
            ->orderBy('expiry_date')
            ->get();

        $overdueSanitation = $business->butcherSanitationRecords()
            ->with(['outlet', 'performedByUser'])
            ->whereNotNull('next_due_at')
            ->where('next_due_at', '<', now())
            ->latest('next_due_at')
            ->limit(10)
            ->get();

        $recentFailedHygiene = $business->butcherHygieneLogs()
            ->with(['outlet', 'signedByUser'])
            ->whereIn('status', [ButcherHygieneLog::STATUS_FAIL, ButcherHygieneLog::STATUS_PARTIAL])
            ->latest('log_date')
            ->limit(5)
            ->get();

        return [
            'missing_hygiene_today' => $missingHygieneOutlets->values(),
            'missing_hygiene_count' => $missingHygieneOutlets->count(),
            'expiring_health_cards' => $expiringHealthCards->values(),
            'expiring_health_count' => $expiringHealthCards->count(),
            'expiring_permits' => $expiringPermits,
            'expiring_permit_count' => $expiringPermits->count(),
            'overdue_sanitation' => $overdueSanitation,
            'overdue_sanitation_count' => $overdueSanitation->count(),
            'recent_failed_hygiene' => $recentFailedHygiene,
            'alert_total' => $missingHygieneOutlets->count()
                + $expiringHealthCards->count()
                + $expiringPermits->count()
                + $overdueSanitation->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAuditReport(Business $business, Carbon $from, Carbon $to): array
    {
        $hygieneLogs = $business->butcherHygieneLogs()
            ->with(['outlet', 'signedByUser'])
            ->whereBetween('log_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('log_date')
            ->get();

        $sanitationRecords = $business->butcherSanitationRecords()
            ->with(['outlet', 'performedByUser'])
            ->whereBetween('performed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('performed_at')
            ->get();

        $healthRecords = $business->butcherStaffHealthRecords()
            ->with('user')
            ->orderBy('expiry_date')
            ->get();

        $permits = $business->butcherPermits()
            ->orderBy('expiry_date')
            ->get();

        $hygienePassRate = $hygieneLogs->isNotEmpty()
            ? round(($hygieneLogs->where('status', ButcherHygieneLog::STATUS_PASS)->count() / $hygieneLogs->count()) * 100, 1)
            : 0.0;

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'business_name' => $business->business_name,
            'hygiene_logs' => $hygieneLogs,
            'hygiene_total' => $hygieneLogs->count(),
            'hygiene_pass_count' => $hygieneLogs->where('status', ButcherHygieneLog::STATUS_PASS)->count(),
            'hygiene_fail_count' => $hygieneLogs->where('status', ButcherHygieneLog::STATUS_FAIL)->count(),
            'hygiene_partial_count' => $hygieneLogs->where('status', ButcherHygieneLog::STATUS_PARTIAL)->count(),
            'hygiene_pass_rate' => $hygienePassRate,
            'sanitation_records' => $sanitationRecords,
            'sanitation_total' => $sanitationRecords->count(),
            'health_records' => $healthRecords,
            'health_expiring_30d' => $healthRecords->filter(fn ($r) => $r->isExpiringSoon(30))->count(),
            'permits' => $permits,
            'permits_expiring_60d' => $permits->filter(
                fn (ButcherPermit $p) => $p->expiry_date && $p->expiry_date->lte(now()->addDays(60))
            )->count(),
        ];
    }

    public function exportAuditReport(Business $business, Carbon $from, Carbon $to): string
    {
        $report = $this->getAuditReport($business, $from, $to);
        $filename = sprintf(
            'butcher-compliance/%d/audit-%s-%s.csv',
            $business->id,
            $from->format('Ymd'),
            $to->format('Ymd')
        );

        $lines = [];
        $lines[] = 'BuchaPro Butcher Compliance Audit Report';
        $lines[] = 'Business,'.$this->csvEscape((string) $report['business_name']);
        $lines[] = 'Period,'.$report['from'].' to '.$report['to'];
        $lines[] = 'Generated,'.now()->toDateTimeString();
        $lines[] = '';
        $lines[] = 'Summary';
        $lines[] = 'Hygiene logs,'.$report['hygiene_total'];
        $lines[] = 'Hygiene pass rate %,'.$report['hygiene_pass_rate'];
        $lines[] = 'Sanitation records,'.$report['sanitation_total'];
        $lines[] = 'Staff health cards expiring (30d),'.$report['health_expiring_30d'];
        $lines[] = 'Permits expiring (60d),'.$report['permits_expiring_60d'];
        $lines[] = '';
        $lines[] = 'Hygiene Logs';
        $lines[] = 'Date,Outlet,Status,Signed By,Issues,Corrective Action';
        foreach ($report['hygiene_logs'] as $log) {
            $lines[] = implode(',', [
                $log->log_date?->toDateString(),
                $this->csvEscape($log->outlet?->name ?? ''),
                $log->status,
                $this->csvEscape($log->signedByUser?->name ?? ''),
                $this->csvEscape((string) ($log->issues_found ?? '')),
                $this->csvEscape((string) ($log->corrective_action ?? '')),
            ]);
        }
        $lines[] = '';
        $lines[] = 'Sanitation Records';
        $lines[] = 'Performed At,Outlet,Equipment,Type,Chemical,Next Due,Performed By';
        foreach ($report['sanitation_records'] as $record) {
            $lines[] = implode(',', [
                $record->performed_at?->toDateTimeString(),
                $this->csvEscape($record->outlet?->name ?? ''),
                $this->csvEscape($record->equipment_name),
                $record->cleaning_type,
                $this->csvEscape((string) ($record->chemical_used ?? '')),
                $record->next_due_at?->toDateTimeString() ?? '',
                $this->csvEscape($record->performedByUser?->name ?? ''),
            ]);
        }
        $lines[] = '';
        $lines[] = 'Staff Health Cards';
        $lines[] = 'Staff,Card Number,Issued,Expiry,Status,Last Checked';
        foreach ($report['health_records'] as $health) {
            $lines[] = implode(',', [
                $this->csvEscape($health->user?->name ?? ''),
                $this->csvEscape($health->medical_card_number),
                $health->issued_date?->toDateString(),
                $health->expiry_date?->toDateString(),
                $health->health_status,
                $health->last_checked_at?->toDateString() ?? '',
            ]);
        }
        $lines[] = '';
        $lines[] = 'Permits & Certifications';
        $lines[] = 'Type,Number,Issued By,Issue Date,Expiry,Status';
        foreach ($report['permits'] as $permit) {
            $lines[] = implode(',', [
                $permit->permit_type,
                $this->csvEscape((string) $permit->permit_number),
                $this->csvEscape((string) ($permit->issued_by ?? '')),
                $permit->issue_date?->toDateString() ?? '',
                $permit->expiry_date?->toDateString() ?? '',
                $permit->status,
            ]);
        }

        Storage::disk('public')->put($filename, implode("\n", $lines));

        return $filename;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, bool>
     */
    private function normalizeChecklist(array $input): array
    {
        $checklist = ButcherHygieneLog::DEFAULT_CHECKLIST;

        foreach (array_keys($checklist) as $key) {
            $checklist[$key] = filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return $checklist;
    }

    private function csvEscape(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
