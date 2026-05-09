// ============================================================
//  HRIS SERVER (Port 3001)
//  Simulates HRIS system with navigation to Payroll | TEV
//  Responsibilities:
//    - Employee login (username/password)
//    - Generate signed JWT
//    - Redirect to Payroll/TEV with the token
// ============================================================

const express = require("express");
const jwt = require("jsonwebtoken");
const { JWT_CONFIG } = require("./config");
const { HRIS_EMPLOYEES, employees, attendanceRecords } = require("./data");

const app = express();
const PORT = 3001;

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// ── LOGIN PAGE ────────────────────────────────────────────────
app.get("/", (req, res) => {
  res.send(/* html */`
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>HRIS — Login</title>
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
          font-family: 'Segoe UI', sans-serif;
          background: linear-gradient(135deg, #1a3c5e 0%, #2d6a9f 100%);
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        .card {
          background: white;
          border-radius: 12px;
          padding: 40px;
          width: 500px;
          box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo { text-align: center; margin-bottom: 28px; }
        .logo h1 { color: #1a3c5e; font-size: 26px; font-weight: 700; }
        .logo p  { color: #888; font-size: 13px; margin-top: 4px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #444; margin-bottom: 6px; }
        input {
          width: 100%;
          padding: 10px 14px;
          border: 1.5px solid #ddd;
          border-radius: 8px;
          font-size: 14px;
          margin-bottom: 18px;
          transition: border-color 0.2s;
        }
        input:focus { outline: none; border-color: #2d6a9f; }
        button {
          width: 100%;
          padding: 12px;
          background: #1a3c5e;
          color: white;
          border: none;
          border-radius: 8px;
          font-size: 15px;
          font-weight: 600;
          cursor: pointer;
          transition: background 0.2s;
        }
        button:hover { background: #2d6a9f; }
        .hint {
          margin-top: 20px;
          padding: 12px;
          background: #f0f7ff;
          border-radius: 8px;
          font-size: 12px;
          color: #555;
        }
        .hint b { display: block; margin-bottom: 6px; color: #1a3c5e; }
        .hint span { display: block; margin: 2px 0; font-family: monospace; }
        .error {
          background: #fff0f0;
          color: #c0392b;
          padding: 10px 14px;
          border-radius: 8px;
          font-size: 13px;
          margin-bottom: 16px;
          border-left: 3px solid #e74c3c;
        }
      </style>
    </head>
    <body>
      <div class="card">
        <div class="logo">
          <h1>DOLE HRIS Portal</h1>
          <p>Department of Labor and Employment - HRIS</p>
        </div>

        ${req.query.error ? `<div class="error">❌ ${req.query.error}</div>` : ""}

        <form method="POST" action="/login">
          <label>Employee ID</label>
          <input type="text" name="employeeId" placeholder="e.g. EMP001" required>

          <label>Password</label>
          <input type="password" name="password" placeholder="password (use: pass123)" required>

          <button type="submit">Login to HRIS →</button>
        </form>

        <div class="hint">
          <b>Demo Accounts (password: pass123)</b>
          <span>EMP032 — Divina Torres. Corpuz (Payroll Officer)</span>
          <span>EMP076 — Jose Torres. Torres (HRMO)</span>
          <span>EMP052 — Ricardo Manalo. Macapagal (Accountant)</span>
          <span>EMP077 — Teresita Aguilar. Tolentino (ARD)</span>
          <span>EMP003 — Maricel Garcia. Ocampo (Cashier)</span>
          <span>Use any EMP001-EMP082 for testing</span>
        </div>
      </div>
    </body>
    </html>
  `);
});

