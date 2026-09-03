<?php

namespace Tests\Feature\SalesCompliance;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SalesComplianceCertificateRule;
use App\Models\SalesComplianceEscalation;
use App\Models\SalesComplianceInspection;
use App\Models\SalesComplianceSite;
use App\Models\User;
use App\Support\SalesComplianceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesComplianceModuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Business, 2: Inspector}
     */
    private function processorContext(): array
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create([
            'type' => Business::TYPE_PROCESSOR,
        ]);
        $facility = Facility::factory()->create([
            'business_id' => $business->id,
        ]);
        $inspector = Inspector::factory()->create([
            'facility_id' => $facility->id,
            'status' => Inspector::STATUS_ACTIVE,
        ]);

        return [$user, $business, $inspector];
    }

    public function test_owner_can_schedule_a_site_visit(): void
    {
        [$user, $business, $inspector] = $this->processorContext();
        $site = SalesComplianceSite::factory()->create([
            'business_id' => $business->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_processor_business_id' => $business->id])
            ->post(route('sales-compliance.inspections.store'), [
                'site_id' => $site->id,
                'scheduled_date' => now()->addDay()->toDateString(),
                'scheduled_time' => '10:30',
                'assignee' => 'inspector:'.$inspector->id,
            ]);

        $inspection = SalesComplianceInspection::query()->where('business_id', $business->id)->first();
        $this->assertNotNull($inspection);
        $response->assertRedirect(route('sales-compliance.inspections.show', $inspection));
        $this->assertSame(SalesComplianceCatalog::STATUS_PENDING, $inspection->status);
        $this->assertSame((int) $inspector->id, (int) $inspection->inspector_id);
        $this->assertSame((int) $user->id, (int) $inspection->created_by);
    }

    public function test_restaurant_missing_certificate_fails_when_rule_requires_it(): void
    {
        [$user, $business, $inspector] = $this->processorContext();
        $site = SalesComplianceSite::factory()->create(['business_id' => $business->id]);
        $inspection = SalesComplianceInspection::factory()->create([
            'business_id' => $business->id,
            'site_id' => $site->id,
            'inspector_id' => $inspector->id,
            'created_by' => $user->id,
        ]);

        $this->assertTrue(SalesComplianceCertificateRule::isCertificateRequired(
            $business->id,
            SalesComplianceCatalog::SITE_RESTAURANT,
            SalesComplianceCatalog::MEAT_SOURCE_WET_MARKET
        ));

        $this->actingAs($user)
            ->withSession(['active_processor_business_id' => $business->id])
            ->post(route('sales-compliance.inspections.record', $inspection), [
                'meat_source' => SalesComplianceCatalog::MEAT_SOURCE_WET_MARKET,
                'inspector_notes' => 'Certificate not on file.',
                'responses' => [
                    'cold_room_condition' => ['result' => SalesComplianceCatalog::RESULT_PASS, 'notes' => 'OK'],
                    'freezer_condition' => ['result' => SalesComplianceCatalog::RESULT_PASS],
                    'general_hygiene' => ['result' => SalesComplianceCatalog::RESULT_PASS],
                    'certificate_of_origin' => ['result' => SalesComplianceCatalog::RESULT_MISSING],
                ],
            ])
            ->assertRedirect(route('sales-compliance.inspections.show', $inspection));

        $this->assertSame(SalesComplianceCatalog::STATUS_FAILED, $inspection->fresh()->status);
        $this->assertSame(SalesComplianceCatalog::MEAT_SOURCE_WET_MARKET, $inspection->fresh()->meat_source);
    }

    public function test_restaurant_own_farm_skips_certificate_and_can_pass(): void
    {
        [$user, $business, $inspector] = $this->processorContext();
        $site = SalesComplianceSite::factory()->create(['business_id' => $business->id]);
        $inspection = SalesComplianceInspection::factory()->create([
            'business_id' => $business->id,
            'site_id' => $site->id,
            'inspector_id' => $inspector->id,
        ]);

        $this->assertFalse(SalesComplianceCertificateRule::isCertificateRequired(
            $business->id,
            SalesComplianceCatalog::SITE_RESTAURANT,
            SalesComplianceCatalog::MEAT_SOURCE_OWN_FARM
        ));

        $this->actingAs($user)
            ->withSession(['active_processor_business_id' => $business->id])
            ->post(route('sales-compliance.inspections.record', $inspection), [
                'meat_source' => SalesComplianceCatalog::MEAT_SOURCE_OWN_FARM,
                'responses' => [
                    'cold_room_condition' => ['result' => SalesComplianceCatalog::RESULT_PASS],
                    'freezer_condition' => ['result' => SalesComplianceCatalog::RESULT_PASS],
                    'general_hygiene' => ['result' => SalesComplianceCatalog::RESULT_PASS],
                ],
            ])
            ->assertRedirect();

        $fresh = $inspection->fresh(['responses']);
        $this->assertSame(SalesComplianceCatalog::STATUS_PASSED, $fresh->status);
        $this->assertSame(
            SalesComplianceCatalog::RESULT_NA,
            $fresh->responses->firstWhere('item_key', 'certificate_of_origin')?->result
        );
    }

    public function test_butchery_product_lines_with_missing_certificate_fail(): void
    {
        [$user, $business, $inspector] = $this->processorContext();
        $site = SalesComplianceSite::factory()->butchery()->create(['business_id' => $business->id]);
        $inspection = SalesComplianceInspection::factory()->create([
            'business_id' => $business->id,
            'site_id' => $site->id,
            'inspector_id' => $inspector->id,
        ]);

        $this->actingAs($user)
            ->withSession(['active_processor_business_id' => $business->id])
            ->post(route('sales-compliance.inspections.record', $inspection), [
                'meat_source' => SalesComplianceCatalog::MEAT_SOURCE_PROCESSOR,
                'responses' => [
                    'hygiene_standards' => ['result' => SalesComplianceCatalog::RESULT_PASS],
                ],
                'product_lines' => [
                    [
                        'product_name' => 'Beef chuck',
                        'quantity_description' => '12 kg',
                        'certificate_status' => SalesComplianceCatalog::RESULT_MISSING,
                    ],
                    [
                        'product_name' => 'Goat ribs',
                        'quantity_description' => '4 carcasses',
                        'certificate_status' => SalesComplianceCatalog::RESULT_PRESENT,
                    ],
                ],
            ])
            ->assertRedirect();

        $fresh = $inspection->fresh(['productLines']);
        $this->assertSame(SalesComplianceCatalog::STATUS_FAILED, $fresh->status);
        $this->assertCount(2, $fresh->productLines);
    }

    public function test_private_event_checklist_records_event_products_and_proof(): void
    {
        [$user, $business, $inspector] = $this->processorContext();
        $site = SalesComplianceSite::factory()->privateEvent()->create(['business_id' => $business->id]);
        $inspection = SalesComplianceInspection::factory()->create([
            'business_id' => $business->id,
            'site_id' => $site->id,
            'inspector_id' => $inspector->id,
        ]);

        $this->actingAs($user)
            ->withSession(['active_processor_business_id' => $business->id])
            ->post(route('sales-compliance.inspections.record', $inspection), [
                'meat_source' => SalesComplianceCatalog::MEAT_SOURCE_CERTIFIED_SUPPLIER,
                'inspector_notes' => 'Wedding catering inspected.',
                'responses' => [
                    'certificate_for_meat' => ['result' => SalesComplianceCatalog::RESULT_PRESENT],
                    'proof_of_purchase' => ['result' => SalesComplianceCatalog::RESULT_PRESENT],
                ],
                'product_lines' => [
                    [
                        'product_name' => 'Beef tenderloin',
                        'quantity_description' => '8 kg',
                        'certificate_status' => SalesComplianceCatalog::RESULT_PRESENT,
                    ],
                ],
            ])
            ->assertRedirect();

        $fresh = $inspection->fresh(['productLines', 'responses']);
        $this->assertSame(SalesComplianceCatalog::STATUS_PASSED, $fresh->status);
        $this->assertSame('wedding', $fresh->site->event_type);
        $this->assertCount(1, $fresh->productLines);
    }

    public function test_visit_can_be_assigned_to_inspector_role_user(): void
    {
        [$user, $business] = $this->processorContext();
        $site = SalesComplianceSite::factory()->create(['business_id' => $business->id]);
        $inspectorUser = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $inspectorUser->id,
            'role' => BusinessUser::ROLE_INSPECTOR,
        ]);

        $this->actingAs($user)
            ->withSession(['active_processor_business_id' => $business->id])
            ->post(route('sales-compliance.inspections.store'), [
                'site_id' => $site->id,
                'scheduled_date' => now()->addDays(2)->toDateString(),
                'scheduled_time' => '14:00',
                'assignee' => 'user:'.$inspectorUser->id,
            ])
            ->assertRedirect();

        $inspection = SalesComplianceInspection::query()->where('business_id', $business->id)->first();
        $this->assertNotNull($inspection);
        $this->assertSame((int) $inspectorUser->id, (int) $inspection->assigned_user_id);
        $this->assertNull($inspection->inspector_id);
    }

    public function test_dashboard_flags_repeat_non_compliant_sites(): void
    {
        [$user, $business, $inspector] = $this->processorContext();
        $site = SalesComplianceSite::factory()->create(['business_id' => $business->id]);

        foreach ([now()->subDays(20), now()->subDays(5)] as $date) {
            SalesComplianceInspection::factory()->create([
                'business_id' => $business->id,
                'site_id' => $site->id,
                'inspector_id' => $inspector->id,
                'status' => SalesComplianceCatalog::STATUS_FAILED,
                'scheduled_date' => $date->toDateString(),
                'completed_at' => $date,
            ]);
        }

        $this->actingAs($user)
            ->withSession(['active_processor_business_id' => $business->id])
            ->get(route('sales-compliance.hub', ['view' => 'repeat']))
            ->assertOk()
            ->assertSee($site->name)
            ->assertSee('Repeatedly non-compliant');
    }

    public function test_escalation_status_can_be_tracked(): void
    {
        [$user, $business] = $this->processorContext();
        $site = SalesComplianceSite::factory()->create(['business_id' => $business->id]);

        $this->actingAs($user)
            ->withSession(['active_processor_business_id' => $business->id])
            ->post(route('sales-compliance.escalations.store'), [
                'site_id' => $site->id,
                'reason' => 'Repeat hygiene failures',
                'notes' => 'Second failed visit this month.',
            ])
            ->assertRedirect();

        $escalation = SalesComplianceEscalation::query()->where('business_id', $business->id)->first();
        $this->assertNotNull($escalation);
        $this->assertSame(SalesComplianceCatalog::ESCALATION_OPEN, $escalation->status);
        $this->assertSame((int) $user->id, (int) $escalation->created_by);

        $this->actingAs($user)
            ->withSession(['active_processor_business_id' => $business->id])
            ->put(route('sales-compliance.escalations.update', $escalation), [
                'status' => SalesComplianceCatalog::ESCALATION_IN_REVIEW,
                'notes' => 'Assigned to compliance officer.',
            ])
            ->assertRedirect(route('sales-compliance.escalations.show', $escalation));

        $this->assertSame(SalesComplianceCatalog::ESCALATION_IN_REVIEW, $escalation->fresh()->status);
        $this->assertSame((int) $user->id, (int) $escalation->fresh()->updated_by);

        $this->actingAs($user)
            ->withSession(['active_processor_business_id' => $business->id])
            ->put(route('sales-compliance.escalations.update', $escalation), [
                'status' => SalesComplianceCatalog::ESCALATION_RESOLVED,
            ])
            ->assertRedirect();

        $this->assertSame(SalesComplianceCatalog::ESCALATION_RESOLVED, $escalation->fresh()->status);
    }

    public function test_user_without_permission_cannot_open_compliance_hub(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->for($owner)->create([
            'type' => Business::TYPE_PROCESSOR,
        ]);
        $member = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $member->id,
            'role' => BusinessUser::ROLE_ACCOUNTANT,
        ]);

        $this->actingAs($member)
            ->withSession(['active_processor_business_id' => $business->id])
            ->get(route('sales-compliance.hub'))
            ->assertForbidden();
    }

    public function test_certificate_rule_override_changes_requirement_without_code(): void
    {
        [$user, $business] = $this->processorContext();

        $this->assertTrue(SalesComplianceCertificateRule::isCertificateRequired(
            $business->id,
            SalesComplianceCatalog::SITE_RESTAURANT,
            SalesComplianceCatalog::MEAT_SOURCE_PROCESSOR
        ));

        $this->actingAs($user)
            ->withSession(['active_processor_business_id' => $business->id])
            ->post(route('sales-compliance.rules.store'), [
                'site_type' => SalesComplianceCatalog::SITE_RESTAURANT,
                'meat_source' => SalesComplianceCatalog::MEAT_SOURCE_PROCESSOR,
                'certificate_required' => '0',
                'notes' => 'Processor-packed meat already certified at source.',
            ])
            ->assertRedirect(route('sales-compliance.rules.index'));

        $this->assertFalse(SalesComplianceCertificateRule::isCertificateRequired(
            $business->id,
            SalesComplianceCatalog::SITE_RESTAURANT,
            SalesComplianceCatalog::MEAT_SOURCE_PROCESSOR
        ));
    }

    public function test_site_location_is_saved_from_rwanda_division_selection(): void
    {
        [$user, $business] = $this->processorContext();
        $country = AdministrativeDivision::query()->create([
            'name' => 'Rwanda',
            'type' => AdministrativeDivision::TYPE_COUNTRY,
        ]);
        $province = AdministrativeDivision::query()->create([
            'parent_id' => $country->id,
            'name' => 'Kigali City',
            'type' => AdministrativeDivision::TYPE_PROVINCE,
        ]);
        $district = AdministrativeDivision::query()->create([
            'parent_id' => $province->id,
            'name' => 'Gasabo',
            'type' => AdministrativeDivision::TYPE_DISTRICT,
        ]);
        $sector = AdministrativeDivision::query()->create([
            'parent_id' => $district->id,
            'name' => 'Kacyiru',
            'type' => AdministrativeDivision::TYPE_SECTOR,
        ]);

        $this->actingAs($user)
            ->withSession(['active_processor_business_id' => $business->id])
            ->post(route('sales-compliance.sites.store'), [
                'site_type' => SalesComplianceCatalog::SITE_RESTAURANT,
                'name' => 'Kacyiru Grill',
                'country_id' => $country->id,
                'province_id' => $province->id,
                'district_id' => $district->id,
                'sector_id' => $sector->id,
                'contact_name' => 'Aline Uwase',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $site = SalesComplianceSite::query()->where('business_id', $business->id)->first();
        $this->assertNotNull($site);
        $this->assertSame((int) $country->id, (int) $site->country_id);
        $this->assertSame((int) $province->id, (int) $site->province_id);
        $this->assertSame((int) $district->id, (int) $site->district_id);
        $this->assertSame((int) $sector->id, (int) $site->sector_id);
        $this->assertSame('Kigali City, Gasabo, Kacyiru', $site->locationDisplay());
    }
}
