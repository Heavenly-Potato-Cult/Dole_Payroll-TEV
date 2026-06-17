<?php

namespace App\SharedKernel\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HrisApiService
{
    private string $baseUrl;
    private string $apiKey;
    private bool $useDummy;
    private ?string $authToken = null;

    public function __construct()
    {
        $this->useDummy = config('services.hris.use_dummy', true);
        
        if ($this->useDummy) {
            $this->baseUrl = rtrim(config('services.hris.url', ''), '/');
            $this->apiKey  = config('services.hris.key', '');
        } else {
            $this->baseUrl = rtrim(config('services.hris.base_url', ''), '/');
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Employees
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Authenticate with HRIS API and get bearer token.
     * Returns token string or null on failure.
     */
    private function authenticate(): ?string
    {
        $loginUrl = config('services.hris.login_url');
        $username = config('services.hris.username');
        $password = config('services.hris.password');
        $deviceName = config('services.hris.device_name');

        Log::info('HRIS authenticate() called', [
            'login_url' => $loginUrl,
            'username' => $username,
            'device_name' => $deviceName,
        ]);

        try {
            $response = Http::timeout(30)->post($loginUrl, [
                'username' => $username,
                'password' => $password,
                'device_name' => $deviceName,
            ]);

            Log::info('HRIS authenticate() response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Extract token from response - adjust based on actual API response structure
                $token = $data['token'] ?? $data['access_token'] ?? $data['api_token'] ?? null;

                if ($token) {
                    Log::info('HRIS API authentication successful', [
                        'token_extracted' => substr($token, 0, 20) . '...',
                    ]);
                    return $token;
                }

                Log::warning('HRIS API authentication: token not found in response', [
                    'response' => $data,
                ]);
            } else {
                Log::warning('HRIS API authentication failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('HRIS API authentication error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Fetch all employees from HRIS API.
     *
     * Old API format: GET /employees → { total: 82, data: [...] }
     * New API format: GET /employees → { success: true, data: { current_page: 1, data: [...] } }
     *
     * Handles pagination by fetching all pages if the new format is used.
     */
    public function fetchEmployees(): array
    {
        try {
            Log::info('HRIS fetchEmployees() called', [
                'use_dummy' => $this->useDummy,
                'base_url' => $this->baseUrl,
            ]);

            $allEmployees = [];
            $currentPage = 1;

            // Get authentication token if not using dummy API
            if (!$this->useDummy) {
                $this->authToken = $this->authenticate();
                if (!$this->authToken) {
                    Log::error('HRIS API: failed to authenticate');
                    return [];
                }
            }

            $employeesUrl = $this->useDummy
                ? "{$this->baseUrl}/employees"
                : config('services.hris.employees_url', "{$this->baseUrl}/employees");

            Log::info('HRIS fetchEmployees() URL determined', [
                'employees_url' => $employeesUrl,
                'use_dummy' => $this->useDummy,
            ]);

            do {
                $http = $this->useDummy 
                    ? Http::withToken($this->apiKey)
                    : Http::withToken($this->authToken);

                // Retry logic for failed requests
                $maxRetries = 3;
                $attempt = 0;
                $response = null;

                while ($attempt < $maxRetries) {
                    $attempt++;
                    $response = $http
                        ->timeout(60) // Increased from 30 to 60 seconds
                        ->get($employeesUrl, ['page' => $currentPage]);

                    if ($response->successful()) {
                        break;
                    }

                    // Log retry attempt
                    Log::warning('HRIS API employees fetch failed, retrying', [
                        'page' => $currentPage,
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries,
                        'status' => $response->status(),
                        'error' => $response->body(),
                    ]);

                    if ($attempt < $maxRetries) {
                        sleep(2); // Wait 2 seconds before retry
                    }
                }

                if ($response && $response->successful()) {
                    $data = $response->json();

                    // Handle new API format: { success: true, data: { current_page: 1, data: [...] } }
                    if (isset($data['success']) && isset($data['data']['data'])) {
                        $employees = $data['data']['data'] ?? [];
                        $allEmployees = array_merge($allEmployees, $employees);

                        // Check if there are more pages
                        $lastPage = $data['data']['last_page'] ?? 1;
                        $currentPage = $data['data']['current_page'] ?? 1;
                        $total = $data['data']['total'] ?? 0;

                        Log::info('HRIS API employees page fetched', [
                            'page' => $currentPage,
                            'last_page' => $lastPage,
                            'total' => $total,
                            'per_page' => $data['data']['per_page'] ?? null,
                            'employees_on_page' => count($employees),
                            'cumulative_count' => count($allEmployees),
                        ]);

                        // Continue to next page if available
                        if ($currentPage < $lastPage) {
                            $currentPage++;
                            continue;
                        }
                    }
                    // Handle old API format: { total: 82, data: [...] }
                    elseif (isset($data['data']) && is_array($data['data'])) {
                        $allEmployees = $data['data'];
                        Log::info('HRIS API employees fetched (old format)', [
                            'total' => count($allEmployees),
                        ]);
                    }
                    // Unknown format
                    else {
                        Log::warning('HRIS API employees: unknown response format', [
                            'response' => $data,
                        ]);
                    }

                    break; // Exit loop after processing
                } else {
                    Log::error('HRIS API employees fetch failed after retries', [
                        'page' => $currentPage,
                        'status' => $response ? $response->status() : 'no response',
                        'body'   => $response ? $response->body() : 'no response',
                    ]);
                    break;
                }
            } while (true);

            Log::info('HRIS API employees fetch completed', [
                'total_employees' => count($allEmployees),
                'pages_fetched' => $currentPage,
                'expected_total_pages' => $lastPage ?? 'unknown',
            ]);

            // Return whatever data we successfully fetched (even if incomplete)
            // Only fall back to mock data if using dummy API and got nothing
            if ($this->useDummy && empty($allEmployees)) {
                Log::warning('HRIS dummy API returned no data, using mock');
                return $this->mockEmployees();
            }

            return $allEmployees;
        } catch (\Exception $e) {
            Log::error('HRIS API employees error', ['error' => $e->getMessage()]);

            // Return partial data if we have any, otherwise empty array
            // Don't fall back to mock data for real API
            if (!empty($allEmployees)) {
                Log::info('Returning partial employee data due to error', [
                    'count' => count($allEmployees),
                ]);
                return $allEmployees;
            }

            return [];
        }
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
     *
     * Supports two API response formats:
     * 1. Legacy format: Array of aggregated records [{employee_id, days_present, ...}]
     * 2. New format: Wrapper with granular daily logs {status, meta, data: [{user_id, date, logs, ...}]}
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

                // Handle new wrapper format with granular daily logs
                if (is_array($data) && isset($data['status']) && isset($data['data'])) {
                    return $this->transformGranularAttendance($data['data']);
                }

                // Handle legacy format: array of aggregated records
                if (is_array($data) && ! empty($data) && ! isset($data['status'])) {
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

    /**
     * Transform granular daily log format into the structure expected by AttendanceService.
     *
     * Input format (new API):
     * [
     *   {
     *     "user_id": 1,
     *     "user_name": "ADMIN",
     *     "date": "2026-05-01",
     *     "logs": {
     *       "am": { "in": "07:55:00", "out": "12:00:00" },
     *       "pm": { "in": "13:00:00", "out": "17:00:00" },
     *       "ot": { "in": null, "out": null }
     *     },
     *     "status": "pending",
     *     "remarks": null
     *   }
     * ]
     *
     * Output format (expected by AttendanceService):
     * [
     *   "EMP001" => [
     *     "daily_logs" => [
     *       ["date" => "2026-05-01", "am" => [...], "pm" => [...]]
     *     ],
     *     "leave_credits" => 15.0
     *   ]
     * ]
     *
     * Note: user_id mapping to employee_id (EMP001 format) is handled by AttendanceService.
     * We pass the raw user_id and let AttendanceService do the mapping based on employee DB IDs.
     */
    private function transformGranularAttendance(array $granularData): array
    {
        $groupedByEmployee = [];

        foreach ($granularData as $record) {
            $userId = $record['user_id'] ?? null;
            $date = $record['date'] ?? null;

            if (! $userId || ! $date) {
                continue;
            }

            // Use user_id directly as the key - AttendanceService will handle the mapping
            // to employee_id format (EMP001, etc.) based on employee DB IDs
            $key = (string) $userId;

            if (! isset($groupedByEmployee[$key])) {
                $groupedByEmployee[$key] = [
                    'daily_logs' => [],
                    'leave_credits' => 15.0, // Default value - may need to be fetched separately
                ];
            }

            // Transform the log structure
            $logs = $record['logs'] ?? [];
            $groupedByEmployee[$key]['daily_logs'][] = [
                'date' => $date,
                'am' => [
                    'in' => $logs['am']['in'] ?? null,
                    'out' => $logs['am']['out'] ?? null,
                ],
                'pm' => [
                    'in' => $logs['pm']['in'] ?? null,
                    'out' => $logs['pm']['out'] ?? null,
                ],
                // OT logs are currently not used in computation but preserved for future use
            ];
        }

        return $groupedByEmployee;
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