# Demand Module – Design

**How a “Demand” module could look in DayareMeat.**

---

## 1. What it is

**Demand** = a request for product (meat) that you need to fulfill. It answers: *“Who wants what, how much, and by when?”*

- **Today:** You do Animal Intake → Slaughter → Batches → Transport → Delivery. Demand is implicit (you know your customers and send them product).
- **With Demand:** You record customer requests first (demands), then plan slaughter and deliveries to meet them. You can see open demand, due dates, and later link deliveries to demands.

So the Demand module sits **before or alongside** Slaughter Plan and **links forward** to Transport/Delivery.

---

## 2. How it fits the flow

```
[Demand]  "Butchery X wants 200 kg beef by 15 March"
       ↓
[Animal Intake] → [Slaughter Plan] → [Execution] → [Batches] → [Certificates] → [Warehouse]
       ↓
[Transport Trip] → [Delivery Confirmation]  (optional: link delivery to Demand → mark demand fulfilled)
```

- **Demand** = the “order” or request (destination, product type, quantity, date).
- **Slaughter / Batches / Transport / Delivery** = the execution. Later you can optionally link a Delivery (or Transport Trip) to a Demand to track “this delivery fulfilled demand #5.”

---

## 3. Data model (sketch)

### Table: `demands`

| Column | Type | Purpose |
|--------|------|--------|
| id | bigint PK | |
| business_id | FK businesses | Your business (tenant-scoped). |
| demand_number | string | Unique ref, e.g. DEM-2026-0001. |
| title | string | Short description (e.g. "March order – Butchery Kigali"). |
| destination_facility_id | FK facilities nullable | Who requested (a facility you deliver to). Null when demand is for an **external/international client**. |
| contract_id | FK contracts nullable | Optional link to a contract. |
| **International client (when no facility)** | | |
| client_name | string nullable | Contact person or company name (e.g. when you meet a client in another country). |
| client_company | string nullable | Company name if different from client_name. |
| client_country | string nullable | Country (e.g. Uganda, Kenya, DRC). |
| client_contact | string nullable | Phone or email. |
| client_address | text nullable | Address in that country. |
| species | string | Cattle, Goat, Sheep, Pig, Other (align with AnimalIntake). |
| product_description | string nullable | e.g. "Beef cuts", "Whole carcass". |
| quantity | decimal | Amount requested. |
| quantity_unit | string | kg, heads, etc. (default kg). |
| requested_delivery_date | date | When they want it. |
| status | string | draft, confirmed, in_progress, fulfilled, cancelled. |
| notes | text nullable | |
| created_at, updated_at | timestamps | |

**Optional later:**

- `delivery_confirmation_id` or a pivot `demand_fulfillments(demand_id, delivery_confirmation_id)` to link which delivery fulfilled (part of) this demand.
- `source_facility_id` (your facility that will supply – slaughterhouse/warehouse).

---

## 4. Relationships

- **Demand** belongs to: **Business**, **Facility** (destination, optional), **Contract** (optional).
- **Facility** hasMany **Demands** (as destination).
- **Contract** hasMany **Demands** (optional).
- Later: **Demand** hasMany **DeliveryConfirmations** or a pivot to track fulfillment.

---

## 5. Client in another country

When you **meet a client in another country**, they usually are not a Facility in your system. You still want to record their demand (what they want, quantity, date) and their details so you can follow up and plan export.

**Option A – Store on the Demand (recommended for v1)**  
Add nullable fields on `demands`:

- `client_name` – contact person or company name  
- `client_company` – company name if different  
- `client_country` – country (e.g. Uganda, Kenya, DRC)  
- `client_contact` – phone / email  
- `client_address` – address in that country  

**Behaviour:**

- **Destination type:** In the form, choose either:
  - **“Facility”** → select `destination_facility_id` (domestic or known facility), or  
  - **“External / International client”** → leave facility empty and fill client name, country, contact, address.
- In the list and show page: if there is a destination facility, show its name; otherwise show “*Client name* (Country)” or “*Company* – Country”.
- Export / logistics can use `client_*` for shipping and documents; Transport/Delivery might stay facility-based for domestic legs, with a note or separate process for international.

