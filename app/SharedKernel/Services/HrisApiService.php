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
        return [
            'employee_id'       => $employeeNo,
            'cutoff_start'      => $cutoffStart,
            'cutoff_end'        => $cutoffEnd,
            'days_present'      => 11.0,
            'lwop_days'         => 0.0,
            'late_minutes'      => 0,
            'undertime_minutes' => 0,
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
}