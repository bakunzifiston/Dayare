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

Validation failures use Laravel’s field map in `errors` (HTTP `422`). Paginated lists put the **Laravel paginator array** inside `data` (so list rows are at `data.data`).

## HTTP verbs in v1

This API is **read/create only** for processor workflows: **GET** and **POST** are implemented. There are **no** `PUT`, `PATCH`, or `DELETE` routes under `/api/v1` yet—updates and deletes must be done in the web workspace, or added in a future API version.

## Authentication

The mobile API uses Bearer token authentication.

### Login

- `POST /api/v1/auth/login`
- Public endpoint (no token required)

Request:

```json
{
  "email": "user@company.com",
  "password": "secret",
  "device_name": "android-phone-1"
}
```

Response `200` (payload under `data`):

```json
{
  "success": true,
  "message": "Logged in successfully.",
  "data": {
    "token": "plain_token_string",
    "token_type": "Bearer",
    "expires_at": "2026-05-10T12:00:00+00:00",
    "user": {
      "id": 12,
      "name": "Field User",
      "email": "user@company.com",
      "is_super_admin": false,
      "userRole": "business_manager",
      "business_type": "processor"
    }
  }
}
```

Wrong email/password returns HTTP **`401`** with `success: false` and message `Invalid credentials.` Malformed requests (e.g. missing fields) return **`422`** with validation `errors`.

`POST /api/v1/auth/login` is **rate limited** (5 attempts per minute per IP by default).

### Auth header for protected endpoints

`Authorization: Bearer <token>`

### Get current user

- `GET /api/v1/auth/me`

Response `200` (user fields under `data`):

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": 12,
    "name": "Field User",
    "email": "user@company.com",
    "is_super_admin": false,
    "userRole": "business_manager",
    "business_type": "processor",
    "accessible_business_ids": [3, 5]
  }
}
```

### Logout

- `POST /api/v1/auth/logout`
- Invalidates current token

---

## Common Response Notes

- Validation errors return `422` with `errors` populated.
- Unauthorized token returns `401`.
- Out-of-scope resources return `404`.
- Successful creates return `201` where applicable.
- Paginated list endpoints: paginator fields are inside the top-level `data` object (see **Standard JSON envelope**).
- API routes are **stateless** (no CSRF); use `Authorization: Bearer` on protected routes.

---

## Endpoints

## 1) Lookup Data (for form dropdowns)

### Get lookups

- `GET /api/v1/lookups`

Response `200` (lookup payload under `data`):

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "facilities": [
      { "id": 1, "facility_name": "Main Slaughterhouse", "facility_type": "slaughterhouse" }
    ],
    "inspectors": [
      { "id": 10, "facility_id": 1, "first_name": "John", "last_name": "Doe", "status": "active" }
    ],
    "species": [
      { "id": 1, "name": "Cattle", "code": "CAT" }
    ],
    "statuses": {
      "animal_intake": ["received", "approved", "rejected"],
      "slaughter_plan": ["planned", "approved"],
      "slaughter_execution": ["scheduled", "in_progress", "completed", "cancelled"]
    }
  }
}
```

---

## 2) Animal Intakes

### List animal intakes

- `GET /api/v1/animal-intakes?per_page=20`

### Create animal intake

- `POST /api/v1/animal-intakes`

Request body:

```json
{
  "facility_id": 1,
  "intake_date": "2026-04-10",
  "species": "Cattle",
  "number_of_animals": 25,
  "status": "received",
  "supplier_firstname": "Alice",
  "supplier_lastname": "N.",
  "supplier_contact": "+2507xxxxxxx",
  "farm_name": "Green Farm",
  "animal_identification_numbers": "TAG-001,TAG-002"
}
```

Required fields:

- `facility_id`, `intake_date`, `species`, `number_of_animals`, `status`
- `supplier_firstname`, `supplier_lastname`

---

## 3) Slaughter Plans

### List slaughter plans

- `GET /api/v1/slaughter-plans?per_page=20`

### Create slaughter plan

- `POST /api/v1/slaughter-plans`

Request body:

```json
{
  "slaughter_date": "2026-04-11",
  "facility_id": 1,
  "animal_intake_id": 100,
  "inspector_id": 10,
  "species": "Cattle",
  "number_of_animals_scheduled": 20,
  "status": "planned"
}
```

---

## 4) Slaughter Executions

### List slaughter executions

- `GET /api/v1/slaughter-executions?per_page=20`

### Create slaughter execution

- `POST /api/v1/slaughter-executions`

Request body:

```json
{
  "slaughter_plan_id": 77,
  "actual_animals_slaughtered": 19,
  "slaughter_time": "2026-04-11 09:30:00",
  "status": "completed"
}
```

---

## 5) Ante-Mortem Inspections

### Create ante-mortem inspection

- `POST /api/v1/ante-mortem-inspections`

Request body:

```json
{
  "slaughter_plan_id": 77,
  "inspector_id": 10,
  "inspection_date": "2026-04-11",
  "species": "Cattle",
  "number_examined": 20,
  "number_approved": 19,
  "number_rejected": 1,
  "notes": "One animal rejected",
  "observations": {
    "behavior": { "value": "normal", "notes": null },
    "gait_posture": { "value": "normal", "notes": null }
  }
}
```

Validation rules:

- `number_approved + number_rejected <= number_examined`
- `observations` required and validated against species checklist
- `inspector_id` must refer to an **active** inspector assigned to the **same facility** as the slaughter plan

---

## 6) Post-Mortem Inspections

### Create post-mortem inspection

- `POST /api/v1/post-mortem-inspections`

Request body:

```json
{
  "batch_id": 301,
  "inspector_id": 10,
  "species": "Cattle",
  "inspection_date": "2026-04-11",
  "total_examined": 19,
  "approved_quantity": 18,
  "condemned_quantity": 1,
  "notes": "Minor lesion",
  "observations": {
    "carcass_lesions": { "value": "yes", "notes": "Localized" },
    "organ_liver": { "value": "normal", "notes": null }
  }
}
```

Validation rules:

- `approved_quantity + condemned_quantity <= total_examined`
- `observations` required and validated against species checklist
- `inspector_id` must refer to an **active** inspector assigned to the **same facility** as the batch’s slaughter plan

Computed result:

- Server computes and stores one of:
  - `approved`
  - `partial`
  - `rejected`

---

## Suggested Mobile Integration Flow

1. Login (`/auth/login`) and store bearer token securely.
2. Call `/lookups` once after login (refresh periodically).
3. Submit data in operational order:
   - animal intake
   - slaughter plan
   - slaughter execution
   - ante-mortem inspection
   - post-mortem inspection
4. On `401` (invalid token or failed login), redirect to login or show invalid credentials.
5. On `422`, show field-level or form-level validation message from response.

---

## Versioning Notes

- Current version: `v1`
- Future changes should be additive or released under `v2` to avoid breaking mobile clients.

