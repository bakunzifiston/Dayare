# Animal intake analysis

Analysis of the Buchapro **animal intake** module and live data in the `dayaremeat` database. Generated from codebase review and database queries (June 2026).

---

## Module overview

Animal intake is the **first step** in the processor slaughter chain. Every slaughter plan must trace back to an intake record.

```
Animal intake → Slaughter plan → Ante-mortem → Slaughter execution → Batch
```

### What each record captures

| Area | Fields / behaviour |
|------|-------------------|
| Origin | Supplier or client, farm name, administrative location |
| Livestock | Species, sex, age, `number_of_animals`, ear tags / ID numbers |
| Compliance | Health certificate (number, issue/expiry dates), movement permit document |
| Transport | Vehicle plate, driver name |
| Commercial | `unit_price`, `total_price` (feeds AP payables via finance sync) |
| Status | `received` → `approved` or `rejected` |

### Key model behaviour

- **`remainingAnimalsAvailable()`** — heads not yet assigned to slaughter plans (`number_of_animals` minus sum of `number_of_animals_scheduled` on linked plans).
- **`isHealthCertificateExpired()`** — if expiry date is in the past, new slaughter plan creation can be blocked.
- **Species constants** — `Cattle`, `Goat`, `Sheep`, `Pig`, `Other` (title case in DB).
- **Source types** — `supplier` or `client`.

### Routes

| Route | Name |
|-------|------|
| `/animal-intakes/overview` | `animal-intakes.hub` |
| `/animal-intakes` | `animal-intakes.*` (resource CRUD) |

See also [PROCESSOR_WORKSPACE.md](./PROCESSOR_WORKSPACE.md) for role permissions and workspace context.

---

## Database snapshot (all businesses)

| Metric | Value |
|--------|-------|
| Total intake records | **364** |
| Total animals (heads) | **8,135** |
| Status breakdown | **100% approved** (0 rejected, 0 still `received`) |
| Source breakdown | **100% supplier** |
| Average heads per intake | **22.3** |
| Estimated procurement value | **~RWF 2,921,661,000** |
| Average unit price | **~RWF 360,577** per head |
| Intakes in last 7 days | **0** (last intake: **2026-05-01**) |

### By species

| Species | Records | Heads | Share |
|---------|---------|-------|-------|
| Pig | 91 | 2,092 | 25.7% |
| Sheep | 91 | 2,090 | 25.7% |
| Cattle | 91 | 1,979 | 24.3% |
| Goat | 91 | 1,974 | 24.3% |

Species mix is evenly distributed across four types — consistent with comprehensive seeder patterns.

### Monthly volume trend

| Period | Typical volume |
|--------|----------------|
| 2022 – Apr 2025 | ~30–75 heads/month (2–4 intake records) |
| May 2025 onward | ~450–580 heads/month (~20 intake records/month) |
| 2026 YTD (Jan–May) | **1,988 heads** |
| 2025 Jan–May (comparison) | **702 heads** (+183% YoY for same period) |

May 2025 shows a step-change in volume, aligned with expanded demo/seed data rather than organic growth alone.

### Intakes by facility type

Only **slaughterhouse** facilities hold intake records. Butcheries and cold stores show zero intakes.

| Facility | Intake records |
|----------|----------------|
| Kigali Slaughterhouse | 88 |
| Slaughterhouse — 1 | 44 |
| Slaughterhouse — 2 | 44 |
| Slaughterhouse — 3 | 44 |
| Slaughterhouse — 4 | 44 |
| Slaughterhouse — 5 | 44 |
| Nyagatare Slaughterhouse | 56 |
| All butchery / cold store sites | 0 |

---

## Pipeline health

| Check | Result | Interpretation |
|-------|--------|----------------|
| Heads waiting to be scheduled | **0** | All intakes are fully allocated to slaughter plans |
| Intakes with no slaughter plan | **0** | No orphaned intake records |
| Rejected intakes | **0** | No rejection workflow in use |
| Expired health certificates | **304 / 364 (84%)** | Compliance risk — see below |

### Recent intake records (sample)

| ID | Date | Facility | Species | Heads | Farm | Remaining |
|----|------|----------|---------|-------|------|-----------|
| 220 | 2026-05-01 | Slaughterhouse — 5 | Pig | 28 | Ubworozi co-op lot 44 | 0 |
| 219 | 2026-04-30 | Slaughterhouse — 5 | Sheep | 30 | Ubworozi co-op lot 43 | 0 |
| 218 | 2026-04-28 | Slaughterhouse — 5 | Goat | 18 | Ubworozi co-op lot 42 | 0 |
| 217 | 2026-04-26 | Slaughterhouse — 5 | Cattle | 29 | Ubworozi co-op lot 41 | 0 |
| 216 | 2026-04-25 | Slaughterhouse — 5 | Pig | 17 | Ubworozi co-op lot 40 | 0 |

