// ============================================================
//  SHARED CONFIG — HRIS and Payroll must use the same secret
//  In production: store this in environment variables (.env)
// ============================================================

const JWT_CONFIG = {
  secret: "dole-hris-payroll-shared-secret-2024",
  expiresIn: "1h",
  issuer: "hris-system",
  audience: "payroll-system",
};

module.exports = { JWT_CONFIG };
