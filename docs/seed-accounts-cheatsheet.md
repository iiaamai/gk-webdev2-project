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

Source of truth in code: `database/seeders/SeedAccounts.php` and `docs/development/seed_plan.txt`.