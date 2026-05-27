<?php

namespace App\Models\Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;

class AllowanceType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'default_amount',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
