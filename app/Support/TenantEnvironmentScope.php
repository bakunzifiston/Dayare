<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Manual tenant environment filter for super-admin platform views.
 * Default: live tenants only. No auto-detection — only respects {@see User::$tenant_environment}.
 */
class TenantEnvironmentScope
{
    public const FILTER_ALL = 'all';

    private static ?string $filter = null;

    public static function resetFilter(): void
    {
        self::$filter = null;
    }

    public static function setFilter(?string $filter): void
    {
        self::$filter = self::normalize($filter);
    }

    public static function current(): string
    {
        if (self::$filter !== null) {
            return self::$filter;
        }

        return self::normalize(request()?->query('tenant_environment'));
    }

    public static function resolveFromRequest(?Request $request = null): string
    {
        $request = $request ?? request();

        return self::normalize($request?->query('tenant_environment'));
    }

    public static function isFiltering(): bool
    {
        return self::current() !== self::FILTER_ALL;
    }

    public static function label(?string $filter = null): string
    {
        return match (self::normalize($filter)) {
            User::TENANT_ENVIRONMENT_TEST => __('Test tenants only'),
            self::FILTER_ALL => __('All tenants'),
            default => __('Live tenants only'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function filterOptions(): array
    {
        return [
            User::TENANT_ENVIRONMENT_LIVE => __('Live only'),
            User::TENANT_ENVIRONMENT_TEST => __('Test only'),
            self::FILTER_ALL => __('All (live + test)'),
        ];
    }

    private static function resolveFilter(?string $filter): string
    {
        return $filter !== null ? self::normalize($filter) : self::current();
    }

    /**
     * @param  Builder<\App\Models\User>  $query
     * @return Builder<\App\Models\User>
     */
    public static function applyToTenantOwners(Builder $query, ?string $filter = null): Builder
    {
        $filter = self::resolveFilter($filter);

        if ($filter === self::FILTER_ALL) {
            return $query;
        }

        return self::applyTenantEnvironmentConstraint($query, $filter);
    }

    /**
     * @param  Builder<\App\Models\Business>  $query
     * @return Builder<\App\Models\Business>
     */
    public static function applyToBusinesses(Builder $query, ?string $filter = null): Builder
    {
        $filter = self::resolveFilter($filter);

        if ($filter === self::FILTER_ALL) {
            return $query;
        }

        return $query->whereHas(
            'user',
            fn (Builder $userQuery) => self::applyTenantEnvironmentConstraint($userQuery, $filter)
        );
    }

    /**
     * @param  Builder<\App\Models\Facility>  $query
     * @return Builder<\App\Models\Facility>
     */
    public static function applyToFacilities(Builder $query, ?string $filter = null): Builder
    {
        $filter = self::resolveFilter($filter);

        if ($filter === self::FILTER_ALL) {
            return $query;
        }

        return $query->whereHas(
            'business.user',
            fn (Builder $userQuery) => self::applyTenantEnvironmentConstraint($userQuery, $filter)
        );
    }

    /**
     * @param  Builder<\App\Models\Batch>  $query
     * @return Builder<\App\Models\Batch>
     */
    public static function applyToBatches(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain(
            $query,
            'slaughterExecution.slaughterPlan.facility.business.user',
            $filter
        );
    }

    /**
     * @param  Builder<\App\Models\SlaughterExecution>  $query
     * @return Builder<\App\Models\SlaughterExecution>
     */
    public static function applyToSlaughterExecutions(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain(
            $query,
            'slaughterPlan.facility.business.user',
            $filter
        );
    }

    /**
     * @param  Builder<\App\Models\Certificate>  $query
     * @return Builder<\App\Models\Certificate>
     */
    public static function applyToCertificates(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain($query, 'facility.business.user', $filter);
    }

    /**
     * @param  Builder<\App\Models\Contract>  $query
     * @return Builder<\App\Models\Contract>
     */
    public static function applyToContracts(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain($query, 'business.user', $filter);
    }

    /**
     * @param  Builder<\App\Models\Inspector>  $query
     * @return Builder<\App\Models\Inspector>
     */
    public static function applyToInspectors(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain($query, 'facility.business.user', $filter);
    }

    /**
     * @param  Builder<\App\Models\WarehouseStorage>  $query
     * @return Builder<\App\Models\WarehouseStorage>
     */
    public static function applyToWarehouseStorages(Builder $query, ?string $filter = null): Builder
    {
        $filter = self::resolveFilter($filter);

        if ($filter === self::FILTER_ALL) {
            return $query;
        }

        return $query->where(function (Builder $scoped) use ($filter): void {
            $scoped->whereHas(
                'batch.slaughterExecution.slaughterPlan.facility.business.user',
                fn (Builder $userQuery) => self::applyTenantEnvironmentConstraint($userQuery, $filter)
            )->orWhereHas(
                'warehouseFacility.business.user',
                fn (Builder $userQuery) => self::applyTenantEnvironmentConstraint($userQuery, $filter)
            );
        });
    }

    /**
     * @param  Builder<\App\Models\TemperatureLog>  $query
     * @return Builder<\App\Models\TemperatureLog>
     */
    public static function applyToTemperatureLogs(Builder $query, ?string $filter = null): Builder
    {
        $filter = self::resolveFilter($filter);

        if ($filter === self::FILTER_ALL) {
            return $query;
        }

        return $query->whereHas(
            'warehouseStorage',
            fn (Builder $storageQuery) => self::applyToWarehouseStorages($storageQuery, $filter)
        );
    }

    /**
     * @param  Builder<\App\Models\AnimalIntake>  $query
     * @return Builder<\App\Models\AnimalIntake>
     */
    public static function applyToAnimalIntakes(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain($query, 'facility.business.user', $filter);
    }

    /**
     * @param  Builder<\App\Models\Demand>  $query
     * @return Builder<\App\Models\Demand>
     */
    public static function applyToDemands(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain($query, 'business.user', $filter);
    }

    /**
     * @param  Builder<\App\Models\DeliveryConfirmation>  $query
     * @return Builder<\App\Models\DeliveryConfirmation>
     */
    public static function applyToDeliveryConfirmations(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain($query, 'receivingFacility.business.user', $filter);
    }

    /**
     * Restrict ante-mortem / slaughter-plan queries via facility ownership.
     *
     * @param  Builder<\App\Models\SlaughterPlan>  $query
     * @return Builder<\App\Models\SlaughterPlan>
     */
    public static function applyToSlaughterPlans(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain($query, 'facility.business.user', $filter);
    }

    /**
     * @param  Builder<\App\Models\PostMortemInspectionItem>  $query
     * @return Builder<\App\Models\PostMortemInspectionItem>
     */
    public static function applyToPostMortemInspectionItems(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain(
            $query,
            'inspection.batch.slaughterExecution.slaughterPlan.facility.business.user',
            $filter
        );
    }

    /**
     * @param  Builder<\App\Models\SlaughterExecutionItem>  $query
     * @return Builder<\App\Models\SlaughterExecutionItem>
     */
    public static function applyToSlaughterExecutionItems(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain(
            $query,
            'execution.slaughterPlan.facility.business.user',
            $filter
        );
    }

    /**
     * @param  Builder<\App\Models\Supplier>  $query
     * @return Builder<\App\Models\Supplier>
     */
    public static function applyToSuppliers(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain($query, 'business.user', $filter);
    }

    /**
     * @param  Builder<\App\Models\Client>  $query
     * @return Builder<\App\Models\Client>
     */
    public static function applyToClients(Builder $query, ?string $filter = null): Builder
    {
        return self::applyThroughBusinessChain($query, 'business.user', $filter);
    }

    public static function queryParams(?string $filter = null): array
    {
        $filter = self::resolveFilter($filter);

        return $filter === User::TENANT_ENVIRONMENT_LIVE
            ? []
            : ['tenant_environment' => $filter];
    }

    private static function normalize(?string $filter): string
    {
        if (in_array($filter, [User::TENANT_ENVIRONMENT_LIVE, User::TENANT_ENVIRONMENT_TEST, self::FILTER_ALL], true)) {
            return $filter;
        }

        return User::TENANT_ENVIRONMENT_LIVE;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private static function applyThroughBusinessChain(Builder $query, string $relation, ?string $filter = null): Builder
    {
        $filter = self::resolveFilter($filter);

        if ($filter === self::FILTER_ALL) {
            return $query;
        }

        return $query->whereHas(
            $relation,
            fn (Builder $userQuery) => self::applyTenantEnvironmentConstraint($userQuery, $filter)
        );
    }

    /**
     * @param  Builder<\App\Models\User>  $query
     * @return Builder<\App\Models\User>
     */
    private static function applyTenantEnvironmentConstraint(Builder $query, string $filter): Builder
    {
        if ($filter === User::TENANT_ENVIRONMENT_LIVE) {
            return $query->where(function (Builder $scoped): void {
                $scoped->where('tenant_environment', User::TENANT_ENVIRONMENT_LIVE)
                    ->orWhereNull('tenant_environment');
            });
        }

        return $query->where('tenant_environment', $filter);
    }
}
