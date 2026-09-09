# BuchaPro Mobile API (v1)

This document is for mobile app integration with the BuchaPro backend deployed at [buchapro.com](https://buchapro.com/).

## Base URL

- Production: `https://buchapro.com`
- API prefix: `/api/v1`
- Full example: `https://buchapro.com/api/v1/auth/login`

Interactive reference: `GET /api/documentation` (Swagger UI). The bundled spec includes **web** routes for staff reference; filter by tag **Mobile API** for Bearer JSON endpoints only.

**Regenerate OpenAPI JSON after changing annotations:** `composer api-docs` or `php artisan l5-swagger:generate` (run in CI/deploy so production stays current).

## Standard JSON envelope

Unless noted otherwise, successful responses use:

```json
{
  "success": true,
  "message": "Human-readable message",
  "data": { }
}
```

Errors:

```json
{
  "success": false,
  "message": "Summary message",
  "errors": { }
}
```

Validation failures use Laravel’s field map in `errors` (HTTP `422`). Paginated lists put the **Laravel paginator array** inside `data` (list rows at `data.data`, plus `data.meta` and `data.filters`).

## Authentication

The mobile API uses **opaque Bearer tokens** stored hashed in `mobile_api_tokens` (not Sanctum / not JWT). Middleware: `mobile.auth`. Tokens expire **30 days** after issuance (`expires_at`). There is **no refresh endpoint**; on `401`, re-login.

### Login

- `POST /api/v1/auth/login` — public; throttle **5/min** per IP

Request:

```json
{
  "email": "user@company.com",
  "password": "secret",
  "device_name": "android-phone-1",
  "business_id": null
}
```

Optional `business_id` resolves `userRole`, `business_type`, and effective `permissions` for that workspace (must be accessible).

Response `200` payload under `data`: `token`, `token_type` (`Bearer`), `expires_at`, `user` (see **Get current user**).

Wrong credentials → **`401`**. Validation errors → **`422`**.

### Register (stateless)

- `POST /api/v1/auth/register` — public, **no CSRF**; throttle **10/min**
- Body: `name`, `email`, `password`, `password_confirmation`, `business_type` (`farmer` | `processor` | `logistics`), optional `device_name`
- Returns **`201`** with the same token + `user` shape as login

Do **not** use web `POST /register` or `POST /businesses` from mobile (CSRF). Use API routes instead.

### Create business (authenticated)

- `POST /api/v1/businesses` with `Authorization: Bearer <token>`
- Body matches `StoreBusinessRequest` (same fields as the web form)

### Get current user

- `GET /api/v1/auth/me` — optional query `business_id`
- Response `200` `data`:

```json
{
  "id": 12,
  "name": "Field User",
  "email": "user@company.com",
  "is_super_admin": false,
  "userRole": "inspector",
  "business_type": "processor",
  "business_id": 3,
  "permissions": ["view_processor_dashboard", "record_ante_mortem", "view_certificates"],
  "accessible_businesses": [
    { "id": 3, "name": "Acme Ltd", "type": "processor", "membership": "inspector" }
  ],
  "accessible_business_ids": [3]
}
```

- **`userRole`**: membership for the selected business (`org_admin`, `operations_manager`, `compliance_officer`, `inspector`, `transport_manager`, `accountant`, `super_admin`, or `user`).
- **`permissions`**: effective processor permission strings for the selected business. Gate mobile features on this list.
- **`business_type`**: `farmer` | `processor` | `logistics`.

### Logout

- `POST /api/v1/auth/logout` — invalidates **only the current** bearer token

### Auth header

`Authorization: Bearer <token>`

---

## Common response notes

| Code | Meaning |
|------|---------|
| `401` | Missing / invalid / expired token (or bad login credentials) |
| `403` | Authenticated but forbidden (policy / permission, e.g. export) |
| `404` | Missing **or** outside workspace scope (intentional; prevents tenant enumeration) |
| `422` | Validation or business-rule failure (`errors` map) |
| `429` | Throttled |

Creates return **`201`** where applicable. Routes are **stateless** (no CSRF).

---

## Public (unauthenticated)

| Method | Path | Notes |
|--------|------|--------|
| `GET` | `/api/v1/` | API name, version, documentation URL |
| `GET` | `/api/v1/verify/permit/{identifier}` | Public permit verification; throttle 60/min. Response shape may omit `message` |

---

## Endpoint inventory (authenticated)

All paths below are under `/api/v1` and require Bearer auth unless noted.

### Dashboard & lookups

| Method | Path | Notes |
|--------|------|--------|
| `GET` | `/dashboard` | Processor KPI shell from `ProcessorDashboardService` |
| `GET` | `/lookups` | `facilities`, `inspectors`, `species`, `statuses`, ante/post-mortem checklists + meta |

### Animal intakes

| Method | Path |
|--------|------|
| `GET` | `/animal-intakes` |
| `POST` | `/animal-intakes` |
| `GET` | `/animal-intakes/{animalIntake}` |
| `PUT` | `/animal-intakes/{animalIntake}` |
| `POST` | `/animal-intakes/{animalIntake}/submit` |
| `DELETE` | `/animal-intakes/{animalIntake}` |

Create uses `StoreAnimalIntakeRequest` (client-sourced intake; `source_type` forced to client). List filters: `facility_id`, `species`, `status`, `intake_date_from/to`, `per_page`.

### Slaughter plans & executions

| Method | Path |
|--------|------|
| `GET`/`POST` | `/slaughter-plans` |
| `GET`/`PUT`/`DELETE` | `/slaughter-plans/{slaughterPlan}` |
| `GET`/`POST` | `/slaughter-executions` |
| `GET`/`PUT`/`DELETE` | `/slaughter-executions/{slaughterExecution}` |

### Monthly inspection reports

| Method | Path | Notes |
|--------|------|--------|
| `GET` | `/monthly-inspection-reports` | Paginated list |
| `GET` | `/monthly-inspection-reports/{facility}` | Full report JSON; query `year`, `month` |
| `GET` | `/monthly-inspection-reports/{facility}/pdf` | PDF download (`application/pdf`); query `year`+`month` or `month=YYYY-MM` (defaults to current month) |
| `POST` | `/monthly-inspection-reports/{facility}/closure` | Save draft or submit to RICA |

### Inspectors

| Method | Path |
|--------|------|
| `GET`/`POST` | `/inspectors` |
| `GET`/`PUT`/`DELETE` | `/inspectors/{inspector}` |

### Batches (read-only)

| Method | Path |
|--------|------|
| `GET` | `/batches` |
| `GET` | `/batches/{batch}` |

### Ante-mortem & post-mortem

| Method | Path |
|--------|------|
| `GET`/`POST` | `/ante-mortem-inspections` |
| `GET`/`PUT`/`DELETE` | `/ante-mortem-inspections/{anteMortemInspection}` |
| `GET`/`POST` | `/post-mortem-inspections` |
| `GET`/`PUT`/`DELETE` | `/post-mortem-inspections/{postMortemInspection}` |

**Post-mortem create (`StorePostMortemInspectionRequest`):** requires `slaughter_execution_id`, inspector, species, counts, `inspection_date`, plus `observations` and/or `item_outcomes`.

> **Known drift:** the mobile store action still scopes with `batch_id` after validation while the FormRequest requires `slaughter_execution_id`. Prefer the web flow until aligned. See Swagger `PostMortemCreateRequest`.

### Certificates

| Method | Path | Notes |
|--------|------|--------|
| `GET`/`POST` | `/certificates` | Create: `StoreCertificateRequest` |
| `GET`/`PUT`/`DELETE` | `/certificates/{certificate}` | |
| `GET` | `/certificates/{certificate}/qr` | `slug`, `trace_url`, `qr_svg` |
| `GET` | `/certificates/{certificate}/pdf` | PDF download (not ApiJson); eligibility errors → 422 |

### Warehouse storage

| Method | Path |
|--------|------|
| `POST` | `/warehouse-storages` |

**FormRequest (`StoreWarehouseStorageRequest`):** `warehouse_facility_id`, `post_mortem_inspection_item_ids[]`, `entry_date`, `quantity_unit`, optional `cold_room_id` / `quantities` / `temperature_at_entry`.

> **Known drift:** mobile store still expects certificate-centric fields after validation. Prefer the web cold-room flow until aligned. See Swagger `WarehouseStorageCreateRequest`.

### Transport trips

| Method | Path | Notes |
|--------|------|--------|
| `GET` | `/transport-trips` | Filters: `per_page`, `certificate_id`, `status`, `origin_facility_id`, `destination_facility_id` (legacy), `departure_date_from/to` |
| `GET` | `/transport-trips/export` | Requires `export_records`; query: `format`, `status`, `from`, `to`, facility filters |
| `GET` | `/transport-trips/{transportTrip}` | Show |
| `POST` | `/transport-trips` | Create — see below |

#### Create transport trip

`StoreTransportTripRequest` (+ certificate prepare/validate + destination rules):

**Required:** `certificate_id`, `origin_facility_id`, `destination_name`, `vehicle_plate_number`, `driver_name`, `departure_date`, `status` (`pending` \| `in_transit` \| `arrived` \| `completed`)

**Optional:** `batch_id`, `destination_country` (ISO-2 from `config('processor.destination_countries')`), `destination_address`, `driver_phone`, `arrival_date`

**Prohibited:** `destination_facility_id`

Certificate must be **active** and not expired. Locked vehicle/driver/destination/departure fields are **forced from the certificate** when present; mismatches → `422`.

Example:

```json
{
  "certificate_id": 101,
  "origin_facility_id": 3,
  "destination_name": "Nairobi Cold Store",
  "destination_country": "KE",
  "destination_address": "Industrial Area, Nairobi",
  "vehicle_plate_number": "RAB123C",
  "driver_name": "Jean Claude",
  "driver_phone": "+250788000000",
  "departure_date": "2026-04-22",
  "status": "pending"
}
```

### Delivery confirmations

| Method | Path | Notes |
|--------|------|--------|
| `GET` | `/delivery-confirmations/export` | Requires `export_records` |
| `POST` | `/delivery-confirmations` | Create — see below |

There is **no** mobile list/show/update/delete for deliveries yet (web workspace only).

#### Create delivery confirmation

`StoreDeliveryConfirmationRequest`:

**Required:** `transport_trip_id`, `received_quantity`, `received_date`, `receiver_name`, `confirmation_status` (`pending` \| `confirmed` \| `disputed`)

**Optional:** `received_unit` (`units` \| `kg` \| `g` \| `tonnes` \| `carcasses` \| `boxes`), `receiver_country`, `receiver_address`, `client_id`, `contract_id`

**Prohibited:** `receiving_facility_id`

When the trip has destination name/country/address, those values are **always written into** the receiver fields (locked). Optional `client_id` must be an **active** client on an accessible business.

Example:

```json
{
  "transport_trip_id": 44,
  "received_quantity": 24,
  "received_unit": "kg",
  "received_date": "2026-04-23",
  "receiver_name": "Nairobi Cold Store",
  "receiver_country": "KE",
  "confirmation_status": "confirmed"
}
```

---

## Suggested mobile integration flow

1. Login (`/auth/login`) and store the bearer token securely.
2. Call `/lookups` (and optionally `/dashboard`) after login; refresh periodically.
3. Gate screens on `permissions` from login / `/auth/me`.
4. Operational order (processor):
   - animal intake → slaughter plan → slaughter execution
   - ante-mortem → post-mortem → certificate
   - (optional) warehouse storage via **web** until mobile store is aligned
   - transport trip → delivery confirmation
5. On `401`, clear session and re-login.
6. On `422`, show field-level messages from `errors`.

---

## Versioning notes

- Current version: `v1`
- Prefer additive changes, or release breaking changes under `v2`
- Source of truth for fields: FormRequests + `routes/api.php`; keep Swagger PHP attributes (`app/Swagger/*`) in sync and regenerate `storage/api-docs/api-docs.json`
