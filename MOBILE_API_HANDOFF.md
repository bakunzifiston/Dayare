# Mobile API Handoff

This handoff summarizes how mobile clients should integrate with the current `/api/v1` contract.

## Base Rules

- Base path: `/api/v1`
- Auth for protected routes: `Authorization: Bearer <token>` (opaque `mobile_api_tokens`, middleware `mobile.auth` — not Sanctum)
- Success envelope: `{ "success": true, "message": "...", "data": ... }`
- Error envelope: `{ "success": false, "message": "...", "errors": {...} }`

## Authentication Lifecycle

- Login: `POST /api/v1/auth/login` (throttle 5/min)
- Register: `POST /api/v1/auth/register` (throttle 10/min)
- Current user: `GET /api/v1/auth/me` (optional `business_id`)
- Logout (current token only): `POST /api/v1/auth/logout`

Token policy:

- Token is opaque and returned once on login/register
- `expires_at` is returned (~30 days)
- No refresh endpoint
- On `401`, client must re-login

Gate features on `data.user.permissions` (or `/auth/me`), not only `userRole`.

## Ownership and 404 Semantics

For protected `/api/v1/*` routes, `404` means either the resource does not exist **or** it is outside the current workspace scope. This is intentional.

## Pagination Contract (List Endpoints)

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "data": [],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 20,
      "total": 0
    },
    "filters": {}
  }
}
```

## Core Mobile Endpoints

### Auth & tenant

- `POST /auth/login`, `POST /auth/register`, `GET /auth/me`, `POST /auth/logout`
- `POST /businesses`
- `GET /dashboard`, `GET /lookups`

### Collections and processing

- Animal intakes: `GET/POST /animal-intakes`, `GET/PUT/DELETE /animal-intakes/{id}`, `POST .../submit`
- Slaughter plans / executions: full list/create/show/update/delete
- Inspectors: full CRUD
- Batches: `GET` list/show only
- Ante-mortem / post-mortem: full CRUD (see known post-mortem create drift below)
- Monthly inspection reports: list, show by facility, PDF download (`GET .../{facility}/pdf`), closure POST
- Certificates: list/create/show/update/delete + `.../qr` + `.../pdf`

### Logistics

- `POST /warehouse-storages` — **known FormRequest vs controller drift; prefer web**
- `GET /transport-trips`, `GET /transport-trips/{id}`, `GET /transport-trips/export`, `POST /transport-trips`
- `GET /delivery-confirmations/export`, `POST /delivery-confirmations`

### Transport create (current)

- Required: `certificate_id`, `origin_facility_id`, `destination_name`, vehicle/driver, `departure_date`, `status`
- Optional ISO-2 `destination_country`, `destination_address`
- **Do not send** `destination_facility_id` (prohibited)
- Certificate-locked fields are forced server-side

### Delivery create (current)

- Required: `transport_trip_id`, quantity, date, `receiver_name`, `confirmation_status`
- Receiver fields locked from trip destination are forced server-side
- **Do not send** `receiving_facility_id` (prohibited)

### Public

- `GET /` — API meta
- `GET /verify/permit/{identifier}` — public permit check

## Expected Error Handling on Mobile

- `401`: missing/invalid/expired token → clear session and force login
- `403`: authenticated but forbidden by policy/role (e.g. `export_records`)
- `404`: not found OR out of workspace scope
- `422`: validation or business rule failure → show `errors`
- `429`: throttled → retry with backoff

## Known gaps / drifts (do not rely on mobile for these yet)

1. **Post-mortem POST:** FormRequest requires `slaughter_execution_id`; mobile store still reads `batch_id`.
2. **Warehouse POST:** FormRequest requires `post_mortem_inspection_item_ids`; mobile store still reads `certificate_id`.
3. Delivery confirmations have **create + export only** (no mobile list/show/update).

## Swagger

- UI: `/api/documentation`
- Raw: regenerate with `composer api-docs`
- Filter tag **Mobile API**
- Full narrative: `docs/MOBILE_API_DOCUMENTATION.md`
