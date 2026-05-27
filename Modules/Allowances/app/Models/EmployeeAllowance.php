<?php

namespace Modules\Allowances\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAllowance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'allowance_type_id',
        'amount',
        'effectivity_date',
        'expiry_date',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effectivity_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\SharedKernel\Models\Employee::class);
    }

    public function allowanceType()
    {
        return $this->belongsTo(AllowanceType::class);
    }
}
