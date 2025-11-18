# Changelog

## 2025-11-15

- Refined the guest Rooms page into a unified, boxed layout with live filters, richer room cards (images, amenities, pricing), and an inline two-step booking panel that checks availability/pricing per room, suggests alternatives, and pre-fills guest details before posting directly to the reservation flow.
- Added `/booking/check` endpoint plus controller logic that validates stay dates, enforces tenant-aware availability, recalculates nightly promos, and responds with pricing + suggestion data for the new booking panel.
- Hardened the booking submission to auto-calculate totals server-side, capture special requests, and block stale room assignments, ensuring staff dashboards receive accurate folio-ready reservations.
- Reset local MariaDB schema for clean multi-tenant bootstrap (drop/recreate `hotela`, reran migrations/seeders) per tenant onboarding plan.
- Made additive migrations idempotent (`check_in_status`, `room_status`, `avg_cost`) using `ADD COLUMN IF NOT EXISTS` so repeated runs succeed after schema resets.
- Cleaned `scripts/seed.php` artifacts and reran full seeding to repopulate demo tenant data without null `tenant_id` conflicts.
- Delivered role-based dashboard revamp: added `DashboardRepository` metrics layer (occupancy, revenue, arrivals, folio balances, POS sales, low-stock) plus widget-ready data service, refreshed admin/director/operations/finance/cashier/service dashboards, and added reusable condensed list styles for consistent UI.
- Rebuilt guest homepage hero/welcome shells with centered boxed design, widened hero to 90vw, refreshed CTA trio, and added responsive mobile nav behavior.
- Swapped home “Featured Rooms” block for room-type cards that deep-link into the rooms catalogue; updated rooms page with type filters, centered heading, and repository support for filtering.
- Made guest booking/order flows account-free: removed portal requirement, added contact-detail capture with client-side validation, auto-create/update guest sessions after checkout, and enhanced food menu filters (category search, price, vegetarian) with live filtering.

## 2025-11-14

- Documented high-level Hotela architecture, modules, workflows, and MVP roadmap.
- Scaffolded bootstrap, routing, base controllers/views, and public website landing shell with responsive styling.
- Fixed router base-path detection so `/Hotela/public` resolves homepage correctly.
- Added dynamic asset helper so CSS/JS load when app runs from subdirectories.
- Integrated new Hotela logo into header with brand styling.
- Added configurable Admin Settings hub with tabbed UI, backed by JSON store and SettingsController for real-time updates.
- Added Apache rewrite rules so `/admin/*` routes resolve via index, and improved asset helper to avoid duplicate paths.
- Introduced role-aware dashboards with dedicated controller, layout, and views for director, admin, management, and frontline teams, including permissions scaffolding.
- Added Tech dashboard role covering systems health, backups, and log monitoring.
- Introduced PDO database layer, migrations, and seed scripts for users/roles/settings to kick off dynamic, testable development.
- Implemented booking engine foundations: room/room-type/reservation tables, seed data, public booking form with availability logic, and staff booking dashboard.
- Added housekeeping notification pipeline: notifications table/service, booking-triggered alerts, and live feed on housekeeping dashboard.
- Added authentication system with login/logout, role guards, and protected dashboards/settings routes.
- Implemented PMS check-in/out foundation: folios, folio entries, reservation status tracking, guarded staff actions, and housekeeping alerts on departures.
- Expanded folio UI with line items, manual charges/payments, automated room-status transitions, real-time housekeeping board, and booking calendar with room assignment tools.
- Added guest portal login flow with dashboard access, booking/order gating, and sticky public navigation with portal shortcuts.
- Delivered POS MVP: categories/items/tills schema, seeding, POS console with cart + payments (cash/room/etc.), sales storage, folio posting for room charges, and finance notifications.
- Kicked off POS ↔ Inventory integration: inventory schema with locations/levels/movements, POS item recipes, seeded stock, POS sales now deduct inventory and log movements with location selection.
- Added low-stock detection with role notifications, requisition → PO → goods receipt workflow screens, inventory valuation surfaced on dashboards, and refreshed home page/role dashboards to showcase the unified platform.
- Launched admin-driven guest website: new public layout + pages (Home, Rooms, Drinks & Food, About, Contact, Order) with hero highlights, menu data, and settings-powered branding/SEO/banners/page toggles.
- Split routing into tenant guest site vs. platform site: tenant domains now load only public pages via `routes/tenant.php`, while platform domain loads staff/sysadmin routes via `routes/platform.php`, with automatic auth enforcement on protected prefixes. Added `config/domains.php` to define platform host detection.
- Started multi-tenant foundation: introduced `tenants` table + resolver, tenant-aware settings loader, scoped repositories (rooms, room types, reservations, POS items) to current tenant, and updated seed/migration scripts so rooms/reservations/POS/inventory data store `tenant_id`. Added `Tenant` helper, `TenantRepository`, and domain-based tenant bootstrapping in `public/index.php`.
