# Conversation Summary — DayareMeat Processor Workspace

## User requests (chronological)

1. Align **FarmerWorkspaceDemoSeeder** with movement module redesign.
2. Explain **"Lifecycle status: Dead"** on animal passport.
3. **Remove lifecycle status** from public animal passport PDF.
4. **Clean animal traceability passport** PDF UI.
5. Apply **BuchaPro color codes** to table headers (user tried `#178236` and `#90A1B9`, then confirmed **official BuchaPro** colors).
6. **Analyze** processor business registration fields vs slaughterhouse/VIBE questionnaire; identify gaps.
7. **Add 14 fields** to business registration: `year_business_started`, `access_road_condition`, `network_connectivity`, workforce counts, `bank_account_type`, `uses_mobile_money`, booleans for digital payments/record keeping/ledger, `record_keeping_system`, `available_devices` (JSON multi-select).
8. Remove subtitle **"VIBE reporting and processor profile details."** from profile-extensions partial.
9. Remove **"VIBE metadata" section title/comment only** — user clarified **not the fields**; VIBE fields were briefly removed by mistake, then **restored without section heading** (unnamed card with fields still labeled "VIBE …").
10. **Remove Farmer supply** module from processor workspace (nav + routes; redirects to dashboard).
11. **Analyze** processor workspace + register business form sections.
12. **Implement wizard UI** on Register Business form (screenshot: left sidebar "Business Onboarding Wizard", 7 steps, progress, draft save) — **in progress, not finished**.

---

## BuchaPro brand colors (`resources/css/app.css`)

- Primary: `#a11d1e`
- Burgundy: `#7a1516`
- Sidebar: `#2d2d2c`
- Canvas: `#f7fafc`

---

## Key files changed

### Animal passport PDF
- `resources/views/public/animal-passport-pdf.blade.php` — redesigned layout; lifecycle row removed; table headers use BuchaPro primary/burgundy; mint-style table header optional in design.

### FarmerWorkspaceDemoSeeder
- `database/seeders/FarmerWorkspaceDemoSeeder.php` — movement module: permit requests, permits, history; `purgeMovementModuleData()`; partial re-seed if farm exists but no permit requests.

### Processor business profile (14 fields)
- Migration: `database/migrations/2026_05_18_100000_add_processor_profile_fields_to_businesses_table.php` (run locally).
- Model: `app/Models/Business.php` — constants, casts, label helpers (`accessRoadConditionOptions`, etc.).
- Validation: `app/Http/Requests/Concerns/ValidatesBusinessProcessorProfile.php` used by `StoreBusinessRequest` and `UpdateBusinessRequest`.
- Form partial: `resources/views/businesses/partials/profile-extensions.blade.php` — section title "Operations, workforce & digital" (subtitle removed).
- Included from `resources/views/businesses/create.blade.php` and `edit.blade.php`.
- Show page: `resources/views/businesses/show.blade.php` displays new fields.

### Register business form (`create.blade.php`) — current structure (still single-page, not wizard yet)

1. Business info (required: name, RDB #, phone, email; status hidden as `active` in wizard step partial only — **main create may still show status**).
2. Ownership info (+ conditional members).
3. Business details (size, revenue bracket).
4. `@include profile-extensions` (operations/workforce/digital).
5. Unnamed card: VIBE unique ID, commencement date, pathway status, comments.
6. Location (Rwanda admin cascade).
7. Submit / Cancel.

### Farmer supply removed
- `resources/views/layouts/sidebar.blade.php` — removed "Farmer supply" nav item.
- `routes/web.php` — processor supply routes → redirects to `/dashboard`; removed `ProcessorSupplyRequestController` import.
- `app/Http/Middleware/EnsureTenantPermission.php` — removed `processor.supply-requests` map entry.

### Wizard step partials created (NOT wired to create view yet)

Under `resources/views/businesses/partials/wizard/`:

| File | Content |
|------|---------|
| `step-business-info.blade.php` | Business fields; hidden `status=active`; `data-wizard-required` on required inputs |
| `step-ownership.blade.php` | Owner + members (uses Alpine `ownershipType`, `members`) |
| `step-business-details.blade.php` | business_size, baseline_revenue |
| `step-operations-connectivity.blade.php` | year, access road, network |
| `step-workforce-digital.blade.php` | workforce, banking, digital (uses `digitalRecordKeeping`) |
| `step-programme.blade.php` | VIBE fields |
| `step-location.blade.php` | Location selects with `data-wizard-location`; uses parent Alpine `countryId`, etc. |

**Missing:** Main wizard shell (`onboarding-wizard-create.blade.php` or similar), Alpine `businessOnboardingWizard()` (step nav, progress %, localStorage draft, merge location + ownership logic), and replacing `resources/views/businesses/create.blade.php` body with wizard layout.

---

## Processor workspace (sidebar)

**Operations:** Businesses, Inspectors, Animal intake, Slaughter planning, Ante-mortem, Slaughter execution, Batches, Post-mortem, Certificates, Cold Room, Transport, Delivery confirmation, Compliance. **Farmer supply removed.**

**CRM & HR, Finance, Users, Settings** unchanged.

**Onboarding:** Processor signup → no auto business → redirect to `businesses.create`. Active business switcher in sidebar when businesses exist.

---

## Errors / fixes

- Accidental `<motion>` HTML tags in blades — fixed via sed to `</div>`.
- User wanted VIBE **words** removed, not fields — restored VIBE field card without section title.
- Seeder partial re-seed needed `loadFarmAnimals()` when farm already populated.

---

## Gap vs slaughterhouse questionnaire (still mostly open)

Missing on registration: NID, WhatsApp, street address, animals processed, capacity/sales/infrastructure/compliance, cooperative member counts, document uploads, many VIBE-specific labels. New 14 fields cover part of workforce/digital/operations baseline.

---

## What remains to do

1. **Finish business onboarding wizard UI** for `businesses/create`:
   - Left panel: NEW BUSINESS SETUP, title, description, WHAT TO PREPARE / DATA QUALITY / COMPLETION RULE cards (BuchaPro sidebar colors).
   - Right panel: step pills (7), progress, draft auto-save, Prev/Next section, include step partials.
   - Single form POST to `businesses.store`; sync location hidden inputs on submit.
   - Location completion rule before submit (mock: location must be complete).
2. Optionally apply same wizard to **edit** or keep edit as long form.
3. User may want further questionnaire fields or facility-level fields later.

---

## Routes reference

- Register: `GET/POST /businesses/create` → `businesses.store` (`BusinessController`, defaults `type=processor`).
- Processor prefix: `processor/business-context` (business switcher); supply-requests redirect to dashboard.
