# CRM Module – SafeMeat Spec Gap Analysis & Improvement Plan

**Role: Senior software developer.**  
This document compares the **CRM Module Specification for SafeMeat Platform** (provided) with the **current DayareMeat implementation** and sets out where to improve.

**Phase A (quick wins) has been implemented:** Supplier status (approved/suspended/blacklisted) with enforcement on animal intake; Client business_type, preferred_facility_id, preferred_species; Contract categories customer/transport and client_id, Delivery contract_id and active-client enforcement; ClientActivity type “Inspection Notice”. See summary table and Phase A section for details.

---

## 1. Spec vs current state – entity mapping

| Spec entity | Current implementation | Gap level |
|-------------|------------------------|-----------|
| **Organizations** | None. Business + Facility exist but are not generic “organizations” (farm, retail, transport, distributor). | **Missing** |
| **Contacts** | None. Contact data is embedded in Client (contact_person), Supplier (first/last name), TransportTrip (driver_name). No reusable Contact linked to Organization. | **Missing** |
| **Suppliers** | **Supplier** model exists; linked to AnimalIntake and Contract. Structure is person/farm-oriented (first_name, last_name, type), not “Supplier Organization + Contact”. Missing spec fields (species supplied, capacity, health cert status, Approved/Suspended/Blacklisted). | **Partial** |
| **Customers** | **Client** model used as customer; linked to Demand and Delivery. Missing: organization_id, business_type, preferred_facility, preferred_species. No “Customer” as distinct from a generic organization. | **Partial** |
| **Transport Companies** | None. TransportTrip has driver_name, vehicle_plate_number only; no Transport Company entity or FK. | **Missing** |
| **Contracts** | **Contract** exists: supplier + employee categories; has facility_id. Missing: customer contract, transport contract, contract document upload. No organization_id (spec: “Organization ID (supplier/customer/transport company)”). | **Partial** |
| **Communication Logs** | **ClientActivity** exists (call, email, meeting, note; contact_id → client_id). Spec wants “Contact ID” and type “Inspection Notice”; currently client-scoped only. | **Partial** |

---

## 2. Detailed gaps by spec section

### 2.1 Organizations (Spec §3)

**Spec requires:** Organization name, type (Farm, Retail, Transport, Distributor, etc.), registration number, tax ID, phone, email, website, country, province, district, sector, address, status, notes.

**Current:** No `organizations` table. **Business** = tenant; **Facility** = our sites (slaughterhouse, butchery, storage). External “organizations” (farms, retailers, transport companies) are not first-class; their data is split across Supplier, Client, and (missing) Transport Company.

**Improvement:** Introduce an **Organization** model (and table) as the single “company or entity” in the supply chain. Types: Farm, Retail, Transport, Distributor, Slaughterhouse, Butchery, Other. Then: Supplier → organization_id (optional migration path), Customer → organization_id, Transport Company → organization_id. Facility can stay as “our” operational site; optionally link Facility to Organization later if you need to represent external facilities as organizations.

---

### 2.2 Contacts (Spec §4)

**Spec requires:** First name, last name, phone, email, job title, organization_id, role (Supplier Contact / Buyer Contact / Driver / Manager), preferred communication method, status, notes.

**Current:** No `contacts` table. Client has `contact_person` (string); Supplier has `first_name`, `last_name`, phone, email; TransportTrip has `driver_name`, `driver_phone`. No reuse: the same person cannot be “supplier contact” and “driver” in one place.

**Improvement:** Introduce a **Contact** model linked to **Organization** (contact belongs to one organization). Roles: supplier_contact, buyer_contact, driver, manager. Then: Supplier can have `primary_contact_id` (optional); Client/Customer can have `primary_contact_id`; TransportTrip can reference `contact_id` for driver. Communication logs (see below) reference Contact.

---

### 2.3 Suppliers (Spec §5)

**Spec requires:** Supplier Organization ID, farm name, farm registration number, farm owner name, supplier contact person, phone, email, district/sector of origin, species supplied, estimated supply capacity, animal health certification status, supplier status (Approved / Suspended / Blacklisted). Relations: Supplier → Animal Intake, Supplier Contract. Only approved suppliers for intake.

