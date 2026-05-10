<?php

namespace App\SharedKernel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfficeOrder extends Model
{
    use SoftDeletes;

    protected $table = 'office_orders';

    protected $fillable = [
        'office_order_no',
        'employee_id',
        'purpose',
        'destination',
        'travel_type',
        'travel_date_start',
        'travel_date_end',
        'status',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected $casts = [
        'travel_date_start' => 'date',
        'travel_date_end'   => 'date',
        'approved_at'       => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(\App\SharedKernel\Models\Employee::class, 'employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

public function tevRequests()
{
    return $this->hasMany(\Modules\Tev\Models\TevRequest::class, 'office_order_id');
}
}
