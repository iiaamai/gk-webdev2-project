# GK Trucking Services — Seed account cheatsheet

Use after **`php artisan migrate:fresh --seed`** (local or presentation). All accounts are email-verified and ready to sign in at **`/login`**.

| Role | Name | Email | Password |
|------|------|-------|----------|
| System Admin | Carlos Reyes | `carlos.reyes@gklogistics.ph` | `admin123` |
| Staff | Ana Mendoza | `ana.mendoza@gklogistics.ph` | `staff123` |
| Customer | Maria Santos | `maria.santos@gklogistics.ph` | `password123` |
| Customer | James Lim | `james.lim@gklogistics.ph` | `password123` |
| Driver | Reynaldo Cruz | `reynaldo.cruz@gklogistics.ph` | `password123` |
| Driver | Mark Villanueva | `mark.villanueva@gklogistics.ph` | `password123` |

## Driver fleet (seed)

| Driver | Assigned plate | Vehicle type |
|--------|----------------|--------------|
| Reynaldo Cruz | ISU-4X2-001 | 6-wheeler |
| Mark Villanueva | ISU-6X4-001 | 10-wheeler |

Drivers are **timed in** by `DriverAttendanceSeeder` so they can use the driver portal immediately.

## Quick demo logins

| Scenario | Use this account |
|----------|------------------|
| Admin overview, fleet, pricing, earnings, reports | Carlos Reyes |
| Staff bookings / gatepass | Ana Mendoza |
| Customer bookings & invoices | Maria Santos |
| Second customer (historical trips) | James Lim |
| Active 6-wheeler trip | Reynaldo Cruz |
| Active 10-wheeler trip | Mark Villanueva |

## Admin earnings (seed history)

**Where:** log in as **Carlos Reyes** → **Earnings**. Use the **year** (2020–current year) and **month** filters.

Earnings count **completed** bookings with invoices, bucketed by **`accepted_at`** (`HistoricalBookingSeeder` plus presentation `GK-{year}-0005` / `0007` / `0008` / `0009`).

### Volume by year (graduated seed)

Every month **Jan–Dec** from **2020** through the current year has multiple completed trips (spread across different days for daily charts):

| Years | Completed trips per month (approx.) |
|-------|-------------------------------------|
| **2020–2022** | **6** |
| **2023–2024** | **10** |
| **2025–current year** | **15** |

Plus presentation trips near “today” in the current year (`BookingSeeder`).

**Good picks for charts:** **Mar 2022** (6 trips/mo), **Jun 2024** (10 trips/mo), **Oct 2025** or **Oct 2026** (15 trips/mo — busiest daily bars).

### Live presentation trips (current year)

After seed, these sit near “today” (relative dates in `BookingSeeder`):

| Booking | Role in demo |
|---------|----------------|
| `GK-{current year}-0005` | Recently completed (paid) |
| `GK-{current year}-0007` | Completed ~prior month |
| `GK-{current year}-0008` | Completed ~2 months back (unpaid invoice) |
| `GK-{current year}-0009` | Completed ~recent weeks (paid) |

Match **Earnings** to those trips’ **accepted** months, or open **Bookings** and filter completed.

**Revenue amounts:** ₱14,500 / ₱15,000 / ₱25,000 (~70% historical invoices paid).

**Refresh data:** `php artisan migrate:fresh --seed` (local) or Render redeploy with **`RUN_DB_SEED=true`** (same Postgres).

Source of truth in code: `database/seeders/SeedAccounts.php` and `docs/development/seed_plan.txt`.