No intakes recorded after **2026-05-01** at time of analysis.

---

## Scoped example: Nyagatare Prime Meats 1

Metrics for a single processor business (3 facilities):

| Metric | Value |
|--------|-------|
| Intake records | 44 |
| Total heads | 1,057 |
| Status | All approved |
| Expired health certificates | **44 / 44 (100%)** |
| Pending approval (`received`) | 0 |
| Unscheduled heads remaining | 0 |

---

## Compliance findings

### Expired health certificates

- **304 records** platform-wide have `health_certificate_expiry_date` in the past.
- Model method `AnimalIntake::isHealthCertificateExpired()` returns true when expiry is past, which can **block slaughter plan creation** for affected intakes.
- Even when all animals are already scheduled, expired certs are a data-quality and audit issue for any new planning from those records.

### Recommendations

1. Run a bulk review of health certificate dates on active intakes.
2. Renew or update certificate metadata before creating new slaughter plans from historical intakes.
3. Consider a compliance report or dashboard alert for intakes with expired certs.

---

## Dashboard and reporting gaps

### “Animals in intake” KPI

The processor dashboard (`ProcessorDashboardService::animalsInIntake`) counts **intake records** with status `received` or `approved`, not **head count**:

```php
AnimalIntake::query()
    ->whereIn('facility_id', $ctx->facilityIds)
    ->whereIn('status', [AnimalIntake::STATUS_RECEIVED, AnimalIntake::STATUS_APPROVED])
    ->count();
```

For accurate operations reporting, prefer:

- **Heads in queue** — `sum(number_of_animals)` where `remainingAnimalsAvailable() > 0`
- **Records in queue** — count of intakes with remaining heads > 0

### Throughput chart species filter

`ProcessorDashboardCharts::intakeBySpeciesForDays` filters with `species LIKE '%cattle%'`. DB values are title case (`Cattle`, `Goat`, etc.). Matching works today but exact enum comparison is safer.

### Historical chart column

An earlier dashboard chart bug used non-existent column `quantity`; correct column is **`number_of_animals`**.

---

## Finance linkage

Animal intakes with pricing generate **AP payables** via `ProcessorFinanceSync`:

- Payable key: `animal_intake_id`
- Bucket: `supplier` or `client` based on `source_type`
- Amount: `total_price` or `number_of_animals × unit_price`

Total estimated intake procurement value across all records: **~RWF 2.92B**.

---

## Suggested next steps

| Priority | Action |
|----------|--------|
| High | Remediate expired health certificates on active intakes |
| High | Resume or verify intake data entry if operations expect daily receipts (no intakes since May 2026) |
| Medium | Change dashboard KPI to report **heads** and **unscheduled remaining** |
| Medium | Add intake analytics panel (species mix, monthly trend, compliance flags) |
| Low | Align chart species filters with `AnimalIntake::SPECIES_*` constants |

---

## Related files

| File | Purpose |
|------|---------|
| `app/Models/AnimalIntake.php` | Model, statuses, remaining-head logic |
| `app/Http/Controllers/AnimalIntakeController.php` | CRUD and hub |
| `app/Services/Processor/ProcessorDashboardService.php` | Dashboard KPIs referencing intake |
| `app/Services/Processor/ProcessorDashboardCharts.php` | Weekly throughput by species |
| `database/migrations/2025_03_01_190000_create_animal_intakes_table.php` | Base schema |

---

## Query reference

Example queries used for this analysis:

```sql
-- Totals by status
SELECT status, COUNT(*) AS records, SUM(number_of_animals) AS heads
FROM animal_intakes
GROUP BY status;

-- By species
SELECT species, COUNT(*) AS records, SUM(number_of_animals) AS heads
FROM animal_intakes
GROUP BY species
ORDER BY heads DESC;

-- Monthly trend
SELECT DATE_FORMAT(intake_date, '%Y-%m') AS month,
       COUNT(*) AS records,
       SUM(number_of_animals) AS heads
FROM animal_intakes
GROUP BY month
ORDER BY month;

-- Expired health certificates
SELECT COUNT(*) FROM animal_intakes
WHERE health_certificate_expiry_date IS NOT NULL
  AND health_certificate_expiry_date < CURDATE();
```

Laravel equivalent for remaining unscheduled heads per intake:

```php
AnimalIntake::query()
    ->whereIn('status', [AnimalIntake::STATUS_RECEIVED, AnimalIntake::STATUS_APPROVED])
    ->get()
    ->sum(fn (AnimalIntake $i) => $i->remainingAnimalsAvailable());
```
