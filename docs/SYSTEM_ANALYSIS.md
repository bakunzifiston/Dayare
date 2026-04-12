# DayareMeat System Analysis

## 1. Actors & User Model

| Actor | Description | Access |
|-------|-------------|--------|
| **Authenticated user (tenant)** | Each registered user is a tenant. Processor/logistics flows scope data by **Business** → facilities (`userFacilityIds`, etc.). | Dashboard, businesses, facilities, inspectors, animal intakes, slaughter plans, executions, batches, ante/post-mortem, certificates, warehouse storage, transport, delivery, compliance. |
| **Farmer workspace user** | Same `users` table; access is via **business membership** and `accessibleFarmerBusinessIds()` (not only `user_id` on a single business). | Routes under `workspace:farmer`: farms, livestock, farm health, supply requests, supply history, divisions dropdowns. Data scoped by farm → `business_id` on `farms`. |
| **Public (unauthenticated)** | Viewer scanning a meat product QR code. | GET `/trace/{slug}` — traceability page only. No auth. |

**Authorization model (processor-centric):**
- **BusinessController, FacilityController:** Check `$business->user_id === $request->user()->id` (abort 404 if not).
- **All other modules:** Controllers use `userFacilityIds($request)` (facilities belonging to the user’s businesses) and restrict queries with `whereIn('facility_id', $facilityIds)` or equivalent. Create/update validate that submitted `facility_id` (or related FK) is in `userFacilityIds`.
- **Middleware:** `tenant` middleware sets `app()->instance('tenant', $request->user())` for tenant-scoped logic. Routes under `auth` + `tenant` are tenant-scoped.

**Farmer workspace:**
- **`workspace:farmer`** ensures the user’s tenant workspace type is farmer.
- **`tenant.permission`** maps route names to Spatie permissions where configured; many farmer routes use `permission => null` in the sidebar (open to any farmer user with farm access).
- Farm access: `accessibleFarmerBusinessIds()` must contain the farm’s `business_id` (see `LivestockController::authorizeFarm`, `FarmController`, etc.).

---

## 2. Modules & Flow

| Module | Purpose | Main relationships |
|--------|---------|---------------------|
| **Businesses** | Tenant’s companies. | User → Businesses; Business → Facilities. |
| **Facilities** | Slaughterhouses, storage, etc. | Business → Facilities; Facility → Inspectors, SlaughterPlans, Certificates, AnimalIntakes, WarehouseStorages, TransportTrips (origin/destination). |
| **Inspectors** | Assigned to a facility. | Facility → Inspectors; Inspector → SlaughterPlans, AnteMortem, PostMortem, Certificates, Batches. |
| **Animal intakes** | Where animals come from (before slaughter). | Facility → AnimalIntakes; AnimalIntake → SlaughterPlans. Compliance: slaughter plan requires intake; health cert must be valid; species/count match. |
| **Slaughter plans** | Planned slaughter day. | Facility, Inspector, optional AnimalIntake → SlaughterPlan; SlaughterPlan → AnteMortemInspections, SlaughterExecutions. |
| **Slaughter executions** | Actual slaughter event. | SlaughterPlan → SlaughterExecutions; SlaughterExecution → Batches. |
| **Ante-mortem inspections** | Pre-slaughter inspection. | SlaughterPlan, Inspector → AnteMortemInspection. |
| **Batches** | Meat batch from an execution. | SlaughterExecution, Inspector → Batch; Batch → PostMortemInspection, Certificate, WarehouseStorage, TransportTrips. |
| **Post-mortem inspections** | Per-batch inspection. | Batch, Inspector → PostMortemInspection. |
| **Certificates** | Issued for a batch (or facility). | Batch (nullable), Facility (nullable), Inspector → Certificate; Certificate → CertificateQr, TransportTrips, WarehouseStorages. |
| **Certificate QR** | Public traceability slug. | Certificate → CertificateQr (1:1). |
| **Warehouse (cold storage)** | Storage before transport. | Facility (type=storage), Batch, Certificate → WarehouseStorage; WarehouseStorage → TemperatureLogs, TransportTrips. Compliance: certificate required; release required before transport; temp alerts; max storage days. |
| **Temperature logs** | Warehouse temperature records. | WarehouseStorage → TemperatureLogs. |
| **Transport trips** | Movement of certified meat. | Certificate, optional WarehouseStorage, optional Batch, origin/destination Facility → TransportTrip. |
| **Delivery confirmations** | Receipt at destination. | TransportTrip, receiving Facility → DeliveryConfirmation. |
| **Compliance** | Read-only dashboard of issues. | Aggregates: expired licenses, expired inspector auth, over-capacity plans, missing ante/post-mortem, missing certificates, missing transport, temp alerts, storage duration exceeded, intakes with expired health cert. |
| **Farms (farmer)** | Farmer-registered production sites. | `business_id` → Farm; Farm → Livestock, AnimalHealthRecord (via `farm_id`), supply request context (`source_farm_id`). |
| **Livestock (farmer)** | Herd rows per farm (type + breed uniqueness per farm). | Farm → Livestock (unique `farm_id` + `type` + `breed`); optional `LivestockDetail`; health quantities on the row; `AnimalHealthRecord` optional FK. |
| **Livestock details (farmer)** | Optional extended card data. | Livestock → LivestockDetail (1:1): `age_range`, `weight_range`, `notes` — not required for core operations. |
| **Supply requests (farmer ↔ processor)** | Processor requests animals; farmer accepts with a source farm. | `SupplyRequest` links processor/farmer businesses and facility; on accept, `SupplyRequestService` creates `AnimalIntake` and updates livestock quantities (see §2.1). |

