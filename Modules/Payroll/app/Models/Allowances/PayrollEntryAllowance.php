<?php

namespace Modules\Payroll\Models\Allowances;

use Illuminate\Database\Eloquent\Model;
use Modules\Payroll\Models\PayrollEntry;

class PayrollEntryAllowance extends Model
{
    protected $fillable = [
        'payroll_entry_id',
        'allowance_type_id',
        'code',
        'name',
        'amount',
        'is_overridden',
        'override_reason',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'is_overridden' => 'boolean',
    ];

    public function entry()
    {
        return $this->belongsTo(PayrollEntry::class, 'payroll_entry_id');
    }

    public function allowanceType()
    {
        return $this->belongsTo(AllowanceType::class);
    }
}