**Current:** Supplier has business_id, first/last name, DOB, nationality, type (farm etc.), address, is_active. No: organization_id, farm_name/farm_registration_number as distinct fields (Contract has farm_name etc.), species supplied, capacity, health cert status, Approved/Suspended/Blacklisted (only is_active). Animal Intake uses supplier_id and contract_id; SlaughterPlan validates contract and health cert – but there is no explicit “only approved suppliers” on the Supplier model itself.

**Improvement:**

- Add **supplier_status**: `approved` | `suspended` | `blacklisted` (default approved for existing). Enforce in Animal Intake (and SlaughterPlan): only allow supplier when `supplier->supplier_status === 'approved'` (and optionally active contract).
- Add fields to Supplier (or to linked Organization/Contract as appropriate): farm_name, farm_registration_number, district/sector of origin, species_supplied (JSON or table), estimated_supply_capacity, animal_health_certification_status.
- Optional: link Supplier to Organization (organization_id) and Contact (primary_contact_id) when those entities exist.

---

### 2.4 Customers (Spec §6)

**Spec requires:** Customer Organization ID, business name, contact person, phone, email, location (district/sector), business type (Butchery/Restaurant/Distributor), preferred facility, preferred species, customer status. Relations: Customer → Demand, Delivery, Customer Contracts.

**Current:** **Client** is used as customer: name, contact_person, email, phone, country, address, tax_id, registration_number, is_active. Linked to Demand and Delivery. Missing: organization_id, business_type, preferred_facility_id, preferred_species, and a dedicated “Customer” view (e.g. customer_type, or rename/clarify Client as “Customer” in UI). No customer-specific contract type (see Contracts).

**Improvement:**

- Add to Client (or to a new Customer model if you want Customer ≠ Client): **business_type** (butchery, restaurant, distributor, other), **preferred_facility_id** (FK to facilities), **preferred_species** (string or JSON). Add **district/sector** (or reuse address fields / administrative divisions).
- Introduce **Customer Contract** (contract_category = customer) and link Contract to Client (e.g. client_id) and Delivery (delivery_confirmation.contract_id or similar) so “Customer → Customer Contracts” and “Customer Contract → Delivery” are explicit.
- Optional: link Client to Organization and Contact when those exist.

---

### 2.5 Transport Companies (Spec §7)

**Spec requires:** Transport company name, registration number, contact person, phone, email, fleet size, vehicle types, cold chain capability, operating regions, status. Relations: Transport Company → Transport Trips, Transport Contracts.

**Current:** No transport company entity. **TransportTrip** has certificate_id, origin/destination_facility_id, vehicle_plate_number, driver_name, driver_phone. No company-level record or FK.

**Improvement:**

- Introduce **TransportCompany** model (business_id, name, registration_number, contact_person, phone, email, fleet_size, vehicle_types, cold_chain_capable, operating_regions, status). Optional: link to Organization and Contact.
- Add **transport_company_id** to **TransportTrip**. Keep driver_name/driver_phone for ad-hoc or override; optionally set driver from Contact (contact_id) when available.
- Introduce **Transport Contract** (contract_category = transport, transport_company_id) and link Contract to TransportTrip (e.g. transport_trip.contract_id or transport_company_id used for validation).

---

### 2.6 Contracts (Spec §8)

**Spec requires:** Contract number, name, type, organization ID, start/end date, status (Draft/Active/Expired/Terminated), description, document upload. Types: Supplier, Employee, Transport, Customer. Optional: renewal date, payment terms, quantity commitment. Relations: Supplier Contract → Animal Intake; Transport Contract → Transport Trip; Customer Contract → Delivery; Employee Contract → Employee.

**Current:** Contract has contract_category (supplier, employee), supplier_id, employee_id, facility_id, start_date, end_date, status, signed_contract_file (and supporting_documents). No: client_id/customer_id, transport_company_id, organization_id. No contract type “customer” or “transport”. Delivery has no contract_id. TransportTrip has no contract_id.

**Improvement:**

- Add **contract_category**: `customer`, `transport` (in addition to supplier, employee).
- Add **client_id** (for customer contracts) and **transport_company_id** (for transport contracts). Optionally add **organization_id** as a generic link.
- Add **contract_document** (or keep using signed_contract_file) and ensure “Contract Document Upload” is supported in UI.
- **DeliveryConfirmation:** add optional **contract_id** (customer contract fulfilled by this delivery).
- **TransportTrip:** add optional **contract_id** or **transport_company_id** (and validate active transport contract if spec requires).
- Enforce in app logic: “Contracts must be Active before they can be linked to operations” (intake, trip, delivery).

