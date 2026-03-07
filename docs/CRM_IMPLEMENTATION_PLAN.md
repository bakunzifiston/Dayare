# CRM Implementation Plan – How We Are Going to Implement It

**Role: Senior software developer.**  
This document is the **actionable implementation plan** for the CRM scope defined in `CRM_IMPLEMENTATION_SUGGESTION.md`. It assumes the existing codebase (Laravel, existing models, sidebar, auth).

---

## 1. Current state (what’s already done)

| Component | Status | Notes |
|-----------|--------|--------|
| **Clients** | Done | Full CRUD, `Client` model, delivery confirmations on show, `demands()` relation. |
| **Demand** | Done | Migration, model, controller, form requests, index/create/edit/show, link to Client & Facility, fulfillability + compliance. Client show lists demands. |
| **Delivery confirmations** | Done | Linked to Client (external) or Facility (receiving_facility_id). |
| **Contracts** | Done | Supplier/employee contracts; optional link from Demand (contract_id). |
| **Sidebar** | Done | Clients, Demand under CRM/HR area. |

**Still to build for “full CRM story”:**

1. **Recipients view** – “Who have we delivered to?” (facilities that received product).
2. **Optional: link Delivery → Demand** – Mark which delivery fulfilled which demand.
3. **Optional: CRM dashboard** – KPIs + recent clients + open demands.
4. **Optional: Client activities** – Log calls, emails, meetings per client.

---

## 2. Implementation approach (high level)

- **No new “Customer” or “Contact” table** for now. Customers = **Clients** (external) + **Facilities** as recipients (domestic).  
- **Recipients page** = read-only view over existing data (DeliveryConfirmation + TransportTrip); no new DB table.  
- **Delivery ↔ Demand link** = one nullable FK (e.g. `demands.fulfilled_by_delivery_id`) to keep the model simple; we can add a pivot later if we need partial fulfillment.  
- **Activities** = one new table `client_activities` scoped to clients only; we can generalize to Facility/Supplier later if needed.  
- All **scoped by user’s businesses** (same pattern as Clients, Demands, Facilities).

---

## 3. Phase 2A – Recipients view (mandatory for CRM)

**Goal:** One page that answers “Who have we delivered to?” with facilities that received product, plus last delivery date and count.

### 3.1 Data source

- **Facilities** that appear as:
  - `receiving_facility_id` in **DeliveryConfirmation**, or
  - `destination_facility_id` in **TransportTrip**
- Restrict to facilities that belong to the **current user’s businesses** (so we only see “our” recipient facilities).

We do **not** list Clients here; Clients already have their own list and Client show with delivery history. This page is “recipient facilities” only.

### 3.2 Backend

| Step | Action |
|------|--------|
| 1 | **Route** – Add `Route::get('recipients', [RecipientController::class, 'index'])->name('recipients.index');` inside the `auth` + `tenant` middleware group (e.g. next to `demands`). |
| 2 | **Controller** – Create `App\Http\Controllers\RecipientController`. Single method `index(Request $request)`. |
| 3 | **Query** – Get facility IDs that belong to user’s businesses: `Facility::whereIn('business_id', $userBusinessIds)->pluck('id')`. Then: |
| 3a | From **DeliveryConfirmation**: `whereIn('receiving_facility_id', $facilityIds)` → select `receiving_facility_id`, count, max(received_date). |
| 3b | From **TransportTrip**: `whereIn('destination_facility_id', $facilityIds)` → same idea for destination_facility_id. |
| 3c | Merge/union: for each facility_id, we want **last_delivery_date** (max of received_date from DC and arrival_date from TT) and **delivery_count** (count of DC rows + count of TT rows, or distinct deliveries by trip; definition: “number of delivery confirmations where this facility is receiver” plus “number of transport trips where this facility is destination” – we can define as “delivery confirmations count” only if every trip has at most one confirmation). Simplest: **last_delivery_date** = max(received_date) from DeliveryConfirmations where receiving_facility_id = facility_id; **delivery_count** = count of DeliveryConfirmations where receiving_facility_id = facility_id. Optionally add “trips to this facility” from TransportTrip in a second column. |
| 4 | **Response** – Return a collection (or paginated) of: `facility_id`, `facility`, `business`, `last_delivery_date`, `delivery_count`. Eager load `facility.business` to avoid N+1. |

