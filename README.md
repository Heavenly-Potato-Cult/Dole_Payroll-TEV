# DOLE Payroll System

A comprehensive payroll management system for **DOLE Regional Office 9**, built with Laravel 11. This system handles regular payroll computation, special payroll processing, travel expense vouchers (TEV), government remittance reports, and employee management with role-based access control.

---

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)

---

## ✨ Features

### Payroll Module

- **Regular Payroll**
  - Batch creation and computation
  - Automated deduction calculations (government contributions, loans, union dues)
  - Withholding tax computation based on BIR TRAIN Law
  - Payroll workflow: Draft → Computed → Pending Accountant → Certified → Approved → Locked
  - Individual payslip generation (PDF)

- **Special Payroll**
  - Newly hired payroll (prorated salary)
  - Salary differential processing
  - NOSI/NOSA (Not on Station/Not on Account) processing

- **Government Remittance Reports**
  - GSIS detailed and summary reports
  - Pag-IBIG (HDMF) remittance reports (P1, P2, MPL, CAL, Housing)
  - PhilHealth CSV export
  - SSS voluntary contribution reports
  - LBP loan amortization
  - CARESS union dues and mortuary contributions
  - BTR refund reports

### TEV Module

- **Travel Expense Voucher**
  - TEV request creation and approval workflow
  - Itinerary planning and per-diem computation
  - Liquidation submission and approval
  - TEV report generation (Itinerary, Travel Completed, Annex-A)

- **Office Orders**
  - Office order creation and approval
  - Integration with TEV system

### Employee Management

- Employee records with salary grade and step increments
- Division/department management
- Employee promotion history tracking
- Deduction enrollment (GSIS, PhilHealth, Pag-IBIG, loans, etc.)

### User Management

- Role-based access control (7 roles)
- User role assignment and activation
- Signatory management per role

---

## 🛠 Tech Stack

### Backend
- **Framework**: Laravel 11 (PHP 8.2)
- **Database**: MySQL
- **Authentication**: Laravel Sanctum + JWT (for HRIS SSO)
- **Authorization**: Spatie Laravel Permission
- **Architecture**: Modular Monolith (nwidart/laravel-modules)

### Key Packages
- `barryvdh/laravel-dompdf` - PDF generation
- `maatwebsite/excel` - Excel exports
- `spatie/laravel-backup` - Database backups
- `spatie/laravel-permission` - Role-based permissions
- `nwidart/laravel-modules` - Modular architecture (Payroll, TEV, Allowances modules)
- `firebase/php-jwt` - JWT token handling

### Frontend
- Blade templates with Tailwind CSS

---

## 📦 Installation

### Prerequisites
- PHP 8.2+
- Composer
- MySQL
- Node.js & NPM

### Quick Setup

```bash
# Clone the repository
git clone <repository-url>
cd Dole_Payroll

# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Configure database in .env
# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

Access the application at `http://localhost:8000`

### Default Admin Account
- Email: `admin@dole9.gov.ph`
- Password: `pass123`

**Important:** Change the default password immediately after first login.

---

## 📚 Documentation

- **Internal Documentation** - [`docs/docs.html`](docs/docs.html) - Detailed technical documentation, HRIS integration, system architecture, and turnover information
- **API Documentation** - Run `php artisan scribe:generate` to generate interactive API documentation in the `.scribe/` directory

---

## 📝 License

This project is proprietary software for DOLE Regional Office 9.

---

**Last Updated**: June 2026