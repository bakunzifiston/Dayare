<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use App\Support\TenantEnvironmentScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantEnvironmentScope::resetFilter();
        parent::tearDown();
    }

    public function test_guest_is_redirected_from_users_overview(): void
    {
        $this->get(route('super-admin.tenants.overview'))
            ->assertRedirect();
    }

    public function test_tenant_user_cannot_open_users_overview(): void
    {
        $tenant = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($tenant)
            ->get(route('super-admin.tenants.overview'))
            ->assertForbidden();
    }

    public function test_super_admin_without_users_module_is_forbidden(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'super_admin_permissions' => [User::SUPER_ADMIN_MODULE_DASHBOARD],
        ]);

        $this->actingAs($admin)
            ->get(route('super-admin.tenants.overview'))
            ->assertForbidden();
    }

    public function test_overview_reports_workspace_kpis_roles_and_activity(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'super_admin_permissions' => [User::SUPER_ADMIN_MODULE_USERS],
        ]);

        $owner = User::factory()->create([
            'name' => 'Live Owner',
            'email' => 'live-owner@example.com',
            'tenant_environment' => User::TENANT_ENVIRONMENT_LIVE,
            'email_verified_at' => now(),
        ]);
        $business = Business::factory()->for($owner, 'user')->create([
            'business_name' => 'Live Meats Ltd',
        ]);

        $inspector = User::factory()->unverified()->create([
            'name' => 'Quiet Inspector',
            'email' => 'quiet-inspector@example.com',
        ]);
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $inspector->id,
            'role' => BusinessUser::ROLE_INSPECTOR,
        ]);

        $activeStaff = User::factory()->create([
            'name' => 'Active Officer',
            'email' => 'active-officer@example.com',
        ]);
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $activeStaff->id,
            'role' => BusinessUser::ROLE_COMPLIANCE_OFFICER,
        ]);
        $this->recordSession($activeStaff->id, now()->timestamp);

        $hiddenOwner = User::factory()->create([
            'name' => 'Hidden Test Owner',
            'email' => 'hidden-test-owner@example.com',
            'tenant_environment' => User::TENANT_ENVIRONMENT_TEST,
        ]);
        Business::factory()->for($hiddenOwner, 'user')->create([
            'business_name' => 'Test Only Abattoir',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('super-admin.tenants.overview'));

        $response->assertOk()
            ->assertSeeText('Users')
            ->assertSeeText('Users accounts')
            ->assertSeeText('Live Owner')
            ->assertSeeText('Quiet Inspector')
            ->assertSeeText('Active Officer')
            ->assertSeeText('Inspector')
            ->assertSeeText('Compliance officer')
            ->assertDontSee('hidden-test-owner@example.com')
            ->assertViewHas('kpis', function (array $kpis) {
                return $kpis['total_users']['value'] === 3
                    && $kpis['tenant_owners']['value'] === 1
                    && $kpis['staff_accounts']['value'] === 2
                    && $kpis['unverified']['value'] === 1
                    && $kpis['active_7d']['value'] === 1
                    && $kpis['super_admins']['value'] === 1;
            })
            ->assertViewHas('roleRows', function (array $rows) {
                $roles = collect($rows)->pluck('users', 'role_key');

                return (int) $roles->get(BusinessUser::ROLE_INSPECTOR) === 1
                    && (int) $roles->get(BusinessUser::ROLE_COMPLIANCE_OFFICER) === 1;
            });
    }

    public function test_overview_all_scope_includes_test_tenants(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'super_admin_permissions' => [User::SUPER_ADMIN_MODULE_USERS],
        ]);

        $testOwner = User::factory()->create([
            'email' => 'visible-test-owner@example.com',
            'tenant_environment' => User::TENANT_ENVIRONMENT_TEST,
        ]);
        Business::factory()->for($testOwner, 'user')->create();

        $this->actingAs($admin)
            ->get(route('super-admin.tenants.overview', [
                'tenant_environment' => TenantEnvironmentScope::FILTER_ALL,
            ]))
            ->assertOk()
            ->assertSee('visible-test-owner@example.com');
    }

    public function test_overview_filters_users_by_workspace_type(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'super_admin_permissions' => [User::SUPER_ADMIN_MODULE_USERS],
        ]);

        $processorOwner = User::factory()->create([
            'name' => 'Processor Owner',
            'email' => 'processor-owner@example.com',
            'tenant_environment' => User::TENANT_ENVIRONMENT_LIVE,
        ]);
        $processorBusiness = Business::factory()->for($processorOwner, 'user')->create([
            'type' => Business::TYPE_PROCESSOR,
        ]);
        $processorStaff = User::factory()->create([
            'name' => 'Processor Staff',
            'email' => 'processor-staff@example.com',
        ]);
        BusinessUser::query()->create([
            'business_id' => $processorBusiness->id,
            'user_id' => $processorStaff->id,
            'role' => BusinessUser::ROLE_INSPECTOR,
        ]);

        $butcherOwner = User::factory()->create([
            'name' => 'Butcher Owner',
            'email' => 'butcher-owner@example.com',
            'tenant_environment' => User::TENANT_ENVIRONMENT_LIVE,
        ]);
        Business::factory()->for($butcherOwner, 'user')->butcher()->create();

        $logisticsOwner = User::factory()->create([
            'name' => 'Logistics Owner',
            'email' => 'logistics-owner@example.com',
            'tenant_environment' => User::TENANT_ENVIRONMENT_LIVE,
        ]);
        Business::factory()->for($logisticsOwner, 'user')->logistics()->create();

        $this->actingAs($admin)
            ->get(route('super-admin.tenants.overview', ['workspace' => Business::TYPE_PROCESSOR]))
            ->assertOk()
            ->assertSeeText('Processor')
            ->assertSeeText('Butcher')
            ->assertSeeText('Logistics')
            ->assertSeeText('Processor Owner')
            ->assertSeeText('Processor Staff')
            ->assertDontSee('butcher-owner@example.com')
            ->assertDontSee('logistics-owner@example.com')
            ->assertViewHas('workspaceType', Business::TYPE_PROCESSOR)
            ->assertViewHas('kpis', function (array $kpis) {
                return $kpis['total_users']['value'] === 2
                    && $kpis['tenant_owners']['value'] === 1
                    && $kpis['staff_accounts']['value'] === 1;
            });

        $this->actingAs($admin)
            ->get(route('super-admin.tenants.overview', ['workspace' => Business::TYPE_BUTCHER]))
            ->assertOk()
            ->assertSeeText('Butcher Owner')
            ->assertDontSee('processor-owner@example.com')
            ->assertDontSee('logistics-owner@example.com')
            ->assertViewHas('kpis', function (array $kpis) {
                return $kpis['total_users']['value'] === 1
                    && $kpis['tenant_owners']['value'] === 1;
            });

        $this->actingAs($admin)
            ->get(route('super-admin.tenants.overview'))
            ->assertOk()
            ->assertSeeText('Processor Owner')
            ->assertSeeText('Butcher Owner')
            ->assertSeeText('Logistics Owner')
            ->assertViewHas('workspaceType', null);
    }

    public function test_users_module_home_is_overview(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'super_admin_permissions' => [User::SUPER_ADMIN_MODULE_USERS],
        ]);

        $this->assertSame('super-admin.tenants.overview', $admin->defaultSuperAdminHomeRouteName());
    }

    public function test_directory_lists_tenants_and_users_in_tables(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'super_admin_permissions' => [User::SUPER_ADMIN_MODULE_USERS],
        ]);

        $owner = User::factory()->create([
            'name' => 'Card Tenant Owner',
            'email' => 'card-tenant@example.com',
            'tenant_environment' => User::TENANT_ENVIRONMENT_LIVE,
        ]);
        $business = Business::factory()->for($owner, 'user')->create([
            'business_name' => 'Card Meats Ltd',
        ]);
        $staff = User::factory()->create([
            'name' => 'Card Staff User',
            'email' => 'card-staff@example.com',
        ]);
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $staff->id,
            'role' => BusinessUser::ROLE_INSPECTOR,
        ]);

        $this->actingAs($admin)
            ->get(route('super-admin.tenants.index'))
            ->assertOk()
            ->assertSee('<table', false)
            ->assertSeeText('Tenant name')
            ->assertSeeText('Card Tenant Owner')
            ->assertSeeText('card-tenant@example.com')
            ->assertSeeText('Card Meats Ltd')
            ->assertSeeText('Card Staff User')
            ->assertSeeText('Profile')
            ->assertSee(route('super-admin.tenants.show', $owner).'#users', false)
            ->assertDontSee('profile-card', false);
    }

    public function test_directory_filters_tenants_and_users_by_workspace_type(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'super_admin_permissions' => [User::SUPER_ADMIN_MODULE_USERS],
        ]);

        $processorOwner = User::factory()->create([
            'name' => 'Directory Processor Owner',
            'email' => 'directory-processor@example.com',
            'tenant_environment' => User::TENANT_ENVIRONMENT_LIVE,
        ]);
        $processorBusiness = Business::factory()->for($processorOwner, 'user')->create([
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'Directory Processor Ltd',
        ]);
        $processorStaff = User::factory()->create([
            'name' => 'Directory Processor Staff',
            'email' => 'directory-processor-staff@example.com',
        ]);
        BusinessUser::query()->create([
            'business_id' => $processorBusiness->id,
            'user_id' => $processorStaff->id,
            'role' => BusinessUser::ROLE_INSPECTOR,
        ]);

        $butcherOwner = User::factory()->create([
            'name' => 'Directory Butcher Owner',
            'email' => 'directory-butcher@example.com',
            'tenant_environment' => User::TENANT_ENVIRONMENT_LIVE,
        ]);
        Business::factory()->for($butcherOwner, 'user')->butcher()->create([
            'business_name' => 'Directory Butcher Shop',
        ]);

        $this->actingAs($admin)
            ->get(route('super-admin.tenants.index', ['workspace' => Business::TYPE_PROCESSOR]))
            ->assertOk()
            ->assertSeeText('Processor')
            ->assertSeeText('Directory Processor Owner')
            ->assertSeeText('Directory Processor Staff')
            ->assertSeeText('Directory Processor Ltd')
            ->assertDontSee('directory-butcher@example.com')
            ->assertDontSee('Directory Butcher Shop')
            ->assertViewHas('workspaceType', Business::TYPE_PROCESSOR);

        $this->actingAs($admin)
            ->get(route('super-admin.tenants.index'))
            ->assertOk()
            ->assertSeeText('Directory Processor Owner')
            ->assertSeeText('Directory Butcher Owner')
            ->assertViewHas('workspaceType', null);
    }

    public function test_super_admin_can_open_tenant_profile(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'super_admin_permissions' => [User::SUPER_ADMIN_MODULE_USERS],
        ]);

        $owner = User::factory()->create([
            'name' => 'Profile Tenant Owner',
            'email' => 'profile-tenant@example.com',
            'tenant_environment' => User::TENANT_ENVIRONMENT_LIVE,
        ]);
        $business = Business::factory()->for($owner, 'user')->create([
            'business_name' => 'Profile Meats Ltd',
        ]);
        $staff = User::factory()->unverified()->create([
            'name' => 'Profile Staff User',
            'email' => 'profile-staff@example.com',
        ]);
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $staff->id,
            'role' => BusinessUser::ROLE_INSPECTOR,
        ]);
        $signedInAt = now()->subHours(3);
        $this->recordSession($owner->id, $signedInAt->timestamp);

        $expectedSignIn = $signedInAt
            ->timezone((string) config('app.display_timezone', config('app.timezone')))
            ->format('d M Y H:i');

        $this->actingAs($admin)
            ->get(route('super-admin.tenants.show', $owner))
            ->assertOk()
            ->assertSeeText('Account')
            ->assertSeeText('Profile Tenant Owner')
            ->assertSeeText('profile-tenant@example.com')
            ->assertSeeText('Profile Meats Ltd')
            ->assertSeeText('Profile Staff User')
            ->assertSeeText('Last sign in')
            ->assertSeeText($expectedSignIn)
            ->assertSeeText('Never')
            ->assertDontSee('profile-card', false)
            ->assertDontSee('Tenant profile');
    }

    public function test_super_admin_profile_is_not_a_tenant_profile(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'super_admin_permissions' => [User::SUPER_ADMIN_MODULE_USERS],
        ]);

        $this->actingAs($admin)
            ->get(route('super-admin.tenants.show', $admin))
            ->assertNotFound();
    }

    private function recordSession(int $userId, int $lastActivity): void
    {
        DB::table('sessions')->insert([
            'id' => Str::random(40),
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => $lastActivity,
        ]);
    }
}
