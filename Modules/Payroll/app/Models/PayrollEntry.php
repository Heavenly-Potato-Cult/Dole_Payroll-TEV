<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollEntry extends Model
{
    protected $fillable = [
        'payroll_batch_id',
        'employee_id',

        // Earnings
        'basic_salary',
        'pera',
        'rata',
        'gross_income',

        // Attendance deductions
        'lwop_days',
        'lwop_deduction',
        'tardiness',
        'undertime',

        // Deduction totals
        'withholding_tax',   // DB column present — was missing from fillable
        'total_deductions',
        'net_amount',

        // Entry state
        'status',                   // 'computed' by default — needed for updateOrCreate
        'is_manually_overridden',   // used by force-edit workflow
        'override_notes',
    ];

    protected $casts = [
        'basic_salary'           => 'decimal:2',
        'pera'                   => 'decimal:2',
        'rata'                   => 'decimal:2',
        'gross_income'           => 'decimal:2',
        'lwop_days'              => 'decimal:3',
        'lwop_deduction'         => 'decimal:2',
        'tardiness'              => 'decimal:2',
        'undertime'              => 'decimal:2',
        'withholding_tax'        => 'decimal:2',
        'total_deductions'       => 'decimal:2',
        'net_amount'             => 'decimal:2',
        'is_manually_overridden' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function batch()
    {
        return $this->belongsTo(PayrollBatch::class, 'payroll_batch_id');
    }

    public function employee()
    {
        return $this->belongsTo(\App\SharedKernel\Models\Employee::class);
    }

    public function deductions()
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    public function allowances()
    {
        return $this->hasMany(\Modules\Payroll\Models\Allowances\PayrollEntryAllowance::class, 'payroll_entry_id');
    }
}