### 2.1 Farmer: livestock, health counts vs logs, supply

**Livestock row (core inventory)**  
- Fields include: `type`, `breed`, `feeding_type`, `total_quantity`, `available_quantity`, `base_price`, `health_status` (quality metadata for scoring).  
- **Health quantities (authoritative for supply):** `healthy_quantity` + `sick_quantity` must equal `total_quantity` (validated when saving herd splits on the **Farm health** page). Non-negative integers.  
- **Computed (not stored):** `herd_health_status` — `healthy` if `sick_quantity === 0`; `sick` if `healthy_quantity === 0`; otherwise `mixed`.  
- **Quality score (computed):** `LivestockQualityScore` returns A/B/C from `health_status`, `feeding_type`, and `breed` — used in UI badges, not a separate DB column.

**Health: two separate concepts (do not mix)**  
1. **Counts (primary)** — Edited only via the farm **Farm health** UI (herd table: healthy vs sick per livestock row). Used for rollups (“total healthy / total sick” at farm level) and for **supply**.  
2. **Visit log (secondary)** — Table `animal_health_records`: `record_date`, `condition` (healthy/sick), optional `livestock_id`, `notes`. These rows are **history only**; they do **not** automatically change `healthy_quantity` / `sick_quantity`.

**Supply fulfilment rule**  
- `SupplyRequestService::accept` only allows fulfilment when the request quantity fits **`available_quantity`** and **`healthy_quantity`**.  
- On success it decrements **`available_quantity`**, **`healthy_quantity`**, and **`total_quantity`** by the fulfilled quantity (sick count unchanged; invariant `healthy + sick = total` preserved).

**UI entry points**  
- Farmer **Livestock** hub / per-farm livestock list: cards, optional detail modal (JSON), links to edit core inventory and to **Farm health** for splits.  
- **Farm health** (`/farmer/farms/{farm}/health-records`): summary counts, herd health table (save splits), add log entry, history table with optional livestock column.

### 2.2 Mobile API (`routes/api.php`)

- Laravel serves these under the default **`/api`** prefix; routes are grouped with **`v1`**, so paths are like **`/api/v1/auth/login`**, **`/api/v1/lookups`**, etc.  
- **Auth:** `POST /api/v1/auth/login` — email/password; issues a **Bearer** token backed by `mobile_api_tokens` (hashed storage). **`mobile.auth`** middleware validates the token for protected routes.  
- **Protected:** logout, `me`, lookups, animal intakes, slaughter plans, slaughter executions, ante/post-mortem collection endpoints — see `App\Http\Controllers\Api\MobileAuthController` and `MobileCollectionController`.

---

## 3. Database Relationships & Foreign Keys

### 3.1 Consistency check

- **Required FKs (non-nullable):** `businesses.user_id`, `facilities.business_id`, `inspectors.facility_id`, `slaughter_plans.facility_id`, `slaughter_plans.inspector_id`, `slaughter_executions.slaughter_plan_id`, `batches.slaughter_execution_id`, `batches.inspector_id`, `ante_mortem_inspections.slaughter_plan_id`, `post_mortem_inspections.batch_id`, `warehouse_storages.warehouse_facility_id`, `warehouse_storages.batch_id`, `warehouse_storages.certificate_id`, `temperature_logs.warehouse_storage_id`, `transport_trips.certificate_id`, `transport_trips.origin_facility_id`, `transport_trips.destination_facility_id`, `delivery_confirmations.transport_trip_id`, `delivery_confirmations.receiving_facility_id`, `animal_intakes.facility_id`. All are consistent with model relationships.