**Option B – Separate Customer/Client table (later)**  
If you have many international clients and want to reuse them (e.g. same client, multiple demands or contracts):

- Add a **Customer** (or **Client**) table: name, company, country, contact, address, business_id.
- Demand then has optional `customer_id` instead of (or in addition to) the inline `client_*` fields.
- Same idea: demand is either “for Facility X” or “for Customer Y (in another country)”.

For “I met a client in another country”, **Option A** is enough: one demand with client name, country, and contact. You can later refactor to Option B if you need a shared client list.

---

## 6. UI (how it could look)

### List (index)

- **Sidebar:** “Demand” → `/demands`.
- **Page:** Table with columns: Demand #, Title, **Destination** (facility name *or* “Client name (Country)” for international), Species, Quantity, Unit, Requested date, Status, Actions (View, Edit, Delete).
- Filters: Status, date range, destination facility; filter “International only” (where destination_facility_id is null).
- Button: “Add demand”.

### Create / Edit

- **Business** (dropdown, your businesses).
- **Demand number** (optional auto-generate, e.g. DEM-YYYYMMDD-XXX).
- **Title** (required).
- **Destination:**
  - **Option 1 – Facility:** dropdown of facilities you deliver to (your businesses’ facilities).  
  - **Option 2 – External / International client:** leave facility empty and fill:
    - Client name (or company name)
    - Company name (optional)
    - Country
    - Contact (phone / email)
    - Address (optional)
- **Contract** (optional dropdown).
- **Species** (same options as Animal Intake).
- **Product description** (optional text).
- **Quantity** + **Unit** (kg / heads).
- **Requested delivery date**.
- **Status** (draft, confirmed, etc.).
- **Notes**.

### Show

- All fields in a read-only layout.
- **Destination:** either “Facility: …” or “International client: *Name* – *Country*” with contact and address.
- Optional: “Deliveries” section listing transport/deliveries linked to this demand (when that link exists).
- Buttons: Edit, Delete, “Create transport trip” (prefill destination from demand when it’s a facility; for international, show a note or “Export / manual process”).

---

## 7. Scope and permissions

- Only show demands for **businesses the user belongs to** (same as Contracts, Suppliers).
- Destination facility: only facilities that belong to the user’s businesses. For **international clients**, no facility is required—just store client name, country, contact, address on the demand.

---

## 8. Implementation order (if you build it)

1. Migration: create `demands` table.
2. Model: `Demand` with fillable, casts, relations (business, destinationFacility, contract); include client_name, client_company, client_country, client_contact, client_address for international clients.
3. Controller: `DemandController` (index, create, store, show, edit, update, destroy), scoped by user’s businesses.
4. Form requests: StoreDemandRequest, UpdateDemandRequest.
5. Views: index, create, edit, show (same style as Contracts/Suppliers). On create/edit: “Destination = Facility” vs “External / International client” with client_* fields.
6. Routes: `Route::resource('demands', DemandController::class)`.
7. Sidebar: add “Demand” link.
8. Optional later: link Delivery to Demand (e.g. `delivery_confirmations.demand_id`) and show “Fulfilled by” on Demand show; or a pivot table for partial fulfillment.

---

## 9. Summary

| Aspect | Proposal |
|--------|----------|
| **Purpose** | Record customer/recipient requests (who wants what, how much, by when). |
| **Core fields** | Business, demand #, title, destination (facility *or* international client), species, quantity, unit, requested date, status. |
| **International client** | When no facility: client_name, client_company, client_country, client_contact, client_address (e.g. “I met a client in another country”). |
| **Optional** | Contract, product description, notes; later: link to deliveries. |
| **UI** | List (with filters), Create, Edit, Show; same layout pattern as Contracts. |
| **Flow** | Demand → (drives) Slaughter & Delivery; optionally link Delivery back to Demand for “fulfilled” tracking. |

If you want to implement it next, we can start with the migration and model, then add the controller and views step by step.
