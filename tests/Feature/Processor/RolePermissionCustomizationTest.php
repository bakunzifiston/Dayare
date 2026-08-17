<?php

namespace Tests\Feature\Processor;

use App\Http\Controllers\TenantUserController;
use App\Models\Business;
use App\Models\BusinessRolePermissionOverride;
use App\Models\BusinessUser;
use App\Models\BusinessUserPermissionOverride;
use App\Models\User;
use App\Support\ProcessorRolePermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionCustomizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administration_catalog_contains_every_processor_permission_once(): void
    {
        $catalogPermissions = collect(ProcessorRolePermissionCatalog::groups())
            ->flatMap(fn (array $group) => array_keys($group['permissions']))
            ->values();

        $this->assertCount(count(BusinessUser::ACTION_PERMISSIONS), $catalogPermissions);
        $this->assertEqualsCanonicalizing(
            BusinessUser::ACTION_PERMISSIONS,
            $catalogPermissions->all()
        );
    }

    public function test_business_owner_can_customize_a_role_and_changes_apply_immediately(): void
    {
        [$owner, $business, $member] = $this->processorTeam();

        $this->actingAs($owner)
            ->withSession(['active_processor_business_id' => $business->id])
            ->get(route('tenant-users.role-permissions.index', [
                'business_id' => $business->id,
                'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
            ]))
            ->assertOk()
            ->assertSee('Role access')
            ->assertSee('Operations')
            ->assertSee('Manage animal intake');

        $this->assertTrue($member->canProcessorPermission(
            BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
            $business->id
        ));
        $this->assertFalse($member->canProcessorPermission(
            BusinessUser::PERMISSION_VIEW_FINANCE_DASHBOARD,
            $business->id
        ));

        $response = $this->actingAs($owner)
            ->withSession(['active_processor_business_id' => $business->id])
            ->put(route('tenant-users.role-permissions.update'), [
                'business_id' => $business->id,
                'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
                'permissions' => [
                    BusinessUser::PERMISSION_VIEW_FINANCE_DASHBOARD,
                ],
            ]);

        $response->assertRedirect(route('tenant-users.role-permissions.index', [
            'business_id' => $business->id,
            'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
        ]));

        $this->assertFalse($member->canProcessorPermission(
            BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
            $business->id
        ));
        $this->assertTrue($member->canProcessorPermission(
            BusinessUser::PERMISSION_VIEW_FINANCE_DASHBOARD,
            $business->id
        ));
        $this->assertFalse($member->canProcessorPermission(
            BusinessUser::PERMISSION_VIEW_PROCESSOR_DASHBOARD,
            $business->id
        ));
        $this->assertSame('finance.dashboard', $member->defaultDashboardRouteName());
        $this->actingAs($member)
            ->withSession(['active_processor_business_id' => $business->id])
            ->get(route('dashboard'))
            ->assertForbidden();
        $this->actingAs($member)
            ->withSession(['active_processor_business_id' => $business->id])
            ->get(route('animal-intakes.index'))
            ->assertForbidden();

        $this->assertDatabaseHas('business_role_permission_overrides', [
            'business_id' => $business->id,
            'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
            'permission' => BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
            'is_allowed' => false,
        ]);
        $this->assertDatabaseHas('business_role_permission_overrides', [
            'business_id' => $business->id,
            'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
            'permission' => BusinessUser::PERMISSION_VIEW_FINANCE_DASHBOARD,
            'is_allowed' => true,
        ]);
    }

    public function test_owner_can_restore_role_defaults(): void
    {
        [$owner, $business, $member] = $this->processorTeam();

        BusinessRolePermissionOverride::query()->create([
            'business_id' => $business->id,
            'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
            'permission' => BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
            'is_allowed' => false,
        ]);

        $this->actingAs($owner)
            ->withSession(['active_processor_business_id' => $business->id])
            ->delete(route('tenant-users.role-permissions.destroy'), [
                'business_id' => $business->id,
                'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('business_role_permission_overrides', [
            'business_id' => $business->id,
            'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
        ]);
        $this->assertTrue($member->canProcessorPermission(
            BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
            $business->id
        ));
    }

    public function test_non_owner_cannot_customize_role_access(): void
    {
        [, $business, $member] = $this->processorTeam();
        BusinessUser::query()
            ->where('business_id', $business->id)
            ->where('user_id', $member->id)
            ->update(['role' => BusinessUser::ROLE_ORG_ADMIN]);

        $this->actingAs($member)
            ->withSession(['active_processor_business_id' => $business->id])
            ->get(route('tenant-users.role-permissions.index'))
            ->assertForbidden();

        $this->actingAs($member)
            ->withSession(['active_processor_business_id' => $business->id])
            ->put(route('tenant-users.role-permissions.update'), [
                'business_id' => $business->id,
                'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
                'permissions' => BusinessUser::ACTION_PERMISSIONS,
            ])
            ->assertForbidden();
    }

    public function test_business_overrides_do_not_leak_to_another_business(): void
    {
        [, $business, $member] = $this->processorTeam();
        $secondOwner = User::factory()->create();
        $secondBusiness = Business::factory()->create([
            'user_id' => $secondOwner->id,
            'type' => Business::TYPE_PROCESSOR,
        ]);
        BusinessUser::query()->create([
            'business_id' => $secondBusiness->id,
            'user_id' => $member->id,
            'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
        ]);

        BusinessRolePermissionOverride::query()->create([
            'business_id' => $business->id,
            'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
            'permission' => BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
            'is_allowed' => false,
        ]);

        $this->assertFalse($member->canProcessorPermission(
            BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
            $business->id
        ));
        $this->assertTrue($member->canProcessorPermission(
            BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
            $secondBusiness->id
        ));
    }

    public function test_role_guidance_reflects_effective_permissions_for_a_business(): void
    {
        [, $business] = $this->processorTeam();

        BusinessRolePermissionOverride::query()->create([
            'business_id' => $business->id,
            'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
            'permission' => BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
            'is_allowed' => false,
        ]);
        BusinessRolePermissionOverride::query()->create([
            'business_id' => $business->id,
            'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
            'permission' => BusinessUser::PERMISSION_VIEW_FINANCE_DASHBOARD,
            'is_allowed' => true,
        ]);

        $guidance = TenantUserController::roleGuidance($business->id);
        $operationsSummary = collect($guidance[BusinessUser::ROLE_OPERATIONS_MANAGER]['permissions']);

        $this->assertFalse($operationsSummary->contains(__('Manage animal intake')));
        $this->assertTrue($operationsSummary->contains(__('View finance dashboard')));
    }

    public function test_user_with_no_enabled_modules_gets_a_safe_landing_page(): void
    {
        [$owner, $business, $member] = $this->processorTeam();

        $this->actingAs($owner)
            ->withSession(['active_processor_business_id' => $business->id])
            ->put(route('tenant-users.role-permissions.update'), [
                'business_id' => $business->id,
                'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
                'permissions' => [],
            ])
            ->assertRedirect();

        $this->actingAs($member)
            ->withSession(['active_processor_business_id' => $business->id]);

        $this->assertSame('processor.no-access', $member->defaultDashboardRouteName());
        $this->get(route('processor.no-access'))
            ->assertOk()
            ->assertSee('No workspace access assigned');
    }

    public function test_two_inspectors_can_have_different_access_in_the_same_business(): void
    {
        [$owner, $business, $firstInspector] = $this->processorTeam(
            BusinessUser::ROLE_INSPECTOR
        );
        $secondInspector = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $secondInspector->id,
            'role' => BusinessUser::ROLE_INSPECTOR,
        ]);

        $this->assertTrue($firstInspector->canProcessorPermission(
            BusinessUser::PERMISSION_ISSUE_CERTIFICATE,
            $business->id
        ));
        $this->assertTrue($secondInspector->canProcessorPermission(
            BusinessUser::PERMISSION_ISSUE_CERTIFICATE,
            $business->id
        ));

        $this->actingAs($owner)
            ->withSession(['active_processor_business_id' => $business->id])
            ->put(route('tenant-users.user-permissions.update', $firstInspector), [
                'business_id' => $business->id,
                'permissions' => [
                    BusinessUser::PERMISSION_VIEW_INSPECTIONS,
                    BusinessUser::PERMISSION_VIEW_ASSIGNED_BATCHES,
                ],
            ])
            ->assertRedirect();

        $this->assertFalse($firstInspector->canProcessorPermission(
            BusinessUser::PERMISSION_ISSUE_CERTIFICATE,
            $business->id
        ));
        $this->assertTrue($secondInspector->canProcessorPermission(
            BusinessUser::PERMISSION_ISSUE_CERTIFICATE,
            $business->id
        ));
        $this->assertDatabaseHas('business_user_permission_overrides', [
            'business_id' => $business->id,
            'user_id' => $firstInspector->id,
            'permission' => BusinessUser::PERMISSION_ISSUE_CERTIFICATE,
            'is_allowed' => false,
        ]);
        $this->assertDatabaseMissing('business_user_permission_overrides', [
            'business_id' => $business->id,
            'user_id' => $secondInspector->id,
        ]);
    }

    public function test_owner_can_restore_an_individual_user_to_role_access(): void
    {
        [$owner, $business, $inspector] = $this->processorTeam(
            BusinessUser::ROLE_INSPECTOR
        );
        BusinessUserPermissionOverride::query()->create([
            'business_id' => $business->id,
            'user_id' => $inspector->id,
            'permission' => BusinessUser::PERMISSION_ISSUE_CERTIFICATE,
            'is_allowed' => false,
        ]);

        $this->assertFalse($inspector->canProcessorPermission(
            BusinessUser::PERMISSION_ISSUE_CERTIFICATE,
            $business->id
        ));

        $this->actingAs($owner)
            ->withSession(['active_processor_business_id' => $business->id])
            ->delete(route('tenant-users.user-permissions.destroy', $inspector), [
                'business_id' => $business->id,
            ])
            ->assertRedirect();

        $this->assertTrue($inspector->canProcessorPermission(
            BusinessUser::PERMISSION_ISSUE_CERTIFICATE,
            $business->id
        ));
    }

    public function test_non_owner_cannot_customize_individual_access(): void
    {
        [, $business, $inspector] = $this->processorTeam(
            BusinessUser::ROLE_INSPECTOR
        );
        $otherMember = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $otherMember->id,
            'role' => BusinessUser::ROLE_ORG_ADMIN,
        ]);

        $this->actingAs($otherMember)
            ->withSession(['active_processor_business_id' => $business->id])
            ->put(route('tenant-users.user-permissions.update', $inspector), [
                'business_id' => $business->id,
                'permissions' => BusinessUser::ACTION_PERMISSIONS,
            ])
            ->assertForbidden();
    }

    public function test_changing_a_users_role_clears_old_individual_overrides(): void
    {
        [$owner, $business, $inspector] = $this->processorTeam(
            BusinessUser::ROLE_INSPECTOR
        );
        BusinessUserPermissionOverride::query()->create([
            'business_id' => $business->id,
            'user_id' => $inspector->id,
            'permission' => BusinessUser::PERMISSION_ISSUE_CERTIFICATE,
            'is_allowed' => false,
        ]);

        $this->actingAs($owner)
            ->withSession(['active_processor_business_id' => $business->id])
            ->put(route('tenant-users.update', $inspector), [
                'name' => $inspector->name,
                'email' => $inspector->email,
                'role' => BusinessUser::ROLE_COMPLIANCE_OFFICER,
                'business_ids' => [$business->id],
            ])
            ->assertRedirect(route('tenant-users.index'));

        $this->assertDatabaseMissing('business_user_permission_overrides', [
            'business_id' => $business->id,
            'user_id' => $inspector->id,
        ]);
        $this->assertTrue($inspector->canProcessorPermission(
            BusinessUser::PERMISSION_MONITOR_COMPLIANCE_METRICS,
            $business->id
        ));
    }

    public function test_individual_finance_access_appears_in_the_sidebar(): void
    {
        [$owner, $business, $inspector] = $this->processorTeam(
            BusinessUser::ROLE_INSPECTOR
        );

        $this->assertFalse($inspector->showsProcessorFinanceSidebar($business->id));

        $this->actingAs($owner)
            ->withSession(['active_processor_business_id' => $business->id])
            ->put(route('tenant-users.user-permissions.update', $inspector), [
                'business_id' => $business->id,
                'permissions' => array_merge(
                    BusinessUser::permissionsForRole(BusinessUser::ROLE_INSPECTOR, $business->id),
                    [BusinessUser::PERMISSION_VIEW_FINANCE_DASHBOARD]
                ),
            ])
            ->assertRedirect();

        $this->assertTrue($inspector->canProcessorPermission(
            BusinessUser::PERMISSION_VIEW_FINANCE_DASHBOARD,
            $business->id
        ));
        $this->assertTrue($inspector->showsProcessorFinanceSidebar($business->id));
    }

    public function test_business_owner_access_cannot_be_customized(): void
    {
        [$owner, $business] = $this->processorTeam();
        $otherOwner = User::factory()->create();
        $otherBusiness = Business::factory()->create([
            'user_id' => $otherOwner->id,
            'type' => Business::TYPE_PROCESSOR,
        ]);
        BusinessUser::query()->create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherOwner->id,
            'role' => BusinessUser::ROLE_ORG_ADMIN,
        ]);

        $this->actingAs($owner)
            ->withSession(['active_processor_business_id' => $business->id])
            ->get(route('tenant-users.user-permissions.index', $otherOwner))
            ->assertForbidden();
    }

    /**
     * @return array{User, Business, User}
     */
    private function processorTeam(
        string $memberRole = BusinessUser::ROLE_OPERATIONS_MANAGER
    ): array {
        $owner = User::factory()->create();
        $business = Business::factory()->create([
            'user_id' => $owner->id,
            'type' => Business::TYPE_PROCESSOR,
        ]);
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'role' => BusinessUser::ROLE_ORG_ADMIN,
        ]);

        $member = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $member->id,
            'role' => $memberRole,
        ]);

        return [$owner, $business, $member];
    }
}