// ── PROCESS LOGIN ─────────────────────────────────────────────
app.post("/login", (req, res) => {
  const { employeeId, password } = req.body;

  // 1. Find employee in HRIS
  const employee = HRIS_EMPLOYEES.find(e => e.id === employeeId);

  // 2. Validate (demo: everyone uses "pass123")
  if (!employee || password !== "pass123") {
    return res.redirect("/?error=Invalid+Employee+ID+or+password");
  }

  // 3. ✅ HRIS Dashboard — employee is now logged in to HRIS
  res.send(/* html */`
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>HRIS — Dashboard</title>
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f7fa; }
        .navbar {
          background: #1a3c5e;
          color: white;
          padding: 14px 30px;
          display: flex;
          justify-content: space-between;
          align-items: center;
        }
        .navbar h1 { font-size: 18px; }
        .navbar span { font-size: 13px; opacity: 0.85; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .welcome {
          background: white;
          border-radius: 12px;
          padding: 28px;
          margin-bottom: 24px;
          box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .welcome h2 { color: #1a3c5e; margin-bottom: 6px; }
        .welcome p  { color: #666; font-size: 14px; }
        .apps { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; }
        .app-card {
          background: white;
          border-radius: 12px;
          padding: 24px;
          text-align: center;
          box-shadow: 0 2px 8px rgba(0,0,0,0.07);
          border: 2px solid transparent;
          transition: all 0.2s;
        }
        .app-card:hover { border-color: #2d6a9f; transform: translateY(-2px); }
        .app-card .icon { font-size: 36px; margin-bottom: 10px; }
        .app-card h3 { font-size: 14px; color: #333; margin-bottom: 4px; }
        .app-card p  { font-size: 12px; color: #999; }
        .action-btns {
          margin-top: 14px;
          display: flex;
          gap: 8px;
          justify-content: center;
          flex-wrap: wrap;
        }
        .btn {
          display: inline-block;
          padding: 8px 18px;
          color: white;
          border-radius: 6px;
          text-decoration: none;
          font-size: 13px;
          font-weight: 600;
          transition: background 0.2s;
        }
        .btn-payroll { background: #27ae60; }
        .btn-payroll:hover { background: #219a52; }
        .btn-tev { background: #e67e22; }
        .btn-tev:hover { background: #d35400; }
        .token-box {
          margin-top: 24px;
          background: #1e2d3d;
          color: #a8d8a8;
          border-radius: 10px;
          padding: 20px;
          font-family: monospace;
          font-size: 12px;
          word-break: break-all;
        }
        .token-box .label { color: #7ec8e3; margin-bottom: 8px; font-weight: bold; font-size: 13px; }
        .token-box .sub   { color: #888; margin-top: 10px; font-size: 11px; }
      </style>
    </head>
    <body>
      <div class="navbar">
        <h1>🏢 DOLE HRIS Portal</h1>
        <span>Logged in as: ${employee.name} (${employee.id})</span>
      </div>
      <div class="container">
        <div class="welcome">
          <h2>Good day, ${employee.name}! 👋</h2>
          <p>Department: ${employee.department} &nbsp;|&nbsp; Employee ID: ${employee.id}</p>
        </div>

        <div class="apps">
          <div class="app-card">
            <div class="icon">📋</div>
            <h3>Leave Requests</h3>
            <p>File and track your leave</p>
          </div>
          <div class="app-card">
            <div class="icon">🕐</div>
            <h3>Attendance</h3>
            <p>View your time records</p>
          </div>
          <div class="app-card">
            <div class="icon">💰</div>
            <h3>Payroll System</h3>
            <p>View payslips & manage payroll</p>
            <div class="action-btns">
              <a class="btn btn-payroll" href="/launch-payroll/${employee.id}">
                Open Payroll →
              </a>
            </div>
          </div>
          <div class="app-card">
            <div class="icon">📊</div>
            <h3>TEV System</h3>
            <p>Travel Expense & Voucher</p>
            <div class="action-btns">
              <a class="btn btn-tev" href="/launch-tev/${employee.id}">
                Open TEV →
              </a>
            </div>
          </div>
          <div class="app-card">
            <div class="icon">👥</div>
            <h3>Performance</h3>
            <p>KPI and evaluations</p>
          </div>
        </div>

        <!-- DEBUG: Show the JWT so you can see what's being sent -->
        <div class="token-box" id="tokenBox">
          <div class="label">🔑 JWT Token being sent to Payroll (debug view)</div>
          <div id="tokenContent">Loading...</div>
          <div class="sub">This token is generated by HRIS and passed to Payroll via URL. Payroll validates it without asking HRIS again.</div>
        </div>
      </div>

      <script>
        // Fetch the token for display purposes only
        fetch('/get-token/${employee.id}')
          .then(r => r.json())
          .then(d => {
            document.getElementById('tokenContent').textContent = d.token;
          });
      </script>
    </body>
    </html>
  `);
});

// ── GENERATE TOKEN (for display in dashboard) ─────────────────
app.get("/get-token/:employeeId", (req, res) => {
  const employee = HRIS_EMPLOYEES.find(e => e.id === req.params.employeeId);
  if (!employee) return res.json({ error: "Not found" });

  const token = generateToken(employee);
  res.json({ token });
});

// ── LAUNCH PAYROLL — generate JWT and redirect ────────────────
app.get("/launch-payroll/:employeeId", (req, res) => {
  const employee = HRIS_EMPLOYEES.find(e => e.id === req.params.employeeId);
  if (!employee) return res.status(404).send("Employee not found");

  // 🔐 Generate JWT — signed with shared secret
  const token = generateToken(employee);

  // Redirect to Laravel Payroll system with JWT in URL query param
  // Assuming Laravel runs on port 8000 (default for php artisan serve)
  res.redirect(`http://localhost:8000/hris-auth?token=${token}`);
});

// ── LAUNCH TEV — generate JWT and redirect ────────────────────
app.get("/launch-tev/:employeeId", (req, res) => {
  const employee = HRIS_EMPLOYEES.find(e => e.id === req.params.employeeId);
  if (!employee) return res.status(404).send("Employee not found");

  // 🔐 Generate JWT — signed with shared secret
  const token = generateToken(employee);

  // Redirect to Laravel TEV system with JWT in URL query param
  // For now, redirect to same system with different route
  res.redirect(`http://localhost:8000/tev-hris-auth?token=${token}`);
});

// ── JWT GENERATION HELPER ─────────────────────────────────────
function generateToken(employee) {
  const payload = {
    // Standard JWT claims
    sub: employee.id,
    iss: JWT_CONFIG.issuer,
    aud: JWT_CONFIG.audience,

    // Custom claims — identity info from HRIS
    employeeId: employee.id,
    name: employee.name,
    email: employee.email,
    department: employee.department,

    // Include full profile data for the Laravel system
    fullProfile: employee.fullProfile,
  };

  return jwt.sign(payload, JWT_CONFIG.secret, {
    expiresIn: JWT_CONFIG.expiresIn,
  });
}

// ── START SERVER ──────────────────────────────────────────────
app.listen(PORT, () => {
  console.log(`\n✅ HRIS Server running at http://localhost:${PORT}`);
  console.log(`   → Handles employee login and JWT generation`);
  console.log(`   → Navigation: Payroll | TEV\n`);
});
