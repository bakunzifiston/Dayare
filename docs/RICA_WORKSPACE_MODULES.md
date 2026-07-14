# RICA Workspace — Modules, Navigation & Dashboards

Reference for the **RICA oversight** workspace in DayareMeat (BuchaPro). Generated from routes, controllers, services, sidebar configuration, and views in the codebase.

---

## Overview

| Item | Detail |
|------|--------|
| **Purpose** | National meat inspection oversight across registered slaughter operators |
| **URL prefix** | `/super-admin/rica` |
| **Route name prefix** | `rica.*` |
| **Middleware** | `auth`, `tenant`, `super_admin`, `super_admin.module:rica` |
| **Default home** | RICA-only super admins land on `rica.dashboard` (`User::SUPER_ADMIN_MODULE_RICA`) |
| **Data scope** | Cross-tenant; slaughterhouses and inspection data from all processor businesses |
| **Tenant filter** | `TenantEnvironmentScope` — live / test / all (configured in RICA Settings) |
| **Primary controller** | `App\Http\Controllers\SuperAdmin\RicaController` |

RICA is a **super-admin module**, not a processor tenant workspace. It reads operational data recorded by processors (intake, slaughter, inspection, certificates, transport) and presents national dashboards, reports, and alerts.

---

## Navigation

Sidebar group: **RICA** (`resources/views/layouts/sidebar.blade.php`)

| Label | Route | Path |
|-------|-------|------|
| Dashboard | `rica.dashboard` | `/super-admin/rica/dashboard` |
| Traceability | `rica.traceability` | `/super-admin/rica/traceability` |
| Meat & condemnation | `rica.meat-condemnation` | `/super-admin/rica/meat-condemnation` |
| Diseases intelligence | `rica.diseases-intelligence` | `/super-admin/rica/diseases-intelligence` |
| Supply chain | `rica.supply-chain` | `/super-admin/rica/supply-chain` |
| Compliance performance | `rica.compliance-performance` | `/super-admin/rica/compliance-performance` |
| Reports | `rica.reports` | `/super-admin/rica/reports` |
| Alerts & notifications | `rica.alerts-notifications` | `/super-admin/rica/alerts-notifications` |
| Settings | `rica.settings` | `/super-admin/rica/settings` |

Traceability sidebar scope also includes slaughterhouse drill-down routes (`rica.slaughterhouses.*`).

---

## Architecture

### Dashboard services

Each analytics module has a dedicated service under `app/Services/SuperAdmin/`:

| Service | View |
|---------|------|
| `RicaOverviewDashboardService` | `superadmin/rica/hub.blade.php` |
| `RicaTraceabilityDashboardService` | `superadmin/rica/traceability.blade.php` |
| `RicaCondemnationDashboardService` | `superadmin/rica/meat-condemnation.blade.php` |
| `RicaDiseaseIntelligenceDashboardService` | `superadmin/rica/diseases-intelligence.blade.php` |
| `RicaSupplyChainDashboardService` | `superadmin/rica/supply-chain.blade.php` |
| `RicaCompliancePerformanceDashboardService` | `superadmin/rica/compliance-performance.blade.php` |
| `RicaAlertsNotificationsDashboardService` | `superadmin/rica/alerts-notifications.blade.php` |
| `RicaReportService` | `superadmin/rica/reports.blade.php` |
| `RicaMonthlyInspectionReportService` | `superadmin/rica/monthly-reports/*` |

Shared filter resolution: `SuperAdminSlaughterDashboardService::resolveHubFilters()`.

### Common filters

Most dashboards support:

| Filter | Values | Notes |
|--------|--------|-------|
| **Period** | `all`, `month`, `year`, `day` | Default varies by module; hub uses RICA Settings default |
| **District** | `all` or district ID | Administrative division (district type) |
| **Slaughterhouse** | Hub only | `facility_id` on national overview |
| **Species** | Disease intelligence | Filters disease case rows |

Charts use Chart.js via Vite entries in `resources/js/rica-*-charts.js`.

### Eligible facilities

Monthly inspection reports and several compliance queries use:

```php
Facility::eligibleForRicaMonthlyReport()
```

A facility qualifies if it is a slaughterhouse **or** has slaughter plans on record.

---

## Modules

### 1. Dashboard (National overview)

**Route:** `rica.dashboard`  
**Service:** `RicaOverviewDashboardService`  
**Default period:** From `RicaSetting::default_dashboard_period` (usually `all`)

Executive landing page for RICA oversight.

#### Executive KPIs

| KPI | Source |
|-----|--------|
| Animals received | Animal intake head count in period |
| Approved meat (kg) | Post-mortem approved items (carcass + other meat) |
| Rejected meat (kg) | Condemned post-mortem items |
| Active slaughterhouses | Facilities with intake or slaughter activity in period |

Each KPI includes trend vs the previous equivalent period.

#### Module summary cards

Six linked cards (same KPI card style) with one headline metric each:

| Module | Headline metric |
|--------|-----------------|
| Traceability | Animals tracked |
| Disease intelligence | Unhealthy animals |
| Meat condemnation | Estimated economic loss (RWF) |
| Supply chain | Approved meat (kg) |
| Compliance performance | Active PMIs |
| Alerts & notifications | Open alerts |

#### Supporting analytics

- Animals received over time (area line chart)
- Animals by species (donut)
- Animals by district (heatmap grid)
- Key insights (narrative cards derived from period comparison)

---

### 2. Traceability

**Route:** `rica.traceability`  
**Service:** `RicaTraceabilityDashboardService`

Batch-level traceability from farm origin through delivery.

#### Features

| Area | Detail |
|------|--------|
| **Coverage KPIs** | Fully / partially / not traceable batches |
| **Batch search** | Ear tag, batch ID, certificate number, QR slug |
| **Journey tracker** | Origin → slaughter → post-mortem → certificate → transport → destination |
| **Timeline** | Per-batch step-by-step events |
| **Live alerts** | Missing origin, missing PM, duplicate certificates, transport delay, etc. |

#### Traceability steps evaluated per batch

1. Origin (animal intake)
2. Slaughter
3. Post-mortem
4. Certificate
5. Cold storage
6. Transport
7. Destination / delivery confirmation

---

### 3. Meat & condemnation

**Route:** `rica.meat-condemnation`  
**Service:** `RicaCondemnationDashboardService`  
**Default period:** This month

Condemnation and economic loss dashboard.

#### KPIs

| KPI | Calculation |
|-----|-------------|
| Rejected meat (kg) | Sum of condemned post-mortem item weights |
| Rejection rate | Rejected ÷ (rejected + approved) × 100 |
| Estimated economic loss | Rejected kg × `RicaSetting::condemnation_loss_per_kg_rwf` |
| Total rejection cases | Count of condemnation rows |

#### Charts & tables

- Rejected meat by organ (donut)
- Reasons for rejection (horizontal bar)
- Rejection trend over time (line)
- Top rejection by species (donut)
- Rejection by slaughterhouse (table)
- Top reasons by economic loss (table)

---

### 4. Diseases intelligence

**Route:** `rica.diseases-intelligence`  
**Service:** `RicaDiseaseIntelligenceDashboardService`  
**Config:** `config/rica_disease_intelligence.php`  
**Default period:** This month

Disease patterns from ante-mortem rejections/deferrals and post-mortem condemnations.

#### KPIs

| KPI | Source |
|-----|--------|
| Disease cases | Ante rejections/deferrals + PM condemnations + abnormal observations |
| Diseases detected | Distinct normalized disease labels |
| Unhealthy animals | Ante-mortem unhealthy outcomes |
| Districts affected | Districts with at least one case |

#### Charts

- Top diseases (bar)
- Disease trend (line)
- Cases by species (donut)
- Seasonal risk (multi-line)
- District case map

Disease names are normalized via `RicaDiseaseLabelResolver`.

---

### 5. Supply chain

**Route:** `rica.supply-chain`  
**Service:** `RicaSupplyChainDashboardService`

Meat movement after inspection: certificates, transport, and delivery.

#### KPIs

| KPI | Detail |
|-----|--------|
| Meat delivered (kg) | From completed transport / delivery rows |
| Certificates issued | Distinct certificates in period |
| Destinations served | Unique destination count |
| Compliance rate | Delivered trips with confirmation vs total |

#### Visualizations

- Meat flow board (origins → destinations)
- Rwanda destinations map
- Delivery trend chart
- Top routes / species breakdown

Uses `RicaSupplyChainDestinationResolver` for destination labeling.

---

### 6. Compliance performance

**Route:** `rica.compliance-performance`  
**Service:** `RicaCompliancePerformanceDashboardService`  
**Default period:** This month

Operator and PMI performance against reporting and inspection benchmarks.

#### KPIs

| KPI | Detail |
|-----|--------|
| Active PMIs | Distinct inspectors on ante/post-mortem in period |
| Reports submitted | Submitted FPU/FRM/018 monthly reports |
| Submission rate | Submitted ÷ expected report slots |
| Average compliance score | 40% submission + 60% inverse rejection rate |
| Average rejection rate | National condemned ÷ total meat |

#### Tables

- Slaughterhouse performance ranking (top 5)
- PMI performance ranking (top 5)

Both include compliance score, rejection rate, and sparkline trend.

#### Charts

- Report submission status (donut: approved / rejected / pending / in review)
- Compliance score trend (line)
- Report submission trend (line)

#### Report status mapping

| Display status | Rule |
|----------------|------|
| Approved | Submitted + stamp acknowledged |
| In review | Submitted without stamp, or draft with signatures |
| Pending | Draft with no progress (current period) |
| Rejected | Missing/overdue for past periods |

---

### 7. Alerts & notifications

**Route:** `rica.alerts-notifications`  
**Service:** `RicaAlertsNotificationsDashboardService`