**Suggested query shape (RecipientController):**

```php
$facilityIds = Facility::whereIn('business_id', $userBusinessIds)->pluck('id');

$recipients = DeliveryConfirmation::query()
    ->whereNotNull('receiving_facility_id')
    ->whereIn('receiving_facility_id', $facilityIds)
    ->selectRaw('receiving_facility_id as facility_id, max(received_date) as last_delivery_date, count(*) as delivery_count')
    ->groupBy('receiving_facility_id')
    ->get();

$facilityIdsWithDeliveries = $recipients->pluck('facility_id')->unique();
$facilities = Facility::with('business')->whereIn('id', $facilityIdsWithDeliveries)->get()->keyBy('id');

// Pass to view: $recipients (with last_delivery_date, delivery_count), $facilities map.
```

(We can do the same for TransportTrip if we want “trips to facility” as well; for v1 the doc says “deliveries” so DeliveryConfirmation is enough.)

### 3.3 Frontend

| Step | Action |
|------|--------|
| 1 | **View** – Create `resources/views/recipients/index.blade.php`. Table: Facility name, Business, Last delivery date, Delivery count, Link to facility show (e.g. `route('businesses.facilities.show', [$facility->business, $facility])`), optional “Deliveries” link to filtered delivery-confirmations list (e.g. `?receiving_facility_id=X`). Reuse layout and table styling from `demands/index` or `clients/index`. |
| 2 | **Sidebar** – Add one entry: “Recipients” → `recipients.index`, icon e.g. truck or map-pin, next to “Demand” under CRM. |

### 3.4 Acceptance

- User opens “Recipients” and sees only facilities (from their businesses) that have at least one DeliveryConfirmation as receiver.
- Columns: facility name, business, last delivery date, delivery count, links to facility and (if we add it) to list of deliveries for that facility.

---

## 4. Phase 2B – Link Delivery → Demand (optional)

**Goal:** Record which delivery “fulfilled” a demand so we can show “Fulfilled by delivery on …” on Demand and “Fulfills demand #X” on Delivery.

### 4.1 Data model

- Add nullable **`fulfilled_by_delivery_id`** (FK to `delivery_confirmations.id`) on **`demands`**.
- One demand → at most one “fulfilling” delivery (1:1). For partial fulfillment we could add a pivot later.

### 4.2 Backend

| Step | Action |
|------|--------|
| 1 | **Migration** – `demands`: `$table->foreignId('fulfilled_by_delivery_id')->nullable()->constrained('delivery_confirmations')->nullOnDelete();` |
| 2 | **Model** – `Demand`: fillable `fulfilled_by_delivery_id`; `belongsTo(DeliveryConfirmation::class, 'fulfilled_by_delivery_id')`. **DeliveryConfirmation**: `hasOne(Demand::class, 'fulfilled_by_delivery_id')` (the demand that this delivery fulfills). |
| 3 | **Validation** – When setting `fulfilled_by_delivery_id` (e.g. on Demand update or dedicated “Mark fulfilled by delivery” action), ensure the delivery’s client_id or receiving_facility_id is consistent with the demand’s client_id / destination_facility_id (optional but recommended). |
| 4 | **Controller** – DemandController: on **edit** (or a dedicated action) allow selecting a delivery to “fulfill” this demand (list deliveries for same client/facility). Update demand: set `fulfilled_by_delivery_id`, set `status = fulfilled`. DeliveryConfirmationController: no change except show view. |

### 4.3 Frontend

| Step | Action |
|------|--------|
| 1 | **Demand show** – If `demand->fulfilled_by_delivery_id` set: show “Fulfilled by delivery on {{ received_date }}” with link to `delivery-confirmations.show`. |
| 2 | **Demand edit** – Optional “Fulfill by delivery” block: dropdown of deliveries (filtered by client/facility) and “Mark as fulfilled” button that sets `fulfilled_by_delivery_id` and status. |
| 3 | **Delivery show** – If this delivery is linked from a demand: “Fulfills demand {{ demand_number }}” with link to `demands.show`. |

---

## 5. Phase 3A – CRM dashboard (optional)

**Goal:** One page “CRM” or “Sales” with KPIs and short lists.

### 5.1 Backend

