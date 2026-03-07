# CRM Integration Plan – DayareMeat

**Senior-level analysis and implementation proposal.**

---

## 1. Current system summary

| Area | What exists today |
|------|-------------------|
| **Organizations** | **Business** (tenant-owned): name, registration, contact_phone, email, owner_*, address. **Facility** (per Business): name, type (slaughterhouse/butchery/storage), location, capacity. |
| **People as data** | **Inspector**: first_name, last_name, phone, email, address (tied to Facility). **BusinessOwnershipMember**: first_name, last_name, DOB (tied to Business). **Animal Intake “supplier”**: supplier_firstname, supplier_lastname, supplier_contact, farm_name, farm_registration_number (inline on each intake, not a reusable entity). **Delivery**: receiver_name (free text). **Transport**: driver_name, driver_phone (inline on trip). |
| **Flow** | Animal Intake (supplier/farm) → Slaughter Plan → Execution → Batches → Certificates → Warehouse → Transport Trip (origin/destination = Facilities) → Delivery Confirmation (receiving_facility_id + receiver_name). |
| **Gaps** | No unified **Contact** or **Customer** model. Suppliers repeated per intake; receivers and drivers are plain text or Facility-only. No shared address book or “customer” (buyer/recipient) beyond “destination Facility.” |

So: **Business** and **Facility** are the main “external party” entities. Recipients of meat are **Facilities** (and their Business). Suppliers and receivers are not first-class, reusable contacts.

---

## 2. What “CRM integration” should mean here

In this traceability context, CRM is best scoped as:

1. **Unified contacts** – One place to manage people and organizations that appear as suppliers, receivers, drivers, or general contacts linked to your businesses/facilities.
2. **Customer/recipient view** – Treat “who receives my meat” (destination Facility + optional contact person) as customers: view history (deliveries, trips), and optionally simple pipeline (e.g. lead → regular customer).
3. **Reuse across modules** – Same supplier contact linked to many intakes; same receiver contact for a facility; driver as contact for transport.
4. **Optional later** – Integrate with an external CRM (e.g. HubSpot, Zoho) via API for sync of contacts/companies.

Recommended focus for **first release**: (1) and (3) – **Contacts** that can be linked to Businesses/Facilities and used in Animal Intake, Delivery, and Transport. Then (2) – **Customer** as a thin layer (e.g. “recipient” = Facility + primary contact) with a simple list/detail and delivery history.

---

## 3. Proposed data model

### 3.1 Contact (new)

Single, reusable entity for “a person or place we interact with.”

| Column | Type | Purpose |
|--------|------|--------|
| id | bigint PK | |
| user_id | FK users | Tenant owner (contacts are per user). |
| type | enum | `supplier`, `receiver`, `driver`, `inspector`, `other` (or free-form label). |
| display_name | string | “Full name” or company name for list/search. |
| first_name | string nullable | |
| last_name | string nullable | |
| email | string nullable | |
| phone | string nullable | |
| company_name | string nullable | Farm, company, etc. |
| notes | text nullable | |
| is_active | boolean default true | Soft “removed” from dropdowns. |
| created_at, updated_at | timestamps | |

Optional: `business_id` nullable (link contact to a Business you own) and/or `facility_id` nullable (link to a Facility) for “contact of this site.”

**Use:**

- **Animal Intake**: optional `supplier_contact_id` FK → Contact. If set, intake form can prefill from Contact; otherwise keep current inline supplier fields (or migrate gradually).
- **DeliveryConfirmation**: optional `receiver_contact_id` FK → Contact; keep `receiver_name` for backward compatibility / one-off.
- **TransportTrip**: optional `driver_contact_id` FK → Contact; keep `driver_name`, `driver_phone` for backward compatibility.
- **Business**: optional “primary contact” later (e.g. `primary_contact_id`).

No change to **Inspector** model initially; inspectors remain a separate entity (they are regulatory). Later you could add `inspector_contact_id` on Inspector if you want one Contact record per inspector.

### 3.2 Customer (optional, thin layer)

“Customer” = someone who receives your product (meat). In your system that is already a **Facility** (destination) and its **Business**. So “Customer” can be:

- **Option A – No new table**: “Customers” = list of **Facilities** that have been **destination** of at least one TransportTrip (or DeliveryConfirmation). Add a “Customers” or “Recipients” view that queries these + their Business + last delivery. No new model.
- **Option B – Lightweight `customers` table**: `id`, `user_id`, `facility_id` (unique per user), `contact_id` nullable (primary contact at that facility), `status` (e.g. lead / active / inactive), `notes`, timestamps. So “Customer” = “I treat this Facility as a customer, with an optional primary contact.”

Recommendation: **Option A** for v1 (no new table); add Option B only if you need explicit status (lead/active) or extra fields per customer.

### 3.3 CRM “module” in the app

- **Contacts** – CRUD list of Contacts (filter by type, search by name/phone/email). From list: create, edit, delete, “View” (detail: where used – intakes, deliveries, trips).
- **Customers (recipients)** – One page: “Recipients” or “Customers” = Facilities that received deliveries (from DeliveryConfirmation or TransportTrip destination), with link to Facility and Business, last delivery date, total deliveries. Optional: link to a Contact as “primary contact” for that facility (if you add `contact_id` on Facility or use Option B).

No need for a full “Deals” or “Pipeline” in v1 unless you explicitly want lead → customer stages.

---

## 4. Implementation phases

### Phase 1 – Contacts (core)

