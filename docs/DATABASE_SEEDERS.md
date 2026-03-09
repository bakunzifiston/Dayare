# Database Seeders — DayareMeat

This document describes the database seeders used to generate realistic Rwanda-based sample data for testing relationships, workflows, and data integrity across the platform.

## Run seeders

```bash
# Full reset and seed (recommended for testing)
php artisan migrate:fresh --seed

# Seed only (migrations already run)
php artisan db:seed
```

**Test login:** `test@example.com` or `tester@dayare.me` — password: `password`

---

## Seeding order and dependencies

| Order | Seeder | What it seeds | Depends on |
|-------|--------|----------------|------------|
| 1 | AdministrativeDivisionSeeder | Rwanda country → provinces → districts → sectors → cells → villages (from GitHub or minimal fallback) | — |
| 2 | SpeciesSeeder | Cattle, Goat, Sheep, Pig, Other | — |
| 3 | TestDataSeeder | Users, Businesses, Facilities (slaughterhouse, butchery, storage), Inspectors, **Employees**, **Suppliers**, **Clients**, **Contracts** | AdministrativeDivision |
| 4 | AnimalIntakeSeeder | Animal intakes (Rwanda suppliers/farms, species, RWF unit_price), optionally linked to Supplier/Contract | Facility (slaughterhouse), AdministrativeDivision, optionally Supplier/Contract |
| 5 | SlaughterPlanSeeder | Slaughter plans (linked to intakes and inspectors) | AnimalIntake, Inspector |
| 6 | SlaughterExecutionSeeder | Slaughter executions | SlaughterPlan |
| 7 | AnteMortemInspectionSeeder | Ante-mortem inspections | SlaughterPlan |
| 8 | BatchSeeder | Batches | SlaughterExecution |
| 9 | PostMortemInspectionSeeder | Post-mortem inspections | Batch |
| 10 | CertificateSeeder | Certificates + QR slugs (only batches with approved_quantity > 0) | Batch, PostMortemInspection, Facility |
| 11 | WarehouseStorageSeeder | Warehouse storages (cold storage, quantity_unit from Unit) | Facility (storage), Certificate |
| 12 | TemperatureLogSeeder | Temperature logs per storage | WarehouseStorage |
| 13 | TransportTripSeeder | Transport trips (RAB plates, Rwanda drivers) | Certificate, Facility |
| 14 | DeliveryConfirmationSeeder | Delivery confirmations (optional client_id) | TransportTrip |
| 15 | **DemandSeeder** | Demands (draft, confirmed, in_progress, fulfilled, cancelled; some with fulfilled_by_delivery_id) | Business, Client, Facility, DeliveryConfirmation, Unit |
| 16 | **ClientActivitySeeder** | Client activities (call, email, meeting, note) | Client, User |

---

## Rwanda context

- **Locations:** Provinces (e.g. City of Kigali, Eastern Province), districts, sectors, cells, villages from Rwanda structure.
- **Names:** Rwandan names (e.g. Jean Niyonzima, Marie Uwera, Eric Nkusi).
- **Phones:** Local format `+250788XXXXXX`.
- **Currency:** RWF (e.g. contract amounts 5,000,000 – 15,000,000 RWF; unit_price in animal intakes).
- **Plates:** Rwanda format `RAB XXX X`.

---

## What you can test

- **CRUD:** All modules have records; create, read, update, delete can be exercised.
- **Dashboard statistics:** Businesses, facilities, intakes, slaughter plans, certificates, warehouse storages, trips, deliveries, demands, client activities.
- **Reports:** Filter by date, status, business, facility, species, client.
- **Status workflows:** Demand (draft → confirmed → in_progress → fulfilled; cancelled); Certificate (active/expired/revoked); WarehouseStorage (in_storage/released/disposed); TransportTrip (pending/in_transit/arrived/completed); DeliveryConfirmation (pending/confirmed/disputed).
- **Relationships:** Foreign keys and model relations (Business → Facility → Inspector; AnimalIntake → SlaughterPlan → … → Certificate → WarehouseStorage → TransportTrip → DeliveryConfirmation; Demand ↔ Client, Contract, Facility, DeliveryConfirmation; ClientActivity → Client, User).

---

## Fixes applied

1. **TestDataSeeder:** Added one **Inspector** to Dayare Meat’s Kigali Slaughterhouse so both businesses get slaughter plans and full chain data.
2. **TestDataSeeder:** Added **Employees** (Rwanda names, +250 phone, job titles), **Suppliers** (approved, Rwanda), **Clients** (butchery/restaurant, preferred_facility), **Contracts** (supplier + customer, RWF amounts).
3. **WarehouseStorageSeeder:** Set **quantity_unit** from `Unit` (code `kg`) so warehouse storages use configured units.
4. **TransportTripSeeder:** Use **Facility::TYPE_SLAUGHTERHOUSE** and **Facility::TYPE_BUTCHERY** instead of string literals.
5. **AnimalIntakeSeeder:** Optionally link **supplier_id** and **contract_id** when Supplier and Contract exist for the facility’s business.
6. **DeliveryConfirmationSeeder:** Optionally set **client_id** when the destination facility’s business has clients; set **receiver_country** to Rwanda.
7. **DemandSeeder** and **ClientActivitySeeder:** New seeders for demands (all statuses, fulfilled_by_delivery_id) and client activities (call, email, meeting, note).

---

## Default units

The **units** table is populated in the migration `2026_03_18_100000_create_units_table.php` with: kg, Heads, Seed, Other. No UnitSeeder is run; Demands and Warehouse storages use these codes (e.g. `kg`).
