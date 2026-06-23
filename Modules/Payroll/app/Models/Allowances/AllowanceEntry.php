<?php

namespace Modules\Payroll\Models\Allowances;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AllowanceEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'allowance_batch_id',
        'employee_id',
        'allowance_type_id',
        'amount',
        'gross_amount',
        'tax_deduction',
        'gsis_deduction',
        'philhealth_deduction',
        'pagibig_deduction',
        'total_deductions',
        'net_amount',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'tax_deduction' => 'decimal:2',
        'gsis_deduction' => 'decimal:2',
        'philhealth_deduction' => 'decimal:2',
        'pagibig_deduction' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function batch()
    {
        return $this->belongsTo(AllowanceBatch::class, 'allowance_batch_id');
    }

    public function employee()
    {
        return $this->belongsTo(\App\SharedKernel\Models\Employee::class);
    }

    public function allowanceType()
    {
        return $this->belongsTo(AllowanceType::class);
    }
}