1. **Migration**  
   - Create `contacts` table (user_id, type, display_name, first_name, last_name, email, phone, company_name, notes, is_active, timestamps).  
   - Add nullable FKs:  
     - `animal_intakes.supplier_contact_id` → contacts.id  
     - `delivery_confirmations.receiver_contact_id` → contacts.id  
     - `transport_trips.driver_contact_id` → contacts.id  

2. **Model**  
   - `Contact` (fillable, user scope, relationships: `user()`, optional `animalIntakes()`, `deliveryConfirmations()`, `transportTrips()`).  
   - On `AnimalIntake`, `DeliveryConfirmation`, `TransportTrip`: `contact()` (or `supplierContact()`, `receiverContact()`, `driverContact()`).  

3. **Controller + routes**  
   - `ContactController`: index, create, store, show, edit, update, destroy.  
   - Routes under `auth`, `tenant`: `Route::resource('contacts', ContactController::class);`  

4. **UI**  
   - **Settings** (or new “CRM” section): add **“Contacts”** (and optionally “Species”-style sub-item or card that links to `/contacts`).  
   - List contacts (table: type, display_name, phone, email, company_name; filters by type; search).  
   - Create/Edit form: type, first_name, last_name, display_name, email, phone, company_name, notes, is_active.  
   - Show: contact details + “Used in” (list of intakes / deliveries / trips that reference this contact).  

5. **Use in existing modules**  
   - **Animal Intake (create/edit)**  
     - Optional “Select existing supplier” dropdown (Contacts with type `supplier`). On select, fill name/contact/company from Contact; store `supplier_contact_id`.  
     - Keep existing supplier_* fields so intakes without a linked contact still work.  
   - **Delivery (create/edit)**  
     - Optional “Receiver contact” dropdown (Contacts with type `receiver`). Store `receiver_contact_id`; optionally prefill `receiver_name` from contact.  
   - **Transport Trip (create/edit)**  
     - Optional “Driver” dropdown (Contacts with type `driver`). Store `driver_contact_id`; prefill driver_name, driver_phone from Contact.  

6. **Validation**  
   - In Store/Update requests: if `supplier_contact_id` / `receiver_contact_id` / `driver_contact_id` is present, validate `exists:contacts,id` and that the contact belongs to current user.  

7. **Seed**  
   - No need to seed contacts; user creates them. Optional: seeder that creates 1–2 sample contacts per type for dev.  

### Phase 2 – “Customers” (recipients) view

1. **No new table**  
   - Add a **Recipients** or **Customers** page: query Facilities that appear as `destination_facility_id` in TransportTrip or `receiving_facility_id` in DeliveryConfirmation (scoped to facilities of the current user’s businesses).  
   - Show: facility name, business name, last delivery date, count of deliveries/trips.  
   - Link to Facility show and to related DeliveryConfirmations / TransportTrips.  

2. **Optional**  
   - Add `facility.primary_contact_id` → contacts.id so “Customers” page can show “Primary contact: X” and link to Contact.  

### Phase 3 – Optional enhancements

- **Activities** – Table `contact_activities` (contact_id, type: call/email/meeting, subject, notes, occurred_at, user_id). Simple log on Contact show page.  
- **External CRM** – If you later integrate with HubSpot/Zoho: map Contact to external “Contact” or “Company”; sync key fields (name, email, phone) via their API; store `external_id` on `contacts`.  

---

## 5. Where CRM appears in the UI

- **Sidebar**  
  - Keep **Settings** as one item.  
  - Under Settings (on the **Settings** page): add a card/section **“Contacts”** with link “Manage contacts” → `/contacts`.  
  - Optionally add a second card **“Customers (recipients)”** → `/customers` (or `/recipients`).  

- **No separate “CRM” top-level menu** unless you add many more features (Deals, Activities, Reports). For now, “CRM” = **Contacts** + **Recipients**, both reachable from **Settings**.  

- **Existing modules**  
  - Animal Intake create/edit: “Supplier” section with “Use existing contact” dropdown + current fields.  
  - Delivery create/edit: “Receiver contact” dropdown + receiver_name.  
  - Transport Trip create/edit: “Driver” dropdown + driver_name, driver_phone.  

---

## 6. Technical notes

- **Tenant scope** – All contacts (and customers view) must be scoped by `user_id` (current user).  
- **Backward compatibility** – All new FKs nullable. Existing intakes/deliveries/trips keep working without a linked contact.  
- **Display name** – Compute `display_name` from `first_name` + `last_name` if not set, or allow user to set a custom display_name (e.g. “Farm Gikondo”).  
- **Contact type** – Use enum or string; in dropdowns filter by type (e.g. Animal Intake supplier dropdown: only type `supplier`).  

---

## 7. Suggested implementation order

1. Migration: `contacts` table + nullable FKs on animal_intakes, delivery_confirmations, transport_trips.  
2. Model `Contact` + relationships on AnimalIntake, DeliveryConfirmation, TransportTrip.  
3. `ContactController` + resource routes; list/create/edit/show/delete.  
4. Settings page: add “Contacts” card linking to `/contacts`.  
5. Contacts index/create/edit/show views (reuse your existing layout and Tailwind).  
6. Animal Intake form: optional supplier contact dropdown + fill from contact.  
7. Delivery form: optional receiver contact dropdown.  
8. Transport Trip form: optional driver contact dropdown.  
9. “Recipients” / “Customers” page (query facilities that received deliveries).  
10. Optional: `primary_contact_id` on facilities and show on Recipients page.  

This gives you **default species**-style “general” data (contacts you create) and **user-managed** list (add/edit/remove contacts), with minimal change to existing traceability flow and no external dependency for v1.