---

### 2.7 Communication Logs (Spec §9)

**Spec requires:** Contact ID, communication type (Call / Email / Meeting / Inspection Notice), date, subject, message/notes, user ID.

**Current:** **ClientActivity** has client_id, activity_type (call, email, meeting, note), subject, notes, occurred_at, user_id. No “Contact ID”; no “Inspection Notice” type.

**Improvement:**

- Add activity_type **inspection_notice** to ClientActivity (or to a generic CommunicationLog if you later move to Contact-based logs).
- When **Contact** exists: add **communication_logs** (or **contact_activities**) linked to **contact_id** and optionally still to client_id for backward compatibility. Spec says “Contact ID”; so long-term, communication logs should be contact-centric. Short-term: keep client_activities, add inspection_notice type.

---

## 3. Business logic (Spec §10) – current vs required

| Rule | Current | Action |
|------|---------|--------|
| Animal Intake only from **Approved** suppliers | Not enforced on Supplier model (only contract + health cert on SlaughterPlan). | Add supplier_status; enforce “approved” (and optionally active contract) when creating/using intake linked to supplier. |
| Deliveries only for **Active** customers | Client has is_active; not enforced on DeliveryConfirmation creation. | In DeliveryConfirmation store: if client_id present, require client.is_active. |
| Transport trips only **Active** transport companies | No transport company entity. | Once TransportCompany exists, enforce transport_company.status active when creating/editing trip. |
| Contracts **Active** before linking to operations | Partially: SlaughterPlan uses active supplier contract. No check on Delivery or TransportTrip. | Enforce: link to intake/trip/delivery only when contract.status = active (and not expired). |
| Organizations may have multiple contacts | N/A (no organizations/contacts). | Implement when Organization + Contact exist. |
| Contacts always belong to an organization | N/A. | Implement when Contact exists: contact.organization_id required. |

---

## 4. Relationships with operational modules (Spec §11)

| Link | Current | Improvement |
|------|---------|-------------|
| Supplier → Animal Intake | ✅ supplier_id, contract_id | Add supplier status check (approved only). |
| Supplier → Slaughter Session | Via AnimalIntake → SlaughterPlan → SlaughterExecution. | No change. |
| Customer → Demand | ✅ Demand.client_id | Optional: rename “Client” to “Customer” in UI or add customer_type. |
| Customer → Delivery | ✅ DeliveryConfirmation.client_id | Add optional customer contract_id on delivery; enforce active customer. |
| Transport Company → Transport Trip | ❌ | Add TransportCompany; add transport_company_id (and optional contract_id) on TransportTrip. |
| Contracts → Suppliers / Employees / Transport / Deliveries | Supplier & employee only; facility_id present. | Add customer contract (client_id), transport contract (transport_company_id); link Contract to Delivery and TransportTrip. |
| Contacts → Communication Logs | ClientActivity → client only. | Add Contact; optionally communication_log.contact_id. |
| Organizations → Suppliers / Customers / Transport Companies | ❌ | Add Organization; add organization_id to Supplier, Client/Customer, TransportCompany. |

---

## 5. Implementation notes (Spec §12)

| Requirement | Current | Action |
|-------------|---------|--------|
| Relational DB, foreign keys | ✅ Laravel migrations, FKs in place. | Keep; add FKs for new entities (organization_id, contact_id, transport_company_id, etc.). |
| Search and filtering | ✅ Index/list pages with basic listing. | Add search/filter on Organizations, Contacts, Suppliers, Customers, Transport Companies, Contracts (e.g. by status, type, date range). |
| Status management | ✅ is_active on Client/Supplier; status on Contract. | Add supplier_status (approved/suspended/blacklisted); add status on TransportCompany; enforce in business rules. |
| Document uploads for contracts | ✅ Contract has signed_contract_file, supporting_documents. | Confirm storage and UI; add if missing for “Contract Document Upload”. |
| Integration with operational modules | ✅ Demand, Delivery, AnimalIntake, SlaughterPlan use Client/Supplier/Contract. | Extend to Transport Company and Transport Trip; add contract links to Delivery and TransportTrip; enforce “active” rules. |

---

## 6. Prioritised improvement roadmap

