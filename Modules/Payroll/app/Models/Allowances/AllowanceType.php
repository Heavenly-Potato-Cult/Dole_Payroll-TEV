<?php

namespace Modules\Payroll\Models\Allowances;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AllowanceType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'default_amount',
        'is_taxable',
        'is_gsis_deductible',
        'is_philhealth_deductible',
        'is_pagibig_deductible',
        'display_order',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_gsis_deductible' => 'boolean',
        'is_philhealth_deductible' => 'boolean',
        'is_pagibig_deductible' => 'boolean',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function employeeAllowances()
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    public function getDisplayOrderAttribute($value): int
    {
        return (int) ($value ?? $this->attributes['sort_order'] ?? 0);
    }
}
