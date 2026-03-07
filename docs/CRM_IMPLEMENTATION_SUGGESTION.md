# CRM Implementation Suggestion – DayareMeat

**How to implement CRM based on the current system structure.**

---

## 1. What you already have (CRM‑relevant)

| Module | Role in CRM |
|--------|-------------|
| **Clients** | Your main “customer” entity: buyers, distributors, international clients. Full profile (name, contact, address, country). Linked to **DeliveryConfirmations**. |
| **Delivery confirmations** | Proof of delivery; can link to **Client** (external) or **Facility** (domestic). History = “what we sent to whom.” |
| **Contracts** | **Supplier contracts** (authorized suppliers) and **Employee contracts**. Optional link from **Demand** and from relationship view. |
| **Facilities** | Your sites + **destination facilities** (who receives product). “Recipients” = facilities that received deliveries. |
| **Suppliers** | First‑class entity; linked to intakes and supplier contracts. Not “CRM customers” but part of partner management. |
| **Transport trips** | Origin → destination; tie to certificates and delivery confirmations. |

So today you have **customers** (Clients + Facilities as recipients) and **delivery history**. What’s missing for a clear “CRM” story is: **demand/orders**, **one place to see the full customer picture**, and optionally **activities** (calls, meetings, follow‑ups).

---

## 2. Recommended CRM scope (aligned with your structure)

Treat CRM as:

1. **Customer 360** – One place to see each “customer” (Client or Facility as recipient) with:
   - Profile (already have for Client; Facility from existing model).
   - **Demands** (orders/requests) – when you add the Demand module.
   - **Delivery history** (already: DeliveryConfirmations linked to Client; for Facility, deliveries where it’s receiving_facility or destination).
   - Optional: **Activities** (calls, emails, meetings) for follow‑up.

2. **Demand (orders/requests)** – “Who wants what, by when?”  
   - Implement **Demand** from `DEMAND_MODULE_DESIGN.md` with **client_id** (optional) and **destination_facility_id** (optional).  
   - Demand links to **Client** (international/external) or **Facility** (domestic recipient).  
   - Later: link **DeliveryConfirmation** to Demand to mark “this delivery fulfilled this demand.”

3. **Recipients view** – “Who have we delivered to?”  
   - **By Client:** Already on Client show (list of delivery confirmations).  
   - **By Facility:** One page listing Facilities that appear as destination/receiving in trips/deliveries, with last delivery date and count (no new table; query + optional cache).

4. **Optional: Contact (unified person)**  
   - Only if you need one reusable “person” across roles (e.g. same person as supplier contact and driver).  
   - Otherwise: **Supplier** = supplier contact, **Employee** = staff, **Client** = customer contact; keep it simple.

---

## 3. Data model (minimal additions)

### 3.1 Reuse and extend existing

- **Client** – Already the main CRM “customer” for external/buyers. Keep as is; add relations to **Demand** when implemented.
- **Facility** – Represents “recipient” when delivery is to a registered facility. No new table; use for “Recipients” view.
- **DeliveryConfirmation** – Already has `client_id` and `receiving_facility_id`. Optional later: `demand_id` (or pivot) to link delivery → demand.

### 3.2 Add: Demand (from DEMAND_MODULE_DESIGN)

| Column | Type | Purpose |
|--------|------|--------|
| id | bigint PK | |
| business_id | FK businesses | |
| demand_number | string | e.g. DEM-2026-0001 |
| title | string | |
| **destination_facility_id** | FK facilities nullable | Domestic recipient (your facility or partner). |
| **client_id** | FK clients nullable | External/international customer (use existing Client). |
| species | string | Cattle, Goat, Sheep, Pig, Other |
| product_description | string nullable | |
| quantity | decimal | |
| quantity_unit | string | kg, heads (default kg) |
| requested_delivery_date | date | |
| status | string | draft, confirmed, in_progress, fulfilled, cancelled |
| notes | text nullable | |
| timestamps | | |

**Behaviour:**  
- Either **destination_facility_id** (domestic) or **client_id** (external), or both nullable for draft.  
- Demand **belongsTo** Business, Facility (destination), Client.  
- **Client** hasMany Demands; **Facility** hasMany Demands (as destination).

### 3.3 Optional: link Delivery to Demand

- Add **delivery_confirmation_id** nullable on `demands` (one demand → one “fulfilling” delivery), **or**
- Pivot **demand_fulfillments** (demand_id, delivery_confirmation_id, quantity_fulfilled) for partial fulfillment.

Start with nullable `demands.fulfilled_by_delivery_id` (or `delivery_confirmation_id`) for simplicity.

### 3.4 Optional: Activities (for follow‑up)

If you want “logged a call with this client”:

| Column | Type | Purpose |
|--------|------|--------|
| id | bigint PK | |
| business_id | FK businesses | |
| contact_type | string | 'client', 'facility', 'supplier' |
| contact_id | bigint | Polymorphic: client_id, facility_id, or supplier_id (or use one table per type). |
| activity_type | string | call, email, meeting, note |
| subject | string nullable | |
| notes | text nullable | |
| occurred_at | datetime | |
| user_id | FK users | Who logged it. |
| timestamps | | |

Simpler alternative: **client_activities** (client_id, activity_type, subject, notes, occurred_at, user_id) only for Clients; extend later if needed.

---

## 4. Where CRM “lives” in the app

### 4.1 Sidebar / navigation

- **Clients** – Already under your “CRM / HR” area. Keep as main customer list.
- **Demand** – New item “Demand” or “Orders” → `/demands` (when implemented).
- **Recipients** – One page “Recipients” or “Delivery history by recipient” → e.g. `/recipients` (facilities that received + optional filter by client).