- **Nullable FKs:**  
  - `certificates.batch_id`, `certificates.facility_id` — certificate can exist without batch (e.g. facility-level).  
  - `transport_trips.batch_id`, `transport_trips.warehouse_storage_id` — trip can be linked to certificate only or to released storage.  
  - `slaughter_plans.animal_intake_id` — backward compatibility for plans created before intakes existed.  
  - Business, Facility, AnimalIntake: `country_id`, `province_id`, `district_id`, `sector_id`, `cell_id`, `village_id` (Rwanda divisions) — all nullable.  
  - `animal_health_records.livestock_id` — optional link to a herd row for logging context only.

- **Unique constraints:** `certificate_qrs.slug`, `certificate_qrs.certificate_id`, `certificates.batch_id`, `post_mortem_inspections.batch_id`, `delivery_confirmations.transport_trip_id`, `businesses.registration_number` — consistent with one-to-one or business rules.  
- **Livestock:** unique `(farm_id, type, breed)` (MySQL: replacing older `farm_id + type` only after migration); supports multiple breeds per type on one farm.

- **Recommendation:** Ensure `certificates.batch_id` unique is not violated when creating certificates (one certificate per batch). CertificateSeeder uses `firstOrCreate` on `batch_id` to avoid duplicates.

---

## 4. Form Requests & Input Validation

- **Business, Facility, Inspector, Animal Intake, Slaughter Plan, Slaughter Execution, Batch, PostMortem, Certificate, Transport, Delivery, AnteMortem:** Each has `Store*` and `Update*` Form Request with `authorize(): true` (authorization done in controller). Rules use `required`, `exists:table,id`, `Rule::in()`, `Rule::exists()->where()`, dates, etc.
- **Slaughter plan (store):** `animal_intake_id` required; `withValidator` checks intake facility match, health cert not expired, species match, `number_of_animals_scheduled` ≤ remaining from intake.
- **Slaughter plan (update):** Same custom rules; remaining count excludes current plan’s scheduled count when editing.
- **Warehouse storage:** No dedicated Form Request; validation in controller with `Rule::in($facilityIds->all())` and `Rule::in($certificateIds->all())` so only user’s facilities/certificates are accepted. Compliance checks (active certificate, not already in storage) done in controller.
- **Farmer livestock:** `StoreLivestockRequest` / `UpdateLivestockRequest` include optional breed, feeding, pricing, extended detail fields; compound unique on `(farm_id, type, breed)`. Herd health splits validated in `UpdateFarmLivestockHealthSplitsRequest` (healthy + sick = total per row).
- **Recommendation:** Consider moving warehouse storage rules into `StoreWarehouseStorageRequest` and `UpdateWarehouseStorageRequest` for consistency and reuse.

---

## 5. Potential Runtime / Null-Pointer Issues

| Location | Issue | Status / Fix |
|----------|--------|---------------|
| **compliance/index.blade.php** | `$c->facility->facility_name` when `batch` is null and `facility` is null. | **Fixed:** Use `$c->facility?->facility_name ?? ''`. |
| **compliance/index.blade.php** | Long chain `$b->slaughterExecution->slaughterPlan->facility->facility_name`. | Schema has required FKs; safe. Optionally use `optional($b->slaughterExecution)->slaughterPlan?->facility?->facility_name ?? ''` for defensive coding. |
| **WarehouseStorageController (create)** | `$c->batch->batch_code` when building certificate list. | Query uses `whereNotNull('batch_id')`, so `$c->batch` is always set. For robustness, use `$c->batch?->batch_code` and `$c->batch?->quantity`. |
| **TraceabilityController** | Uses `?->` and `??` throughout; `firstOrFail()` on slug. | Safe; 404 when slug missing. |
| **Business show** | `owner_dob`, division names. | Wrapped in `@if ($business->owner_dob)` and `@if ($business->countryDivision)` etc. Safe. |
| **Animal intakes show** | `health_certificate_expiry_date`, facility. | Uses `?->format()` and `?? ''`. Safe. |
| **Transport trip show** | `$trip->batch`, `$trip->warehouseStorage`, `$trip->deliveryConfirmation`. | Batch and warehouseStorage wrapped in `@if`; deliveryConfirmation wrapped in `@if`. Safe. |

---

## 6. Performance Considerations

- **N+1:** Controllers generally use `with()` / `load()` for relations used in views (e.g. `facility`, `batch`, `slaughterExecution.slaughterPlan.facility`). Compliance and traceability load nested relations. Keep an eye on list pages (index) that loop over collections and ensure eager loading is used for every relation accessed in the loop.
- **Compliance index:** Multiple queries (one per issue type). Acceptable for a dashboard; if the number of facilities grows large, consider caching or a single aggregated query.
- **Traceability:** Single query with deep `load()`; no N+1.
- **Farmer livestock lists:** Eager-load `detail` where needed; hub aggregates health totals with a dedicated query across all farms’ livestock when paginating hub rows.

---

## 7. Security

