<?php

namespace App\Http\Requests\Butcher\Concerns;

use App\Models\Business;
use App\Services\Butcher\ButcherOnboardingService;
use Illuminate\Foundation\Http\FormRequest;

trait ResolvesButcherBusiness
{
    protected function butcherBusiness(): Business
    {
        /** @var ButcherOnboardingService $service */
        $service = app(ButcherOnboardingService::class);

        return $service->resolveButcherBusiness($this->user());
    }
}
