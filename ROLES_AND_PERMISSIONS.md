# Roles and Permissions – Implementation Guide

This guide fits your Dayare structure: **multi-tenant** (User → Businesses), **Super Admin** (platform owner), and modules (Operations, CRM, Settings).

---

## 1. Recommended approach: Spatie Laravel Permission

- **Package:** [spatie/laravel-permission](https://github.com/spatie/laravel-permission)
- **Why:** Works with Laravel 12, uses `roles` and `permissions` tables, integrates with your existing `User` model and `is_super_admin` flag.

### Install

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

---

## 2. Roles that match your system

| Role           | Who uses it        | Scope        |
|----------------|--------------------|-------------|
| **Super Admin**| Platform owner     | Full system |
| **Owner**      | Tenant (user) who owns businesses | All their businesses + CRM + Settings |
| **Manager**    | Trusted staff      | Operations + CRM (e.g. no Settings) |
| **Staff**      | Limited access     | View + limited create/edit (you define) |

- **Super Admin** is already implied by `users.is_super_admin`. You can either keep that and give Super Admin users a “super_admin” role with all permissions, or rely only on `is_super_admin` and use roles only for tenants.
- **Tenants** (users with businesses): assign **Owner** when they register or when they create their first business; optionally **Manager** / **Staff** for other users you invite (if you add “team” later).

---

## 3. Permissions grouped by your modules

Map one permission per “area” (or per action if you want finer control). Example:

**Operations**

- `view businesses` / `manage businesses`
- `view facilities` / `manage facilities`
- `view inspectors` / `manage inspectors`
- `view animal intakes` / `manage animal intakes`
- `view slaughter plans` / `manage slaughter plans`
- `view slaughter executions` / `manage slaughter executions`
- `view batches` / `manage batches`
- `view ante-mortem` / `manage ante-mortem`
- `view post-mortem` / `manage post-mortem`
- `view certificates` / `manage certificates`
- `view warehouse` / `manage warehouse`
- `view transport` / `manage transport`
- `view delivery confirmations` / `manage delivery confirmations`
- `view compliance`

**CRM & HR**

- `view employees` / `manage employees`
- `view suppliers` / `manage suppliers`
- `view contracts` / `manage contracts`
- `view clients` / `manage clients`
- `view demands` / `manage demands`
- `view crm dashboard`

**Settings**

- `view settings` / `manage settings` (units, species, etc.)

**Platform (Super Admin only)**

- `view platform` / `manage users` (optional; for future “user management” under Super Admin)

You can start with **one permission per module** (e.g. `manage facilities`) and split into view/manage later.

---

## 4. How it fits your current structure

- **Tenant middleware** stays: all tenant routes remain behind `auth` + `tenant` (data still scoped by `user()->businesses()`).
- **Ownership checks** stay: e.g. `FacilityController` still ensures `(int) $business->user_id === (int) $request->user()->id`. Roles do **not** replace that; they add “who is allowed to open this area at all.”
- **Super Admin:** Keep `is_super_admin` and either:
  - Give them a “super_admin” role with all permissions and use `Gate::before` so super admin bypasses permission checks, or
  - Don’t assign roles to them and keep using only `is_super_admin` for the Super Admin area (simplest).

---

## 5. Implementation steps (after installing Spatie)

1. **User model**  
   Add trait and optional `guard_name`:

   ```php
   use Spatie\Permission\Traits\HasRoles;

   class User extends Authenticatable
   {
       use HasRoles; // add this
       // ...
   }
   ```

2. **Super Admin bypass**  
   In `AppServiceProvider::boot()` (or a dedicated `AuthServiceProvider`):

   ```php
   use Illuminate\Support\Facades\Gate;

   Gate::before(function ($user, $ability) {
       if ($user->isSuperAdmin()) {
           return true;
       }
   });
   ```

3. **Seed default roles and permissions**  
   Create `RolesAndPermissionsSeeder` (see below): define permissions, create roles, assign permissions to roles. Example:
   - **owner:** all permissions for businesses, facilities, inspectors, intakes, slaughter, batches, certificates, warehouse, transport, delivery, compliance, employees, suppliers, contracts, clients, demands, crm, settings.
   - **manager:** same as owner except no `manage settings`.
   - **staff:** only `view_*` (or a subset you define).

4. **Assign role to users**  
   - When a user registers: `$user->assignRole('owner');` (or after they create first business).  
   - Super Admin: either `$user->assignRole('super_admin');` and use `Gate::before` as above, or leave without role and rely on `is_super_admin`.

5. **Protect routes**  
   - Option A – middleware on route groups:

     ```php
     Route::middleware(['auth', 'tenant', 'permission:manage facilities'])->group(function () {
         // facilities routes
     });
     ```

     You’d need to register the permission middleware (Spatie’s docs). Then group your existing routes by permission (e.g. “manage businesses”, “manage facilities”, …).

   - Option B – in controllers: at the top of each controller (or in `authorizeResource`), add:

     ```php
     $this->authorize('manage facilities'); // or a policy
     ```

   Start with a few key modules (e.g. facilities, businesses, settings) and then add the rest.

6. **Blade**  
   Hide or show menu items and buttons by permission:

   ```blade
   @can('manage facilities')
       <a href="...">Facilities</a>
   @endcan
   ```

   Or use `@role('owner')` if you prefer role-based menus.

---

## 6. Summary

- Use **Spatie Laravel Permission** for roles and permissions.
- Keep **tenant** scoping and **ownership** checks as they are; add **permission** checks on top.
- Keep **Super Admin** as today (`is_super_admin`) and optionally give them a “super_admin” role + `Gate::before` so they bypass permission checks.
- Define **roles**: Owner, Manager, Staff (and optionally Super Admin role).
- Define **permissions** per module (e.g. `manage businesses`, `manage facilities`, …, `manage settings`).
- Seed roles and permissions, assign **Owner** (or Manager/Staff) to tenant users, then protect routes and menus by permission.

If you tell me your exact role names and “who can do what” (e.g. “Staff can only view slaughter and certificates”), I can outline the exact permission names and a ready-to-run `RolesAndPermissionsSeeder` for your codebase.

---

## 7. What's implemented in the codebase

- **RolesAndPermissionsSeeder** defines permissions (Operations, CRM, Settings) and roles: **owner** (all), **manager** (operations + CRM), **staff** (view + limited manage). It's called from `DatabaseSeeder` before `TestDataSeeder`.
- **TestDataSeeder** assigns the **owner** role to tenant users (`test@example.com`, `tester@dayare.me`). Super Admin has no role; access is via `is_super_admin` and `Gate::before`.
- **Run locally or on cPanel:** (1) `php artisan migrate` then (2) `php artisan db:seed`. To refresh only roles: `php artisan db:seed --class=RolesAndPermissionsSeeder` then `php artisan db:seed --class=TestDataSeeder`.
- **Optional next steps:** Register Spatie's `permission` middleware and use `permission:manage facilities` etc. on route groups; use `@can('manage facilities')` in Blade; on registration call `$user->assignRole('owner');`.