- **Authentication:** All app routes except `/`, `/trace/{slug}`, and auth routes are under `auth` (and `tenant` where applied). Traceability is public by design.
- **Authorization:** Per-resource checks (user_id for business/facility; facility_id in userFacilityIds for plans, intakes, certificates, etc.). No reliance solely on Form Request `authorize()` for tenant scope; controllers enforce it.
- **Farmer API (mobile):** Token-based (`mobile_api_tokens`); middleware `mobile.auth` resolves user from `Authorization: Bearer <token>`.
- **Mass assignment:** Models use `$fillable`; no `$guarded = []` that would allow mass assignment of arbitrary columns.
- **CSRF:** Laravel default for state-changing web requests; API JSON uses token auth, not session CSRF for those routes.
- **SQL injection:** Eloquent and query builder; no raw SQL with user input.
- **XSS:** Blade `{{ }}` escapes output.
- **Recommendation:** Ensure any future file upload or export validates file types and limits size; ensure rate limiting on login and public traceability if needed.

---

## 8. Suggested Automated Tests

See **Section 9** below for concrete test file suggestions (Feature and Unit). Priority areas:

- Tenant authorization (cannot access another user’s business/facility/plan/certificate).
- Animal intake → slaughter plan compliance (facility match, health cert, species, count).
- Certificate / warehouse / transport flow (store only with valid certificate; release before transport).
- Traceability page: slug found → 200 with correct data; slug not found → 404.
- Form validation: required fields, `exists`, `Rule::in`, custom `withValidator` (e.g. slaughter plan intake rules).
- **Farmer (optional):** farm access boundaries; herd split validation; supply accept rejects when `healthy_quantity` insufficient.

---

## 9. Suggested Test Cases (Feature & Unit)

### 9.1 Feature tests

- **TenantAuthorizationTest**
  - User A cannot see User B’s business (show returns 404).
  - User A cannot see User B’s facility (via facility show).
  - User A cannot create slaughter plan for Facility belonging to User B (store with facility_id of B’s facility fails or redirects with error).
  - User A cannot update/delete another user’s animal intake, certificate, warehouse storage, transport trip.

- **AnimalIntakeSlaughterPlanComplianceTest**
  - Creating slaughter plan with expired health certificate fails validation.
  - Creating slaughter plan with species mismatch to intake fails.
  - Creating slaughter plan with `number_of_animals_scheduled` > intake remaining fails.
  - Creating slaughter plan with intake from another facility fails.

- **WarehouseStorageTest**
  - Store warehouse storage with certificate not in user’s scope fails.
  - Store warehouse storage with certificate already in storage fails.
  - Store warehouse storage with inactive certificate fails.

- **TraceabilityTest**
  - GET `/trace/valid-slug` returns 200 and shows certificate/origin info when slug exists.
  - GET `/trace/invalid-slug` returns 404.

- **CompliancePageTest**
  - Authenticated user can access compliance index and sees only issues for their facilities (e.g. expired licenses for their facilities only).

### 9.2 Unit tests

- **AnimalIntakeTest**
  - `isHealthCertificateExpired()` returns true when expiry date is in the past.
  - `remainingAnimalsAvailable()` equals `number_of_animals` minus total scheduled in linked slaughter plans.

- **FacilityTest**
  - `getLocationDisplayAttribute()` returns division hierarchy when division FKs are set; falls back to legacy district/sector when present.

- **CertificateQrTest**
  - `generateSlug()` produces a unique slug; `getTraceUrlAttribute()` returns URL containing the slug.

- **SlaughterPlanRequestTest (or validation unit)**
  - Store request rejects when `animal_intake_id` is for another facility.
  - Store request rejects when intake health certificate is expired.
  - Store request rejects when species does not match intake.
  - Store request rejects when `number_of_animals_scheduled` > intake remaining.

Implementing these will give good coverage for tenant isolation, compliance rules, and public traceability.

### 9.3 Implemented tests (in repo)

- **tests/Feature/TenantAuthorizationTest.php** — User cannot view/update another user's business; user can view own business.
- **tests/Feature/TraceabilityTest.php** — Invalid slug returns 404; valid slug returns 200 with certificate info.
- **tests/Unit/AnimalIntakeTest.php** — `isHealthCertificateExpired()` (past/future/null); `remainingAnimalsAvailable()` with and without slaughter plans.
- **tests/Feature/AnimalIntakeSlaughterPlanComplianceTest.php** — Slaughter plan store fails when health certificate expired or when scheduled count exceeds remaining animals.

Run: `php artisan test tests/Feature/TenantAuthorizationTest.php tests/Feature/TraceabilityTest.php tests/Unit/AnimalIntakeTest.php tests/Feature/AnimalIntakeSlaughterPlanComplianceTest.php`