Central regulatory inbox — no separate alert storage table; alerts are computed live from operational data.

#### Summary KPIs

| KPI | Detail |
|-----|--------|
| Open alerts | Total across all categories |
| Critical | Expired licences, overdue reports, cold room, critical temp |
| Warning | Pending reports, pipeline gaps, transport delay |
| Informational | Recent condemnations |

#### Alert categories

| Category | Examples |
|----------|----------|
| Monthly reports | Overdue or pending FPU/FRM/018 |
| Licences | Expired facility operating licences |
| PMI authorization | Expired inspector authorisations |
| Inspection pipeline | Missing ante-mortem, awaiting post-mortem, overdue cold room, temperature violations |
| Condemnations | Recent condemned meat events (7 days) |
| Transport | In-transit trips delayed beyond 2 days |

#### Filters

- District
- Category chip
- Severity (critical / warning / info)

Each alert row links to the relevant module (monthly report, traceability, condemnation dashboard, etc.).

---

### 8. Reports

**Route:** `rica.reports`  
**Service:** `RicaReportService`

Tabular national slaughter report with facility-level aggregates.

#### Features

- Filter by period, district, business, slaughterhouse
- Per-facility stats: animals slaughtered, meat kg, condemned count, certificates
- Species breakdown
- CSV export (`rica.reports.export`)

Date basis options: slaughter date vs record date.

---

### 9. Monthly inspection reports (FPU/FRM/018)

**Routes:** `rica.monthly-reports.*`  
**Services:** `RicaMonthlyInspectionReportService`, `RicaMonthlyInspectionReportPdfService`, `RicaMonthlyInspectionReportSubmissionService`  
**Model:** `RicaMonthlyInspectionReport`

Regulatory monthly inspection report per slaughterhouse per calendar month.

#### Index views

| View | Purpose |
|------|---------|
| **Submitted** | All submitted reports across periods |
| **Facilities** | Period-scoped facility list with submission status |

#### Per-facility report

- Full FPU/FRM/018 form sections (read-only when submitted)
- Inspector signatures, operator sign-off, stamp acknowledgement
- PDF download (`rica.monthly-reports.pdf`)

Statuses: `draft`, `submitted`.

Processor tenants submit reports from their own workspace; RICA super admins review nationally.

---

### 10. Slaughterhouses

**Routes:** `rica.slaughterhouses.index`, `rica.slaughterhouses.show`

Facility directory and drill-down under the traceability area.

- List all RICA-eligible slaughterhouses
- Per-facility period dashboard (animals, meat, condemned, certificates)
- Linked from traceability and reports workflows

---

### 11. Settings

**Route:** `rica.settings`  
**Controller:** `RicaSettingsController`  
**Model:** `RicaSetting` (key-value store)

| Setting | Default | Purpose |
|---------|---------|---------|
| `workspace_name` | RICA oversight | Display name |
| `default_tenant_environment` | `all` | Live / test / all scope for dashboards |
| `default_dashboard_period` | `all` | Default period on hub and several modules |
| `notification_email` | (empty) | Alert notification target |
| `monthly_report_deadline_day` | `5` | Day of month for report deadline logic |
| `condemnation_loss_per_kg_rwf` | `3880` | Economic loss estimate for condemnation dashboards |

---

## Data lineage

```
Processor workspace
  Animal intake → Slaughter plan → Ante-mortem → Slaughter execution
    → Batch → Post-mortem → Certificate → Warehouse storage
    → Transport trip → Delivery confirmation
         ↓
RICA workspace (read-only aggregation)
  Overview / Traceability / Condemnation / Disease / Supply chain
  Compliance / Alerts / Reports / Monthly reports
```

RICA does not mutate processor operational records except:

- RICA Settings (`rica_settings` table)
- Monthly report records when viewed/edited in oversight context (submitted reports are read-only)

---

## Key files

| Area | Path |
|------|------|
| Routes | `routes/web.php` (RICA group) |
| Controller | `app/Http/Controllers/SuperAdmin/RicaController.php` |
| Settings controller | `app/Http/Controllers/SuperAdmin/RicaSettingsController.php` |
| Dashboard services | `app/Services/SuperAdmin/Rica*DashboardService.php` |
| Views | `resources/views/superadmin/rica/` |
| Shared CSS | `resources/css/rica-supply-chain.css`, `rica-traceability.css` |
| Chart JS | `resources/js/rica-*-charts.js` |
| Tests | `tests/Feature/SuperAdmin/Rica*.php` |

---

## Testing

Feature tests cover module accessibility and dashboard data:

- `RicaMonthlyReportTest` — routes, settings, monthly report PDF
- `RicaCondemnationDashboardTest`
- `RicaDiseaseIntelligenceDashboardTest`
- `RicaCompliancePerformanceDashboardTest`
- `RicaAlertsNotificationsDashboardTest`

Run:

```bash
php artisan test --filter=Rica
```

Frontend assets (charts):

```bash
npm run build
```
