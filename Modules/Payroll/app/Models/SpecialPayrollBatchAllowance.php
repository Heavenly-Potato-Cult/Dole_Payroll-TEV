<?php

namespace Modules\Payroll\Models\Allowances;

use Illuminate\Database\Eloquent\Model;
use Modules\Payroll\Models\SpecialPayrollBatch;

class SpecialPayrollBatchAllowance extends Model
{
    protected $fillable = [
        'special_payroll_batch_id',
        'allowance_type_id',
        'code',
        'name',
        'full_amount',
        'amount',
        'is_overridden',
        'override_reason',
    ];

    protected $casts = [
        'full_amount'   => 'decimal:2',
        'amount'        => 'decimal:2',
        'is_overridden' => 'boolean',
    ];

    public function batch()
    {
        return $this->belongsTo(SpecialPayrollBatch::class, 'special_payroll_batch_id');
    }

    public function allowanceType()
    {
        return $this->belongsTo(AllowanceType::class);
    }
}
