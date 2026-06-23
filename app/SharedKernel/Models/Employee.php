<?php

namespace App\SharedKernel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Identification
        'employee_no',
        'hris_employee_id',
        'plantilla_item_no',

        // Personal info
        'last_name',
        'first_name',
        'middle_name',
        'suffix',
        'date_of_birth',
        'gender',
        'civil_status',

        // Position & salary
        'position_title',
        'division_id',
        'salary_grade',
        'step',
        'sit_year',
        'basic_salary',
        'pera',

        // Employment
        'employment_status',        // DB column name (was 'employment_type' — typo fixed)
        'hire_date',
        'original_appointment_date',
        'last_promotion_date',
        'official_station',

        // Government IDs
        'tin',
        'gsis_bp_no',
        'gsis_crn',
        'pagibig_no',
        'pagibig_id',
        'pagibig_mid_no',
        'mp2_account_no',
        'hdmf_mpl_app_no',
        'hdmf_cal_app_no',
        'hdmf_housing_app_no',
        'philhealth_no',
        'sss_no',

        // Leave balances
        'vacation_leave_balance',
        'sick_leave_balance',

        // Status
        'status',
        'is_excluded',
    ];

    protected $casts = [
        'salary_grade'              => 'integer',
        'step'                      => 'integer',
        'sit_year'                  => 'integer',
        'basic_salary'              => 'decimal:2',
        'pera'                      => 'decimal:2',
        'vacation_leave_balance'    => 'decimal:3',
        'sick_leave_balance'        => 'decimal:3',
        'hire_date'                 => 'date',
        'date_of_birth'             => 'date',
        'original_appointment_date' => 'date',
        'last_promotion_date'       => 'date',
        'is_excluded'               => 'boolean',
    ];

    // ── Status constants ─────────────────────────────────────────
    const STATUS_ACTIVE   = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_VACANT   = 'vacant';

    const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_VACANT,
    ];

    // ── Relationships ────────────────────────────────────────────

    public function division(): BelongsTo
    {
        return $this->belongsTo(\App\SharedKernel\Models\Division::class);
    }

    public function promotionHistory(): HasMany
    {
        return $this->hasMany(\Modules\Payroll\Models\EmployeePromotionHistory::class)
                    ->orderByDesc('effectivity_date');
    }

    /**
     * Primary relationship name used in views/forms.
     */
    public function deductions(): HasMany
    {
        return $this->hasMany(\Modules\Payroll\Models\EmployeeDeductionEnrollment::class);
    }

    /**
     * Alias — PayrollComputationService and AttendanceService use this name.
     */
    public function deductionEnrollments(): HasMany
    {
        return $this->hasMany(\Modules\Payroll\Models\EmployeeDeductionEnrollment::class);
    }

    public function payrollEntries(): HasMany
    {
        return $this->hasMany(\Modules\Payroll\Models\PayrollEntry::class);
    }

    public function employeeAllowances(): HasMany
    {
        return $this->hasMany(\Modules\Allowances\Models\EmployeeAllowance::class);
    }

    // ── Computed helpers ─────────────────────────────────────────

    /**
     * Full name: LAST, First MI.
     */
    public function getFullNameAttribute(): string
    {
        $mi     = $this->middle_name ? ' ' . mb_strtoupper(mb_substr($this->middle_name, 0, 1)) . '.' : '';
        $suffix = $this->suffix ? ', ' . $this->suffix : '';
        return $this->last_name . ', ' . $this->first_name . $mi . $suffix;
    }

    /**
     * Display name for payslips: First MI. Last
     */
    public function getDisplayNameAttribute(): string
    {
        $mi = $this->middle_name ? ' ' . mb_strtoupper(mb_substr($this->middle_name, 0, 1)) . '.' : '';
        return $this->first_name . $mi . ' ' . $this->last_name;
    }

    /**
     * Daily rate = basic_salary / 22
     */
    public function getDailyRateAttribute(): float
    {
        return round($this->basic_salary / 22, 4);
    }

    /**
     * Hourly rate = basic_salary / 22 / 8
     */
    public function getHourlyRateAttribute(): float
    {
        return round($this->basic_salary / 22 / 8, 4);
    }

    /**
     * Minute rate = basic_salary / 22 / 8 / 60
     */
    public function getMinuteRateAttribute(): float
    {
        return round($this->basic_salary / 22 / 8 / 60, 6);
    }

    /**
     * Semi-monthly gross = (basic_salary + pera) / 2
     */
    public function getSemiMonthlyGrossAttribute(): float
    {
        return round(($this->basic_salary + $this->pera) / 2, 2);
    }

    // ── Attribute aliases for PayrollComputationService ──────────

    /**
     * Alias: basic_monthly_salary → basic_salary column.
     * PayrollComputationService calls $employee->basic_monthly_salary.
     */
    public function getBasicMonthlySalaryAttribute(): float
    {
        return (float) $this->basic_salary;
    }

    /**
     * Alias: pera_amount → pera column.
     * PayrollComputationService calls $employee->pera_amount.
     */
    public function getPeraAmountAttribute(): float
    {
        return (float) $this->pera;
    }

    /**
     * RATA allowance — no 'rata' column exists on the employees table.
     * Most employees have no RATA; returns 0.0 by default.
     * Override in a future migration if RATA needs to be stored per-employee.
     */
    public function getRataAttribute(): float
    {
        return 0.0;
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByDivision($query, int $divisionId)
    {
        return $query->where('division_id', $divisionId);
    }
}