### Phase A – Quick wins (no new entities)

1. **Supplier status**  
   Add `supplier_status` (approved/suspended/blacklisted). Enforce “approved” (and optionally active contract) when linking Supplier to Animal Intake / Slaughter Plan.

2. **Customer (Client) rules**  
   Enforce “only active clients” when creating a delivery confirmation with client_id. Add optional **preferred_facility_id** and **business_type** (and preferred_species if useful) to Client.

3. **Contract types**  
   Add contract categories **customer** and **transport**; add **client_id** and **transport_company_id** to contracts table (transport_company_id nullable until Phase C). Add optional **contract_id** to DeliveryConfirmation (customer contract).

4. **Communication log**  
   Add **inspection_notice** to ClientActivity activity_type.

5. **Contract–operation rules**  
   Enforce “contract must be active (and not expired) before linking to intake/delivery/trip” in validation or controller.

### Phase B – Organizations and contacts

6. **Organizations table**  
   Create **organizations** (name, type, registration_number, tax_id, phone, email, website, country, province, district, sector, address, status, notes). Link to business_id (tenant) if needed.

7. **Contacts table**  
   Create **contacts** (first_name, last_name, phone, email, job_title, organization_id, role, preferred_communication_method, status, notes). Enforce organization_id required.

8. **Link existing entities**  
   Add **organization_id** to Supplier, Client, and (when created) TransportCompany. Add **primary_contact_id** to Supplier and Client. Optionally show Organization and primary Contact on Client/Supplier show pages.

9. **Communication logs → Contact**  
   Add **contact_id** to ClientActivity (or create contact_activities with contact_id). When logging an activity, allow choosing Contact (and optionally still Client for backward compatibility).

### Phase C – Transport companies and contract links

10. **Transport companies table**  
    Create **transport_companies** (business_id, name, registration_number, contact_person, phone, email, fleet_size, vehicle_types, cold_chain_capable, operating_regions, status). Optional: organization_id, primary_contact_id.

11. **TransportTrip**  
    Add **transport_company_id** (and optional **contract_id**). Enforce “only active transport company” when set. Keep driver_name/driver_phone for ad-hoc.

12. **Transport contract**  
    Use contract_category = transport and transport_company_id; link Contract to TransportTrip (contract_id or validate by transport_company_id).

13. **Customer contract → Delivery**  
    Ensure DeliveryConfirmation can store contract_id (customer contract) and that “Customer Contract → Delivery” is visible in UI and reports.

### Phase D – Optional enhancements

14. **Search and filters**  
    Add search (name, number, type) and filters (status, type, date range) on all CRM index pages.

15. **Contract document upload**  
    Verify and document contract file upload (signed_contract_file, supporting_documents); add UI if missing.

16. **Supplier spec fields**  
    Add species_supplied, estimated_supply_capacity, animal_health_certification_status to Supplier (or to linked contract/organization) and show in UI.

---

## 7. Summary table

| Spec entity / area | Exists? | Priority improvement |
|--------------------|--------|-----------------------|
| Organizations | No | Phase B: add table; link Supplier, Client, TransportCompany. |
| Contacts | No | Phase B: add table; link to Organization; primary_contact on Supplier/Client; use in communication logs. |
| Suppliers | Yes | Phase A: supplier_status (approved/suspended/blacklisted) + enforce on intake. Phase B: organization_id. Phase D: species, capacity, health cert. |
| Customers (Client) | Yes (as Client) | Phase A: enforce active client on delivery; preferred_facility_id, business_type. Phase B: organization_id, primary_contact_id. |
| Transport Companies | No | Phase C: add table; link TransportTrip; add transport contract. |
| Contracts | Yes | Phase A: add customer + transport types; client_id; contract_id on Delivery. Phase C: transport_company_id, link to TransportTrip. |
| Communication Logs | Yes (ClientActivity) | Phase A: add inspection_notice. Phase B: contact_id and contact-centric logging. |
| Business rules | Partial | Phase A: enforce approved supplier, active customer, active contract when linking to operations. Phase C: active transport company. |

This roadmap aligns the platform with the SafeMeat CRM spec in a staged way: Phase A improves behaviour and data without new core entities; Phase B adds Organizations and Contacts; Phase C adds Transport Companies and completes contract links; Phase D polishes search, filters, and document upload.
