<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Business;
use App\Models\User;
use App\Support\TenantEnvironmentScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_mark_tenant_as_test(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'super_admin_permissions' => [User::SUPER_ADMIN_MODULE_USERS],
        ]);

        $tenant = User::factory()->create(['tenant_environment' => User::TENANT_ENVIRONMENT_LIVE]);
        Business::factory()->create(['user_id' => $tenant->id]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.tenants.environment', $tenant), [
                'tenant_environment' => User::TENANT_ENVIRONMENT_TEST,
            ])
            ->assertRedirect();

        $this->assertSame(User::TENANT_ENVIRONMENT_TEST, $tenant->fresh()->tenant_environment);
    }

    public function test_tenant_environment_scope_excludes_test_businesses_by_default(): void
    {
        $liveOwner = User::factory()->create(['tenant_environment' => User::TENANT_ENVIRONMENT_LIVE]);
        $testOwner = User::factory()->create(['tenant_environment' => User::TENANT_ENVIRONMENT_TEST]);

        Business::factory()->for($liveOwner, 'user')->create();
        Business::factory()->for($testOwner, 'user')->create();

        $this->assertSame(2, Business::query()->count());

        TenantEnvironmentScope::resetFilter();
        TenantEnvironmentScope::setFilter(User::TENANT_ENVIRONMENT_LIVE);
        $this->assertSame(1, TenantEnvironmentScope::applyToBusinesses(Business::query())->count());

        TenantEnvironmentScope::setFilter(User::TENANT_ENVIRONMENT_TEST);
        $this->assertSame(1, TenantEnvironmentScope::applyToBusinesses(Business::query())->count());

        TenantEnvironmentScope::setFilter(TenantEnvironmentScope::FILTER_ALL);
        $this->assertSame(2, TenantEnvironmentScope::applyToBusinesses(Business::query())->count());
    }

    protected function tearDown(): void
    {
        TenantEnvironmentScope::resetFilter();
        parent::tearDown();
    }
}
