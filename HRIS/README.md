# HRIS Simulation Server

This is a simulation of the HRIS (Human Resource Information System) that provides JWT-based single sign-on (SSO) to the Payroll and TEV systems.

## Setup

1. Install dependencies:
```bash
cd HRIS
npm install
```

2. Start the HRIS server:
```bash
npm start
```

The server will run on `http://localhost:3001`

## Features

- **Employee Login**: Login with Employee ID (EMP001-EMP082) and password (pass123)
- **JWT Generation**: Generates signed JWT tokens for SSO
- **Navigation**: Links to Payroll and TEV systems
- **Dummy Data**: Includes 82 employees with full profiles and attendance records

## Usage

1. Access the HRIS portal at `http://localhost:3001`
2. Login with any employee ID (e.g., EMP001) and password (pass123)
3. Click "Open Payroll" or "Open TEV" to navigate to the respective systems
4. The JWT token is passed via URL query parameter and validated by the Laravel application

## JWT Configuration

The JWT configuration is in `config.js`:
- Secret: `dole-hris-payroll-shared-secret-2024`
- Expiration: 1 hour
- Issuer: `hris-system`
- Audience: `payroll-system`

## Laravel Integration

The Laravel application receives the JWT token at:
- Payroll: `http://localhost:8000/hris-auth?token={jwt}`
- TEV: `http://localhost:8000/tev-hris-auth?token={jwt}`

The JWT middleware (`app/Http/Middleware/JwtAuth.php`) validates the token and stores user data in session.