| Step | Action |
|------|--------|
| 1 | **Route** – e.g. `Route::get('crm', [CrmDashboardController::class, 'index'])->name('crm.dashboard');` |
| 2 | **Controller** – `CrmDashboardController@index`: (1) total clients (user’s businesses), (2) open demands count (status in draft, confirmed, in_progress), (3) deliveries this month (DeliveryConfirmation where received_date in current month), (4) recent 5–10 clients (order by updated_at or id), (5) open demands (same statuses, limit 10, with client/facility). Return compact for view. |

### 5.2 Frontend

| Step | Action |
|------|--------|
| 1 | **View** – `resources/views/crm/dashboard.blade.php`. Cards: Total clients, Open demands, Deliveries this month. Two small tables or lists: “Recent clients” (name, link to show), “Open demands” (demand #, title, destination, link to show). Links to clients.index, demands.index, recipients.index. |
| 2 | **Sidebar** – Add “CRM” or “Sales” that goes to `crm.dashboard` (optional: make it a parent and keep Clients, Demand, Recipients as children, or keep flat and add “CRM dashboard” as first CRM item). |

---

## 6. Phase 3B – Client activities (optional)

**Goal:** Log calls, emails, meetings per client and show them on Client show.

### 6.1 Data model

- New table **`client_activities`**:
  - id, business_id (FK), client_id (FK), activity_type (string: call, email, meeting, note), subject (string nullable), notes (text nullable), occurred_at (datetime), user_id (FK), timestamps.

### 6.2 Backend

| Step | Action |
|------|--------|
| 1 | **Migration** – create_client_activities_table. |
| 2 | **Model** – `ClientActivity` with relations: business, client, user. Scoped by business (and client belongs to business). |
| 3 | **Controller** – `ClientActivityController` or inline in `ClientController`: store (validate client_id belongs to user’s business, activity_type in list), destroy. List: on Client show, so load `client->activities()` in ClientController@show. |
| 4 | **Routes** – e.g. `POST clients/{client}/activities` store, `DELETE client-activities/{activity}` destroy (with policy or check client.business_id in user’s businesses). |

### 6.3 Frontend

| Step | Action |
|------|--------|
| 1 | **Client show** – Section “Activities”. List activities (date, type, subject, notes, user). Form (or modal): “Log activity” – type dropdown, subject, notes, occurred_at; submit to store. |
| 2 | **No global “Activities” list required for v1** – activities are only per client. |

---

## 7. Suggested order of implementation

| Order | Item | Effort | Status |
|-------|------|--------|--------|
| 1 | **Recipients view** (Phase 2A) | Small | ✅ Done |
| 2 | **Link Delivery → Demand** (Phase 2B) | Small | ✅ Done |
| 3 | **CRM dashboard** (Phase 3A) | Small | ✅ Done |
| 4 | **Client activities** (Phase 3B) | Medium | ✅ Done |

Full CRM implementation is complete: Recipients, Delivery↔Demand link, CRM dashboard, and Client activities.

---

## 8. File checklist (quick reference) – implemented

- **Phase 2A:** `RecipientController.php`, `recipients/index.blade.php`, route `recipients.index`, sidebar; delivery confirmations index filter by `receiving_facility_id`.
- **Phase 2B:** Migration `fulfilled_by_delivery_id` on demands, `Demand::fulfilledByDelivery()`, `DeliveryConfirmation::fulfillingDemand()`, Demand show/edit, Delivery show.
- **Phase 3A:** `CrmDashboardController.php`, `crm/dashboard.blade.php`, route `crm.dashboard`, sidebar “CRM”.
- **Phase 3B:** Migration `client_activities`, `ClientActivity` model, `StoreClientActivityRequest`, `ClientActivityController::destroy`, `ClientController::storeActivity`, Client show “Activities” section with form and list.

---

## 9. Summary

- **Recipients** = read-only view over facilities that received deliveries (from DeliveryConfirmation); no new table; one controller + one view + route + sidebar.
- **Delivery → Demand** = one nullable FK on demands; optional; improves traceability “this delivery fulfilled that order.”
- **CRM dashboard** = one page with KPIs and links to Clients, Demand, Recipients.
- **Client activities** = one new table and per-client log of calls/emails/meetings/notes.

This plan keeps the CRM aligned with the existing structure (Clients, Demand, DeliveryConfirmations, Facilities) and delivers a clear “Customer 360” and “who did we deliver to” without introducing a generic Contact entity until we need it.
