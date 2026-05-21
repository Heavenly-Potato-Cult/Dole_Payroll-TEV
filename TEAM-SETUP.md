# Team Setup Guide — DOLE Payroll

Onboarding guide for developers using **XAMPP**, **Laravel**, and **Google Drive** database sync.

---

## Prerequisites

1. **XAMPP** (Apache + MySQL) installed and running
2. **PHP 8.2+** and **Composer**
3. **Git**
4. **Google account** with access to the shared Drive folder

---

## 1. Clone and install dependencies

```bash
git clone <repository-url>
cd Dole_Payroll
composer install
```

`npm install` is optional — the app UI is primarily Blade templates; only run it if you are working on Vite assets.

---

## 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` for local XAMPP:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dole_payroll
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

---

## 3. Database (XAMPP)

### Option A — Pull from Google Drive (recommended for team sync)

Complete the [Google Drive setup](#4-google-drive-integration-one-time) first, then:

```powershell
.\pull-from-gdrive.bat
```

This downloads `dole_payroll.sql` from Drive, recreates the `dole_payroll` database in XAMPP, and imports the dump.

### Option B — Manual setup

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Create database `dole_payroll` (phpMyAdmin or MySQL CLI).
3. If you have a local `dole_payroll.sql` file:

   ```powershell
   C:\xampp\mysql\bin\mysql.exe -u root dole_payroll < dole_payroll.sql
   ```

4. Run migrations if needed:

   ```bash
   php artisan migrate
   ```

---

## 4. Google Drive integration (one-time)

The shared Google Cloud / Drive folder is already configured by the team lead. Each developer only needs **rclone** on their machine.

### 4.1 Download rclone

```powershell
.\get-rclone.bat
```

This places `rclone.exe` in the project root. It is **not** committed to Git (large binary).

### 4.2 Configure rclone

```powershell
rclone config
```

Suggested prompts:

| Prompt | Value |
|--------|--------|
| Name | `gdrive` |
| Storage | `18` (Google Drive) |
| Scope | `1` (Full access) |
| Client ID / secret | leave blank |
| Auto config | `y` — sign in in the browser |
| Confirm | `y` |
| Quit | `q` |

`rclone.conf` is local-only (gitignored).

---

## 5. Daily database workflow

| Action | Command |
|--------|---------|
| **Get latest DB from team** | `.\pull-from-gdrive.bat` |
| **Share your DB with team** | `.\push-to-gdrive.bat` |

**Push flow:** XAMPP MySQL → `dole_payroll.sql` (project root) → Google Drive  
**Pull flow:** Google Drive → `dole_payroll.sql` → XAMPP MySQL

Coordinate with teammates before pushing so you do not overwrite someone else's work unintentionally.

---

## 6. Run the application

```bash
php artisan serve
```

Open: **http://localhost:8000**

Default admin (if seeded): see `.env` / seeder — commonly `admin@dole9.gov.ph` after fresh seed.

---

## 7. HRIS simulation (optional, local dev)

For SSO testing without a real HRIS:

```bash
cd HRIS
npm install
npm start
```

HRIS runs on **http://localhost:3001**. Payroll accepts JWT at `/hris-auth?token=...`.

---

## Troubleshooting

| Issue | What to check |
|-------|----------------|
| Push/pull fails | `rclone.exe` exists; `rclone config` remote named `gdrive` |
| MySQL import fails | XAMPP MySQL running; path `C:\xampp\mysql\bin\mysql.exe` matches your install |
| App 500 after pull | `php artisan config:clear`; `.env` DB name `dole_payroll` |
| Missing tables | Run `php artisan migrate` after import if schema is newer than dump |

---

## Related documentation

- [`README.md`](README.md) — Features, architecture, roles
- [`SYSTEM-GAP-ANALYSIS.md`](SYSTEM-GAP-ANALYSIS.md) — Known gaps and roadmap
