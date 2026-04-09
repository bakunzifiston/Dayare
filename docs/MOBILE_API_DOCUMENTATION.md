# BuchaPro Mobile API (v1)

This document is for mobile app integration with the BuchaPro backend deployed at [buchapro.com](https://buchapro.com/).

## Base URL

- Production: `https://buchapro.com`
- API prefix: `/api/v1`
- Full example: `https://buchapro.com/api/v1/auth/login`

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

Response `200`:

```json
{
  "token": "plain_token_string",
  "token_type": "Bearer",
  "expires_at": "2026-05-10T12:00:00+00:00",
  "user": {
    "id": 12,
    "name": "Field User",
    "email": "user@company.com",
    "is_super_admin": false
  }
}
```

### Auth header for protected endpoints

`Authorization: Bearer <token>`

### Get current user

- `GET /api/v1/auth/me`

Response `200`:

```json
{
  "id": 12,
  "name": "Field User",
  "email": "user@company.com",
  "is_super_admin": false,
  "accessible_business_ids": [3, 5]
}
```

### Logout

- `POST /api/v1/auth/logout`
- Invalidates current token

---

## Common Response Notes

- Validation errors return `422`.
- Unauthorized token returns `401`.
- Out-of-scope resources return `404`.
- Paginated list endpoints return Laravel paginator JSON (`data`, `current_page`, `last_page`, etc.).

---

## Endpoints

## 1) Lookup Data (for form dropdowns)

### Get lookups

- `GET /api/v1/lookups`

Response `200`:

```json
{
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
4. On `401`, redirect to login.
5. On `422`, show field-level or form-level validation message from response.

---

## Versioning Notes

- Current version: `v1`
- Future changes should be additive or released under `v2` to avoid breaking mobile clients.

