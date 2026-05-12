<?php

namespace App\Services\Farmer;

use App\Models\AnimalCertificate;
use App\Models\AnimalCertificateLog;
use App\Models\AnimalOwnershipTransfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnimalCertificateAnalyticsService
{
    /**
     * @param  Collection<int, int>  $animalIds
     * @return array<string, mixed>
     */
    public function metrics(Collection $animalIds): array
    {
        $total = AnimalCertificate::query()->whereIn('animal_id', $animalIds)->count();
        $active = AnimalCertificate::query()->whereIn('animal_id', $animalIds)->where('certificate_status', AnimalCertificate::STATUS_ACTIVE)->count();
        $expired = AnimalCertificate::query()->whereIn('animal_id', $animalIds)->where('certificate_status', AnimalCertificate::STATUS_EXPIRED)->count();
        $certificateIds = AnimalCertificate::query()->whereIn('animal_id', $animalIds)->pluck('id');
        $verifications = AnimalCertificateLog::query()
            ->whereIn('animal_certificate_id', $certificateIds)
            ->where('action_type', AnimalCertificateLog::ACTION_VERIFIED)
            ->count();
        $recentVerifications = AnimalCertificateLog::query()
            ->whereIn('animal_certificate_id', $certificateIds)
            ->where('action_type', AnimalCertificateLog::ACTION_VERIFIED)
            ->with('certificate.animal')
            ->latest('action_date')
            ->limit(8)
            ->get();
        $transfers = AnimalOwnershipTransfer::query()->whereIn('animal_id', $animalIds)->count();

        return compact('total', 'active', 'expired', 'verifications', 'recentVerifications', 'transfers');
    }

    /**
     * @param  Collection<int, int>  $animalIds
     * @return array<string, array<string, mixed>>
     */
    public function charts(Collection $animalIds): array
    {
        $months = collect(range(5, 0))->map(fn (int $offset) => Carbon::today()->startOfMonth()->subMonths($offset));
        $labels = $months->map(fn (Carbon $month) => $month->format('M Y'))->all();

        $certificateTrend = $months->map(function (Carbon $month) use ($animalIds): int {
            return AnimalCertificate::query()
                ->whereIn('animal_id', $animalIds)
                ->whereBetween('issue_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->count();
        })->all();

        $verificationTrend = $months->map(function (Carbon $month) use ($animalIds): int {
            return AnimalCertificateLog::query()
                ->where('action_type', AnimalCertificateLog::ACTION_VERIFIED)
                ->whereHas('certificate', fn ($query) => $query->whereIn('animal_id', $animalIds))
                ->whereBetween('action_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->count();
        })->all();

        $transferTrend = $months->map(function (Carbon $month) use ($animalIds): int {
            return AnimalOwnershipTransfer::query()
                ->whereIn('animal_id', $animalIds)
                ->whereBetween('transfer_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->count();
        })->all();

        $expiring = AnimalCertificate::query()
            ->whereIn('animal_id', $animalIds)
            ->where('certificate_status', AnimalCertificate::STATUS_ACTIVE)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', Carbon::today()->addDays(30))
            ->count();

        return [
            'certificate_trend' => ['labels' => $labels, 'datasets' => [['label' => __('Certificates'), 'data' => $certificateTrend, 'backgroundColor' => 'rgba(37, 99, 235, 0.55)']]],
            'verification_trend' => ['labels' => $labels, 'datasets' => [['label' => __('Verifications'), 'data' => $verificationTrend, 'backgroundColor' => 'rgba(16, 185, 129, 0.55)']]],
            'transfer_trend' => ['labels' => $labels, 'datasets' => [['label' => __('Transfers'), 'data' => $transferTrend, 'backgroundColor' => 'rgba(99, 102, 241, 0.55)']]],
            'expiry_tracking' => ['labels' => [__('Expiring in 30 days')], 'datasets' => [['label' => __('Certificates'), 'data' => [$expiring], 'backgroundColor' => 'rgba(245, 158, 11, 0.65)']]],
        ];
    }
}
