<?php

namespace Modules\Payroll\Models\Allowances;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AllowanceBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'period_year',
        'period_month',
        'cutoff',
        'period_start',
        'period_end',
        'status',
        'created_by',
        'prepared_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'released_by',
        'released_at',
        'remarks',
    ];

    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'prepared_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function releaser()
    {
        return $this->belongsTo(\App\Models\User::class, 'released_by');
    }

    public function entries()
    {
        return $this->hasMany(AllowanceEntry::class);
    }
}
