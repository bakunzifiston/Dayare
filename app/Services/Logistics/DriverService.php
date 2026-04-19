<?php

namespace App\Services\Logistics;

use App\Models\LogisticsDriver;
use App\Models\User;
use App\Repositories\Logistics\DriverRepository;
use Illuminate\Http\UploadedFile;

class DriverService
{
    public function __construct(
        private CompanyService $companies,
        private DriverRepository $drivers
    ) {}

    public function create(User $user, array $attributes): LogisticsDriver
    {
        $company = $this->companies->requireAccessible($user, (int) $attributes['company_id']);
        $attributes['company_id'] = (int) $company->id;
        $attributes['name'] = trim(($attributes['first_name'] ?? '').' '.($attributes['last_name'] ?? ''));

        if (isset($attributes['photo']) && $attributes['photo'] instanceof UploadedFile) {
            $attributes['photo_path'] = $attributes['photo']->store('logistics/drivers', 'public');
        }
        unset($attributes['photo']);

        return $this->drivers->create($attributes);
    }
}
