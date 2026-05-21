# DOLE Payroll System

Payroll management for **DOLE Regional Office 9** — regular payroll, special payroll, travel expense vouchers (TEV), government remittance reports, and employee management with role-based access control.

**Stack:** Laravel 11 · PHP 8.2 · MySQL · Spatie Permission · DomPDF · Maatwebsite Excel

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Installation](#installation)
- [Database sync (Google Drive)](#database-sync-google-drive)
- [HRIS integration](#hris-integration)
- [User roles](#user-roles)
- [Known issues](#known-issues)
- [Project structure](#project-structure)
- [Documentation](#documentation)

---

## Features

### Payroll

- Payroll batch creation, attendance pull, and computation
- Workflow: Draft → Computed → Pending Accountant → Pending RD → Released → **Locked**
- Payslips (PDF), payroll register, GSIS/HDMF and other remittance reports
- Employee deductions (GSIS, PhilHealth, Pag-IBIG, loans, union dues, etc.)

### Special payroll

- Newly hired (prorated)
- Salary differential
- NOSI/NOSA

### TEV (Travel & Expense Voucher)

- Request workflow, itinerary, liquidation
- Printable reports (Appendix A, travel completed, Annex A, liquidation DV)

### Other

- Employees, divisions, office orders
- User and signatory management
- HRIS SSO (JWT) for employees

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 11, PHP 8.2 |
| Database | MySQL (XAMPP locally) |
| Auth | Session + Sanctum; HRIS JWT SSO |
| Authorization | Spatie Laravel Permission |
| PDF / Excel | DomPDF, Maatwebsite Excel |
| Modules | `nwidart/laravel-modules` — **Payroll**, **Tev** |

---

## Architecture

Modular Laravel app:

```
routes/web.php
  └── Modules/Payroll/   — payroll, employees, reports, special payroll
  └── Modules/Tev/       — TEV, office orders
  └── app/               — auth, shared kernel, HRIS bridge
```

Business logic lives in **Services** under each module (e.g. `PayrollComputationService`, `TevComputationService`, `DeductionService`).

Enabled modules: `modules_statuses.json` (`Payroll`, `Tev`).

---

## Installation

**Supported local setup:** XAMPP + Composer + `php artisan serve`.  
Docker and legacy setup scripts have been removed from the repo.

### Requirements

- XAMPP (or MySQL 8 + PHP 8.2)
- Composer
- Git

### Steps

```bash
git clone <repository-url>
cd Dole_Payroll
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` for MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dole_payroll
DB_USERNAME=root
DB_PASSWORD=
```

**Database:** use [`pull-from-gdrive.bat`](#database-sync-google-drive) (team dump) or create `dole_payroll` and import `dole_payroll.sql` manually, then:

```bash
php artisan migrate   # if schema is ahead of the dump
php artisan serve
```

App URL: **http://localhost:8000**

Full onboarding: **[`TEAM-SETUP.md`](TEAM-SETUP.md)**

---

## Database sync (Google Drive)

Team database sharing uses **rclone** and two scripts in the project root:

| Script | Purpose |
|--------|---------|
| `get-rclone.bat` | One-time download of `rclone.exe` |
| `pull-from-gdrive.bat` | Drive → `dole_payroll.sql` → XAMPP MySQL |
| `push-to-gdrive.bat` | XAMPP MySQL → `dole_payroll.sql` → Drive |

`dole_payroll.sql` and `rclone.exe` are gitignored.

One-time rclone config: see **TEAM-SETUP.md**.

---

## HRIS integration

- Employee SSO via JWT (`/hris-auth`, `/tev-hris-auth`)
- Attendance API for payroll computation (integration may still be stubbed — see gap analysis)

**Local HRIS simulator:** `HRIS/` (Node.js, port 3001). See `HRIS/README.md` if present.

---

## User roles

| Role | Typical access |
|------|----------------|
| `payroll_officer` | Payroll, employees, reports |
| `hrmo` | Employees, payroll review, TEV |
| `accountant` | Certify payroll, TEV, reports |
| `ard` | Approve payroll / TEV |
| `chief_admin_officer` | Approvals, reports |
| `cashier` | Release, liquidation |
| `budget_officer` | Office orders, TEV |
| `super_admin` | Full access |

Employees (HRIS login) use **My Payslip** and **My TEV** with limited scope.

---

## Known issues

See **[`SYSTEM-GAP-ANALYSIS.md`](SYSTEM-GAP-ANALYSIS.md)** for the full backlog (G-01–G-15).

**Highlights:**

| ID | Topic | Status |
|----|--------|--------|
| G-01 | Missing `ReportController` methods | **Resolved** — reports in `PayrollReportController` / `TevReportController` |
| G-02 | HRIS attendance stubbed | Open |
| G-03 | YTD gross / withholding tax | Open |
| G-04–G-15 | Exports, tests, policies, UI stubs, architecture | See gap doc |

---

## Project structure

```
Dole_Payroll/
├── app/                    # Auth, SharedKernel, policies
├── Modules/
│   ├── Payroll/            # Payroll module (controllers, views, exports)
│   └── Tev/                # TEV module
├── database/               # Migrations, seeders
├── routes/web.php          # Main routes + report routes
├── HRIS/                   # Optional HRIS simulator
├── public/                 # Web root
├── .env.example
├── composer.json
├── modules_statuses.json
├── get-rclone.bat
├── pull-from-gdrive.bat
├── push-to-gdrive.bat
├── README.md               # This file
├── TEAM-SETUP.md           # Developer onboarding
└── SYSTEM-GAP-ANALYSIS.md  # Gap analysis & roadmap
```

---

## Documentation

| File | Purpose |
|------|---------|
| [`TEAM-SETUP.md`](TEAM-SETUP.md) | Clone, `.env`, XAMPP, Google Drive push/pull |
| [`SYSTEM-GAP-ANALYSIS.md`](SYSTEM-GAP-ANALYSIS.md) | Technical gaps and phased roadmap |
| [`docs.html`](docs.html) | Static HTML reference (optional) |

---

## Common commands

```bash
php artisan serve
php artisan migrate
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

```powershell
.\pull-from-gdrive.bat
.\push-to-gdrive.bat
```

---

## License

Proprietary — DOLE Regional Office 9.

**Last updated:** May 2026