So CRM = **Clients** + **Demand** + **Recipients** (and optionally **Activities**).

### 4.2 CRM “dashboard” (optional)

One page under “CRM” or “Sales” with:

- KPIs: total clients, open demands (count by status), deliveries this month.
- List: “Recent clients” (last 5), “Open demands” (e.g. draft + confirmed + in_progress).
- Links to Clients, Demand, Recipients.

### 4.3 Client show page (enhancement)

You already show **Delivery confirmations** for a client. Add:

- **Demands** – List demands where `client_id = this client` (when Demand exists).
- Optional: **Activities** – List of calls/emails/meetings for this client.

### 4.4 Recipients page (new)

- **Data:** Facilities that appear as:
  - `receiving_facility_id` in DeliveryConfirmation, or
  - `destination_facility_id` in TransportTrip  
  (scoped to facilities of the user’s businesses).
- **Columns:** Facility name, Business, Last delivery date, Number of deliveries, Link to facility show, Link to list of deliveries for that facility.

No new table; one controller method that runs this query (and optionally caches).

---

## 5. Implementation phases

### Phase 1 – Demand module (core “order” for CRM)

1. **Migration** – Create `demands` with business_id, demand_number, title, destination_facility_id, **client_id**, species, product_description, quantity, quantity_unit, requested_delivery_date, status, notes.
2. **Model** – `Demand` with relations: business, destinationFacility, client. Scoped by user’s businesses.
3. **Controller + routes** – `DemandController` (index, create, store, show, edit, update, destroy). `Route::resource('demands', DemandController::class)`.
4. **Form requests** – StoreDemandRequest, UpdateDemandRequest (validate client_id or destination_facility_id when not draft).
5. **Views** – index (table + filters), create, edit, show. On create/edit: “Destination = Facility” (dropdown) or “Client” (dropdown from your Clients). Reuse style from Contracts/Clients.
6. **Client model** – Add `demands()` HasMany. Client show: add “Demands” section.
7. **Sidebar** – Add “Demand” (or “Orders”) link.

This gives you **Demand** as the “order/request” that links to **Client** or **Facility**, and fits `DEMAND_MODULE_DESIGN.md` using your existing **Client** entity.

### Phase 2 – Recipients view + optional link Delivery → Demand

1. **Recipients page** – New route `recipients` (or `customers/recipients`). Controller: query facilities that received deliveries (from DeliveryConfirmation + TransportTrip), aggregate last_delivery_date, delivery_count. View: table with facility, business, last delivery, count, links.
2. **Optional:** Add `demand_id` or `fulfilled_by_delivery_id` on demands; on DeliveryConfirmation show “Fulfills demand #X” when set. On Demand show “Fulfilled by delivery on …”.

### Phase 3 – CRM dashboard + optional Activities

1. **Dashboard** – New view `crm.dashboard` or extend main dashboard: cards for “Clients”, “Open demands”, “Deliveries this month”; lists for recent clients and open demands.
2. **Activities (optional)** – If you want to log calls/emails:
   - Migration: `client_activities` (client_id, activity_type, subject, notes, occurred_at, user_id).
   - Model `ClientActivity`, relation on Client.
   - Simple form on Client show (or modal): “Log activity”; list on Client show.
   - No need for a global “Contacts” table if you only care about client follow‑up.

---

## 6. Relationship diagram (CRM view)

```
Business
  ├── Clients (customers / buyers)
  │     ├── DeliveryConfirmations (what we sent them)
  │     └── Demands (what they asked for)  [add]
  ├── Facilities
  │     ├── as destination → TransportTrip / DeliveryConfirmation
  │     └── Demands (destination_facility_id)  [add]
  ├── Contracts (supplier / employee)
  └── …

DeliveryConfirmation
  ├── client_id → Client (optional)
  ├── receiving_facility_id → Facility (optional)
  └── [optional later] demand_id → Demand (fulfillment)

Demand
  ├── client_id → Client (optional)
  ├── destination_facility_id → Facility (optional)
  └── [optional] fulfilled_by_delivery_id → DeliveryConfirmation
```

---

## 7. Summary table

| CRM need | Use existing | Add / do |
|----------|--------------|----------|
| **Customer list** | **Clients** (full profile) | — |
| **Customer delivery history** | **DeliveryConfirmations** (already on Client show) | — |
| **Orders / requests** | — | **Demand** (with client_id + destination_facility_id) |
| **“Who did we deliver to?”** | Facilities + DeliveryConfirmations | **Recipients** page (query, no new table) |
| **Link delivery → order** | — | Optional: demand_id or fulfilled_by on Demand |
| **Follow‑up (calls, etc.)** | — | Optional: **ClientActivity** (or generic Contact + activities later) |
| **Unified “Contact” (person)** | Supplier, Employee, Client cover most roles | Add **Contact** only if you need one person in multiple roles (e.g. driver + supplier contact) |

---

## 8. Suggested implementation order

1. **Demand module** – Migration, model, controller, requests, views (index, create, edit, show); link to Client and Facility; add to Client show; sidebar “Demand”.
2. **Recipients page** – Route, controller (query facilities + delivery stats), view (table with links).
3. **Optional:** Link DeliveryConfirmation to Demand (fulfillment); show on Demand and Delivery show.
4. **Optional:** CRM dashboard (KPIs + recent clients + open demands).
5. **Optional:** Client activities (table + form + list on Client show).

This keeps CRM consistent with your current structure: **Clients** as customers, **Demand** as orders, **DeliveryConfirmations** as proof of delivery, and **Recipients** as a view over facilities that received product.
