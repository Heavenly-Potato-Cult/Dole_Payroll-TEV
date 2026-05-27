<?php

namespace App\SharedKernel\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HrisApiService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.hris.url', ''), '/');
        $this->apiKey  = config('services.hris.key', '');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Employees
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Fetch all employees from HRIS API.
     * Dummy API: GET /employees → { total: 82, data: [...] }
     */
    public function fetchEmployees(): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->get("{$this->baseUrl}/employees");

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            Log::warning('HRIS API employees non-200', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('HRIS API employees error', ['error' => $e->getMessage()]);
        }

        return $this->mockEmployees();
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Attendance
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Fetch attendance for one employee for a cut-off period.
     *
     * Dummy API: GET /attendance?employee_id=EMP-0001&cutoff_start=YYYY-MM-DD&cutoff_end=YYYY-MM-DD
     * Returns an ARRAY of records (not a single object), filtered by the params above.
     * We take the first matching record.
     *
     * Real HRIS API (Ma'am Eden's) will use the same shape once integrated.
     *
     * @param  string $employeeNo   employee_no value (e.g. "EMP-0001")
     * @param  string $cutoffStart  "YYYY-MM-DD"
     * @param  string $cutoffEnd    "YYYY-MM-DD"
     * @return array  Single attendance record or perfect-attendance fallback
     */
    public function fetchAttendance(
        string $employeeNo,
        string $cutoffStart,
        string $cutoffEnd
    ): array {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->get("{$this->baseUrl}/attendance", [
                    'employee_id'  => $employeeNo,   // dummy API param name
                    'cutoff_start' => $cutoffStart,
                    'cutoff_end'   => $cutoffEnd,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Dummy API returns an ARRAY of records — take the first match.
                // If the API returns a single object instead, handle both shapes.
                if (is_array($data) && isset($data[0])) {
                    return $data[0];
                }

                // Single-object response (future real API may do this)
                if (is_array($data) && isset($data['employee_id'])) {
                    return $data;
                }

                // No matching record — employee had no attendance data for this period
                Log::info('HRIS attendance: no record found', [
                    'employee_no'  => $employeeNo,
                    'cutoff_start' => $cutoffStart,
                    'cutoff_end'   => $cutoffEnd,
                ]);
            } else {
                Log::warning('HRIS API attendance non-200', [
                    'employee_no' => $employeeNo,
                    'status'      => $response->status(),
                    'body'        => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('HRIS API attendance error', [
                'employee_no' => $employeeNo,
                'error'       => $e->getMessage(),
            ]);
        }

        // Fall back to perfect attendance so payroll can still run.
        // pullForBatch() logs a warning for each fallback so HR can investigate.
        Log::warning('HRIS attendance fallback: using perfect attendance', [
            'employee_no' => $employeeNo,
        ]);

        return $this->perfectAttendance($employeeNo, $cutoffStart, $cutoffEnd);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Fallbacks / Mocks
    // ═══════════════════════════════════════════════════════════════════

    private function perfectAttendance(string $employeeNo, string $cutoffStart, string $cutoffEnd): array
    {
        // Generate raw daily logs for the fallback period
        $dailyLogs = [];
        $currentDate = strtotime($cutoffStart);
        $endDate = strtotime($cutoffEnd);

        while ($currentDate <= $endDate) {
            $dateStr = date('Y-m-d', $currentDate);
            $dayOfWeek = date('N', $currentDate); // 1 (Monday) to 7 (Sunday)

            // Only generate logs for workdays (Monday-Friday)
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $dailyLogs[] = [
                    'date' => $dateStr,
                    'am' => [
                        'in' => '07:55:00',  // 5 minutes early
                        'out' => '12:00:00',
                    ],
                    'pm' => [
                        'in' => '13:00:00',
                        'out' => '17:00:00',
                    ],
                ];
            }

            $currentDate = strtotime('+1 day', $currentDate);
        }

        return [
            'user_id'       => $employeeNo,
            'daily_logs'    => $dailyLogs,
            'leave_credits' => 15.0,
        ];
    }

    private function mockEmployees(): array
    {
        return [
            [
                'employee_id'               => 'EMP001',
                'employee_no'               => 'EMP-0001',
                'last_name'                 => 'SANTOS',
                'first_name'                => 'MARIA',
                'middle_name'               => 'REYES',
                'position_title'            => 'Administrative Aide IV',
                'plantilla_item_no'         => 'DOLE9-001',
                'salary_grade'              => 4,
                'step'                      => 1,
                'basic_monthly_salary'      => 14993.00,
                'division_code'             => 'IMSD',
                'division_name'             => 'Internal Management Services Division',
                'employment_status'         => 'permanent',
                'official_station'          => 'DOLE RO9 - Zamboanga City',
                'date_original_appointment' => '2015-06-01',
                'last_promotion_date'       => '2020-01-15',
                'gsis_bp_no'                => 'GSIS-001',
                'gsis_crn'                  => 'CRN-001',
                'pagibig_mid_no'            => 'PAGIBIG-001',
                'philhealth_no'             => 'PH-001',
                'tin'                       => '111-222-333-000',
            ],
        ];
    }


    /**
     * Fetch ALL employees' attendance for a cut-off period in ONE request.
     * Returns records keyed by employee_id (e.g. "EMP001").
     */
    public function fetchAttendanceBulk(string $cutoffStart, string $cutoffEnd): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->get("{$this->baseUrl}/attendance", [
                    'cutoff_start' => $cutoffStart,
                    'cutoff_end'   => $cutoffEnd,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (is_array($data) && ! empty($data)) {
                    return collect($data)->keyBy('employee_id')->toArray();
                }

                Log::warning('HRIS bulk attendance: empty response', [
                    'cutoff_start' => $cutoffStart,
                    'cutoff_end'   => $cutoffEnd,
                ]);
            } else {
                Log::warning('HRIS API bulk attendance non-200', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('HRIS API bulk attendance error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Office Orders
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Fetch all approved Office Orders from Employee API.
     * Employee API: GET /office-orders → { total: 164, data: [...] }
     */
    public function fetchOfficeOrders(): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->get("{$this->baseUrl}/office-orders");

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            Log::warning('Employee API office-orders non-200', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Employee API office-orders error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Leave Credits
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Fetch leave credits balance for a specific employee from HRIS API.
     * HRIS API: GET /employees/{employee_no}/leave-credits → { leave_credits_balance: 15.5 }
     *
     * @param  string $employeeNo  employee_no value (e.g. "EMP-0001")
     * @return float  Leave credits balance
     */
    public function fetchLeaveCredits(string $employeeNo): float
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->get("{$this->baseUrl}/employees/{$employeeNo}/leave-credits");

            if ($response->successful()) {
                $data = $response->json();
                return (float) ($data['leave_credits_balance'] ?? 0.0);
            }

            Log::warning('HRIS API leave-credits non-200', [
                'employee_no' => $employeeNo,
                'status'      => $response->status(),
                'body'        => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('HRIS API leave-credits error', [
                'employee_no' => $employeeNo,
                'error'       => $e->getMessage(),
            ]);
        }

        return 0.0;
    }
}