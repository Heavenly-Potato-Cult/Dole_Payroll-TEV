<?php

namespace App\SharedKernel\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private string $secret;
    private string $issuer;
    private string $audience;
    private string $expiresIn;

    public function __construct()
    {
        $this->secret = config('app.jwt_secret', 'dole-payroll-native-secret-2024');
        $this->issuer = 'payroll-system';
        $this->audience = 'payroll-system';
        $this->expiresIn = '1h';
    }

    /**
     * Generate JWT token for authenticated employee.
     */
    public function generateToken(array $payload): string
    {
        $tokenPayload = array_merge([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour
        ], $payload);

        return JWT::encode($tokenPayload, $this->secret, 'HS256');
    }

    /**
     * Validate and decode JWT token.
     */
    public function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate token for employee.
     */
    public function generateEmployeeToken(\App\SharedKernel\Models\Employee $employee): string
    {
        return $this->generateToken([
            'sub' => $employee->employee_no,
            'employeeId' => $employee->employee_no,
            'name' => $employee->full_name,
            'email' => null,
            'department' => $employee->division->division_name ?? null,
            'fullProfile' => $employee->toArray(),
        ]);
    }
}
